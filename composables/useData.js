import { ref } from 'vue';

export function useDataProcessor() {
  const processData = (jsonData) => {
    console.log('Processing API data:', jsonData);
    
    // Use API statistics directly when available (much more reliable)
    let apiStats = jsonData.statistics || {};
    
    // Basic statistics - use API stats directly
    const uniqueUsers = apiStats.unique_uploaders || 0;
    const totalFiles = apiStats.total_files || 0;
    const totalSize = apiStats.total_size_bytes || 0;
    const geotaggedFiles = apiStats.gps_enabled_count || 0;
    
    // Calculate unique dates from data
    const uniqueDates = jsonData.data ? new Set(jsonData.data.map(item => item.upload_date)).size : 0;
    
    // Process real geolocation data from GPS coordinates
    const geoLocations = [];
    if (jsonData.data && Array.isArray(jsonData.data)) {
      jsonData.data.forEach(item => {
        if (item.has_gps && item.gps_latitude && item.gps_longitude) {
          // Use actual GPS coordinates from EXIF data
          const lat = parseFloat(item.gps_latitude);
          const lon = parseFloat(item.gps_longitude);
          
          // Validate coordinates are within reasonable bounds
          if (lat >= -90 && lat <= 90 && lon >= -180 && lon <= 180) {
            geoLocations.push({
              lat: lat,
              lon: lon,
              filename: item.filename,
              author: item.uploader || 'Unknown',
              date: item.upload_date,
              thumbnail: `https://commons.wikimedia.org/wiki/Special:FilePath/${encodeURIComponent(item.filename)}?width=300`,
              commonsUrl: `https://commons.wikimedia.org/wiki/File:${encodeURIComponent(item.filename)}`
            });
          }
        }
      });
    }
    
    console.log(`Found ${geoLocations.length} geotagged images with valid coordinates`);
    
    // User contributions with actual file sizes
    let userContribArray = [];
    if (apiStats.top_uploaders && jsonData.data) {
      // Calculate size per user from the actual data
      const userSizes = {};
      jsonData.data.forEach(item => {
        const user = item.uploader;
        if (!userSizes[user]) {
          userSizes[user] = 0;
        }
        userSizes[user] += item.size_bytes || 0;
      });
      
      userContribArray = Object.entries(apiStats.top_uploaders)
        .map(([name, count]) => ({
          name,
          files: count,
          sizeMB: ((userSizes[name] || 0) / (1024 * 1024)).toFixed(2)
        }))
        .sort((a, b) => b.files - a.files);
    }
    
    // Daily uploads from actual data
    let dailyUploadArray = [];
    if (jsonData.data && Array.isArray(jsonData.data)) {
      const dailyUploads = {};
      jsonData.data.forEach(item => {
        const date = item.upload_date;
        if (date) {
          dailyUploads[date] = (dailyUploads[date] || 0) + 1;
        }
      });
      
      dailyUploadArray = Object.entries(dailyUploads)
        .map(([date, count]) => ({ date, uploads: count }))
        .sort((a, b) => a.date.localeCompare(b.date));
    }
    
    // Hourly distribution from timestamps
    const hourlyDistribution = Array.from({ length: 24 }, (_, i) => ({ hour: i, count: 0 }));
    if (jsonData.data && Array.isArray(jsonData.data)) {
      jsonData.data.forEach(item => {
        const timestamp = item.timestamp;
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
    }
    
    // Monthly activity from API timeline
    let monthlyActivityArray = [];
    if (apiStats.upload_timeline) {
      monthlyActivityArray = Object.entries(apiStats.upload_timeline)
        .map(([month, count]) => ({ month, count }))
        .sort((a, b) => a.month.localeCompare(b.month));
    }
    
    // File size distribution based on actual data - use realistic ranges
    const sizeRanges = {
      '< 1 MB': 0,
      '1-5 MB': 0,
      '5-10 MB': 0,
      '10-15 MB': 0,
      '> 15 MB': 0
    };
    
    if (jsonData.data && Array.isArray(jsonData.data)) {
      jsonData.data.forEach(item => {
        const sizeMB = item.size_mb || 0;
        if (sizeMB < 1) sizeRanges['< 1 MB']++;
        else if (sizeMB < 5) sizeRanges['1-5 MB']++;
        else if (sizeMB < 10) sizeRanges['5-10 MB']++;
        else if (sizeMB < 15) sizeRanges['10-15 MB']++;
        else sizeRanges['> 15 MB']++;
      });
    }
    
    const sizeDistribution = Object.entries(sizeRanges)
      .map(([range, count]) => ({ range, count }))
      .filter(item => item.count > 0); // Only show ranges with data
    
    // Camera models from API statistics (now properly parsed from EXIF)
    let cameraData = [];
    if (apiStats.top_camera_models) {
      cameraData = Object.entries(apiStats.top_camera_models)
        .map(([model, count]) => ({ model, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 10);
    } else if (apiStats.camera_models) {
      cameraData = Object.entries(apiStats.camera_models)
        .map(([model, count]) => ({ model, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 10);
    }
    
    // If no camera data, try to extract from individual files
    if (cameraData.length === 0 && jsonData.data && Array.isArray(jsonData.data)) {
      const cameraCounts = {};
      jsonData.data.forEach(item => {
        const camera = item.camera_model || 'Unknown Camera';
        if (!cameraCounts[camera]) {
          cameraCounts[camera] = 0;
        }
        cameraCounts[camera]++;
      });
      
      cameraData = Object.entries(cameraCounts)
        .map(([model, count]) => ({ model, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 10);
    }
    
    const result = {
      stats: {
        uniqueUsers,
        totalFiles,
        uniqueDates,
        totalSize,
        avgFileSize: totalFiles > 0 ? totalSize / totalFiles : 0,
        geotaggedFiles
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
        sizeMB: (result.stats.totalSize / 1024 / 1024).toFixed(2),
        geotagged: result.stats.geotaggedFiles,
        actualGeoPoints: result.geoData.length
      },
      distributions: {
        topContributors: result.data.userContributions.slice(0, 3).map(u => `${u.name}: ${u.files} files`),
        sizeBuckets: result.data.sizeDistribution.map(s => `${s.range}: ${s.count}`),
        topCameras: result.data.cameraData.slice(0, 3).map(c => `${c.model}: ${c.count}`),
        uploadDates: result.data.dailyUploads.length
      }
    });
    
    return result;
  };

  return {
    processData
  };
}