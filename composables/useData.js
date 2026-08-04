import { ref } from 'vue';

// Convert a MediaWiki YYYYMMDD date into YYYY-MM-DD
const formatImgDate = (date) =>
  date.length >= 8 ? `${date.slice(0, 4)}-${date.slice(4, 6)}-${date.slice(6, 8)}` : '';

// Convert a MediaWiki YYYYMMDDHHmmss timestamp into HH:mm (UTC, as stored)
const formatImgTime = (timestamp) =>
  timestamp.length >= 12 ? `${timestamp.slice(8, 10)}:${timestamp.slice(10, 12)}` : '';

// Turn a raw SQL row into the shape the file browsers render.
// Row layout: [cl_from, category, filename, imgdate, timestamp, size, metadata, uploader]
const buildFileEntry = (row) => {
  const filename = row[2];
  if (!filename) return null;

  // Dates and timestamps are compared as strings when sorting and grouping;
  // coerce them since some data sources return them as numbers.
  const imgdate = String(row[3] ?? '');
  const timestamp = String(row[4] ?? '');

  const encoded = encodeURIComponent(filename);
  let camera = '';
  let lat = null;
  let lon = null;

  try {
    const metadata = JSON.parse(row[6] || '{}');
    camera = metadata?.data?.Model || '';
    const rawLat = parseFloat(metadata?.data?.GPSLatitude);
    const rawLon = parseFloat(metadata?.data?.GPSLongitude);
    if (!isNaN(rawLat) && !isNaN(rawLon) &&
        rawLat >= -90 && rawLat <= 90 && rawLon >= -180 && rawLon <= 180) {
      lat = rawLat;
      lon = rawLon;
    }
  } catch (e) {
    // Metadata is frequently malformed or empty; a file without it is still valid
  }

  const size = parseInt(row[5]) || 0;

  return {
    filename,
    title: String(filename).replace(/_/g, ' '),
    user: row[7] || 'Unknown',
    date: formatImgDate(imgdate),
    time: formatImgTime(timestamp),
    timestamp,
    size,
    sizeMB: (size / (1024 * 1024)).toFixed(2),
    camera,
    lat,
    lon,
    thumbnail: `https://commons.wikimedia.org/wiki/Special:FilePath/${encoded}?width=320`,
    commonsUrl: `https://commons.wikimedia.org/wiki/File:${encoded}`
  };
};

