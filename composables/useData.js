import { ref } from 'vue';

export function useDataProcessor() {
  const processData = (jsonData) => {
    console.log('Processing API data:', jsonData);
    
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
    
    // Daily uploads from actual data
    const dailyUploads = {};
    rows.forEach(row => {
      const date = row[3];
      if (date && date.length === 8) {
        const formattedDate = `${date.slice(0,4)}-${date.slice(4,6)}-${date.slice(6,8)}`;
        dailyUploads[formattedDate] = (dailyUploads[formattedDate] || 0) + 1;
      }
    });
    
    const dailyUploadArray = Object.entries(dailyUploads)
      .map(([date, count]) => ({ date, uploads: count }))
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
        cameraData
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
        cameraData: []
      },
      geoData: []
    };
  };

  return {
    processData
  };
}