export function useDataProcessor() {
  const processData = (jsonData) => {
    //console.log('Processing API data:', jsonData);
    
    // Handle the original "rows" format from Quarry SQL output
    const rows = jsonData.rows || [];
    if (rows.length === 0) {
      console.warn('No data rows found in API response');
      return getEmptyStats();
    }
    
    // Process each row to extract statistics
    const uniqueUsers = new Set(rows.map(r => r[7])).size;
    const totalFiles = rows.length;
    const totalSize = rows.reduce((sum, r) => sum + (parseInt(r[5]) || 0), 0);
    const uniqueDates = new Set(rows.map(r => r[3])).size;

    // Full per-file list, used by the daily and per-user file browsers
    const files = rows.map(row => buildFileEntry(row)).filter(Boolean);
    
    // Process geolocation data from metadata
    const geoLocations = [];
    let geotaggedCount = 0;
    
    rows.forEach(row => {
      try {
        const metadataJson = row[6];
        if (metadataJson && metadataJson !== '{}') {
          const metadata = JSON.parse(metadataJson);
          const lat = metadata?.data?.GPSLatitude;
          const lon = metadata?.data?.GPSLongitude;
          
          if (lat && lon && !isNaN(parseFloat(lat)) && !isNaN(parseFloat(lon))) {
            const latitude = parseFloat(lat);
            const longitude = parseFloat(lon);
            
            // Validate coordinates are within reasonable bounds
            if (latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180) {
              geotaggedCount++;
              
              const filename = row[2];
              const date = row[3];
              const formattedDate = date ? `${date.slice(0,4)}-${date.slice(4,6)}-${date.slice(6,8)}` : 'Unknown';
              
              geoLocations.push({
                lat: latitude,
                lon: longitude,
                filename: filename,
                author: row[7] || 'Unknown',
                date: formattedDate,
                thumbnail: `https://commons.wikimedia.org/wiki/Special:FilePath/${encodeURIComponent(filename)}?width=300`,
                commonsUrl: `https://commons.wikimedia.org/wiki/File:${encodeURIComponent(filename)}`
              });
            }
          }
        }
      } catch (e) {
        // Skip invalid metadata, don't log as this is common
      }
    });
    
    console.log(`Found ${geoLocations.length} valid GPS coordinates from ${geotaggedCount} geotagged files`);
    
    // User contributions with actual sizes
    const userContributions = {};
    rows.forEach(row => {
      const user = row[7];
      if (!userContributions[user]) {
        userContributions[user] = { count: 0, size: 0 };
      }
      userContributions[user].count++;
      userContributions[user].size += parseInt(row[5]) || 0;
    });
    
    const userContribArray = Object.entries(userContributions)
      .map(([name, data]) => ({
        name,
        files: data.count,
        sizeMB: (data.size / (1024 * 1024)).toFixed(2)
      }))
      .sort((a, b) => b.files - a.files);
    
    // Daily uploads from actual data, with the files behind each day
    const filesByDate = {};
    const filesByUser = {};
    files.forEach(file => {
      if (file.date) {
        (filesByDate[file.date] = filesByDate[file.date] || []).push(file);
      }
      (filesByUser[file.user] = filesByUser[file.user] || []).push(file);
    });

    // Newest first within a day, so the browsers open on the most recent upload
    Object.values(filesByDate).forEach(list => list.sort((a, b) => b.timestamp.localeCompare(a.timestamp)));
    Object.values(filesByUser).forEach(list => list.sort((a, b) => b.timestamp.localeCompare(a.timestamp)));

    const dailyUploadArray = Object.entries(filesByDate)
      .map(([date, dayFiles]) => ({
        date,
        uploads: dayFiles.length,
        contributors: new Set(dayFiles.map(f => f.user)).size,
        size: dayFiles.reduce((sum, f) => sum + f.size, 0)
      }))
      .sort((a, b) => a.date.localeCompare(b.date));
    
    // Hourly distribution from timestamps
    const hourlyDistribution = Array.from({ length: 24 }, (_, i) => ({ hour: i, count: 0 }));
    rows.forEach(row => {
      const timestamp = row[4]; // Full timestamp
      if (timestamp && timestamp.length >= 10) {
        try {
          const hour = parseInt(timestamp.slice(8, 10)); // Extract hour from YYYYMMDDHHmmss
          if (hour >= 0 && hour <= 23) {
            hourlyDistribution[hour].count++;
          }
        } catch (e) {
          // Skip invalid timestamps
        }
      }
    });
    
    // Monthly activity
    const monthlyActivity = {};
    rows.forEach(row => {
      const date = row[3];
      if (date && date.length >= 6) {
        const monthKey = `${date.slice(0,4)}-${date.slice(4,6)}`; // YYYY-MM
        monthlyActivity[monthKey] = (monthlyActivity[monthKey] || 0) + 1;
      }
    });
    
    const monthlyActivityArray = Object.entries(monthlyActivity)
      .map(([month, count]) => ({ month, count }))
      .sort((a, b) => a.month.localeCompare(b.month));
    
    // File size distribution based on actual file sizes
    const sizeRanges = {
      '< 1 MB': 0,
      '1-5 MB': 0,
      '5-10 MB': 0,
      '10-15 MB': 0,
      '> 15 MB': 0
    };
    
    rows.forEach(row => {
      const sizeMB = (parseInt(row[5]) || 0) / (1024 * 1024);
      if (sizeMB < 1) sizeRanges['< 1 MB']++;
      else if (sizeMB < 5) sizeRanges['1-5 MB']++;
      else if (sizeMB < 10) sizeRanges['5-10 MB']++;
      else if (sizeMB < 15) sizeRanges['10-15 MB']++;
      else sizeRanges['> 15 MB']++;
    });
    
    const sizeDistribution = Object.entries(sizeRanges)
      .map(([range, count]) => ({ range, count }))
      .filter(item => item.count > 0);
    
    // Camera models from metadata
    const cameraModels = {};
    rows.forEach(row => {
      try {
        const metadataJson = row[6];
        if (metadataJson && metadataJson !== '{}') {
          const metadata = JSON.parse(metadataJson);
          const model = metadata?.data?.Model;
          if (model && model.trim() !== '') {
            cameraModels[model] = (cameraModels[model] || 0) + 1;
          }
        }
      } catch (e) {
        // Skip invalid metadata
      }
    });
    
    // Add filename-based camera detection as fallback
    let unknownCameraCount = 0;
    rows.forEach(row => {
      const filename = row[2].toUpperCase();
      let detected = false;
      
      // Check if we already have camera info for this file
      try {
        const metadata = JSON.parse(row[6] || '{}');
        if (metadata?.data?.Model) {
          detected = true;
        }
      } catch (e) {}
      
      if (!detected) {
        if (filename.includes('DSC_')) {
          cameraModels['Nikon DSLR'] = (cameraModels['Nikon DSLR'] || 0) + 1;
        } else if (filename.includes('IMG_')) {
          cameraModels['Canon DSLR'] = (cameraModels['Canon DSLR'] || 0) + 1;
        } else if (filename.includes('IMG') && filename.includes('202')) {
          cameraModels['Smartphone'] = (cameraModels['Smartphone'] || 0) + 1;
        } else {
          unknownCameraCount++;
        }
      }
    });
    
    if (unknownCameraCount > 0) {
      cameraModels['Unknown Camera'] = unknownCameraCount;
    }
    
    const cameraData = Object.entries(cameraModels)
      .map(([model, count]) => ({ model, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 10);
    
    const result = {
      stats: {
        uniqueUsers,
        totalFiles,
        uniqueDates,
        totalSize,
        avgFileSize: totalFiles > 0 ? totalSize / totalFiles : 0,
        geotaggedFiles: geotaggedCount
      },
      data: {
        userContributions: userContribArray,
        dailyUploads: dailyUploadArray,
        hourlyDistribution,
        monthlyActivity: monthlyActivityArray,
        sizeDistribution,
        cameraData,
        files,
        filesByDate,
        filesByUser
      },
      geoData: geoLocations
    };
    
    console.log('Processed data summary:', {
      stats: {
        users: result.stats.uniqueUsers,
        files: result.stats.totalFiles,
        dates: result.stats.uniqueDates,
        sizeMB: (result.stats.totalSize / 1024 / 1024).toFixed(2),
        geotagged: result.stats.geotaggedFiles,
        validGeoPoints: result.geoData.length
      },
      distributions: {
        contributors: result.data.userContributions.length,
        sizeBuckets: result.data.sizeDistribution.length,
        cameras: result.data.cameraData.length,
        uploadDays: result.data.dailyUploads.length
      }
    });
    
    return result;
  };
  
  // Helper function for empty stats
  const getEmptyStats = () => {
    return {
      stats: {
        uniqueUsers: 0,
        totalFiles: 0,
        uniqueDates: 0,
        totalSize: 0,
        avgFileSize: 0,
        geotaggedFiles: 0
      },
      data: {
        userContributions: [],
        dailyUploads: [],
        hourlyDistribution: Array.from({ length: 24 }, (_, i) => ({ hour: i, count: 0 })),
        monthlyActivity: [],
        sizeDistribution: [],
        cameraData: [],
        files: [],
        filesByDate: {},
        filesByUser: {}
      },
      geoData: []
    };
  };

  return {
    processData
  };
}