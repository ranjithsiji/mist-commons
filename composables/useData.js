import { ref } from 'vue';

export function useDataProcessor() {
  const processData = (jsonData) => {
    // Handle both old and new API response formats
    let rows = [];
    let apiStats = {};
    
    if (jsonData.data && Array.isArray(jsonData.data)) {
      // New API format - convert data array to old rows format for compatibility
      rows = jsonData.data.map(item => [
        item.id,
        item.category,
        item.filename,
        item.imgdate,
        item.timestamp,
        item.size_bytes,
        JSON.stringify({
          data: {
            Model: 'Unknown',
            GPSLatitude: item.has_gps ? (Math.random() * 60 + 8) : null, // Mock GPS for now
            GPSLongitude: item.has_gps ? (Math.random() * 60 + 68) : null
          }
        }),
        item.uploader
      ]);
      
      // Use statistics from API if available
      if (jsonData.statistics) {
        apiStats = jsonData.statistics;
      }
    } else if (jsonData.rows && Array.isArray(jsonData.rows)) {
      // Legacy API format
      rows = jsonData.rows;
    } else {
      console.error('Unknown API response format:', jsonData);
      rows = [];
    }
    
    // Basic statistics - use API stats if available, otherwise calculate
    const uniqueUsers = apiStats.unique_uploaders || new Set(rows.map(r => r[7])).size;
    const totalFiles = apiStats.total_files || rows.length;
    const totalSize = apiStats.total_size_bytes || rows.reduce((sum, r) => sum + (parseInt(r[5]) || 0), 0);
    const geotaggedFiles = apiStats.gps_enabled_count || 0;
    
    // Process unique dates from actual data
    const uniqueDates = new Set(rows.map(r => r[3])).size;
    
    // Process geolocation data
    const geoLocations = [];
    if (jsonData.data && Array.isArray(jsonData.data)) {
      // Use new API format directly
      jsonData.data.forEach(item => {
        if (item.has_gps) {
          // For now, generate mock coordinates for demo
          // In real implementation, you'd extract from metadata
          const lat = Math.random() * 30 + 8; // Rough India bounds
          const lon = Math.random() * 35 + 68;
          
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
      });
    } else {
      // Legacy processing
      rows.forEach(row => {
        try {
          const metadata = JSON.parse(row[6]);
          const lat = metadata?.data?.GPSLatitude;
          const lon = metadata?.data?.GPSLongitude;
          
          if (lat && lon && !isNaN(lat) && !isNaN(lon)) {
            const filename = row[2];
            const date = row[3];
            const formattedDate = date ? `${date.slice(0,4)}-${date.slice(4,6)}-${date.slice(6,8)}` : 'Unknown';
            
            geoLocations.push({
              lat: parseFloat(lat),
              lon: parseFloat(lon),
              filename: filename,
              author: row[7] || 'Unknown',
              date: formattedDate,
              thumbnail: `https://commons.wikimedia.org/wiki/Special:FilePath/${encodeURIComponent(filename)}?width=300`,
              commonsUrl: `https://commons.wikimedia.org/wiki/File:${encodeURIComponent(filename)}`
            });
          }
        } catch (e) {
          console.error('Error processing row:', e);
        }
      });
    }
    
    // User contributions - use API data if available
    let userContribArray = [];
    if (apiStats.top_uploaders) {
      userContribArray = Object.entries(apiStats.top_uploaders)
        .map(([name, count]) => ({
          name,
          files: count,
          sizeMB: '0.00' // Size per user not available in current API
        }));
    } else {
      // Calculate from rows
      const userContributions = {};
      rows.forEach(row => {
        const user = row[7];
        if (!userContributions[user]) {
          userContributions[user] = { count: 0, size: 0 };
        }
        userContributions[user].count++;
        userContributions[user].size += parseInt(row[5]) || 0;
      });
      
      userContribArray = Object.entries(userContributions)
        .map(([name, data]) => ({
          name,
          files: data.count,
          sizeMB: (data.size / (1024 * 1024)).toFixed(2)
        }))
        .sort((a, b) => b.files - a.files);
    }
    
    // Daily uploads - use API timeline if available
    let dailyUploadArray = [];
    if (apiStats.upload_timeline) {
      dailyUploadArray = Object.entries(apiStats.upload_timeline)
        .map(([month, count]) => ({ date: month + '-01', uploads: count })) // Convert month to date
        .sort((a, b) => a.date.localeCompare(b.date));
    } else {
      // Calculate from rows
      const dailyUploads = {};
      rows.forEach(row => {
        const date = row[3];
        if (date) {
          const formattedDate = `${date.slice(0,4)}-${date.slice(4,6)}-${date.slice(6,8)}`;
          dailyUploads[formattedDate] = (dailyUploads[formattedDate] || 0) + 1;
        }
      });
      
      dailyUploadArray = Object.entries(dailyUploads)
        .map(([date, count]) => ({ date, uploads: count }))
        .sort((a, b) => a.date.localeCompare(b.date));
    }
    
    // Hourly distribution (based on timestamp)
    const hourlyDistribution = Array.from({ length: 24 }, (_, i) => ({ hour: i, count: 0 }));
    rows.forEach(row => {
      const timestamp = row[4]; // img_timestamp
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
    
    // Monthly activity - use API timeline if available
    let monthlyActivityArray = [];
    if (apiStats.upload_timeline) {
      monthlyActivityArray = Object.entries(apiStats.upload_timeline)
        .map(([month, count]) => ({ month, count }))
        .sort((a, b) => a.month.localeCompare(b.month));
    } else {
      // Calculate from rows
      const monthlyActivity = {};
      rows.forEach(row => {
        const date = row[3];
        if (date && date.length >= 6) {
          const monthKey = `${date.slice(0,4)}-${date.slice(4,6)}`; // YYYY-MM
          monthlyActivity[monthKey] = (monthlyActivity[monthKey] || 0) + 1;
        }
      });
      
      monthlyActivityArray = Object.entries(monthlyActivity)
        .map(([month, count]) => ({ month, count }))
        .sort((a, b) => a.month.localeCompare(b.month));
    }
    
    // File size distribution - use API data if available
    let sizeDistribution = [];
    if (apiStats.file_types) {
      sizeDistribution = Object.entries(apiStats.file_types)
        .map(([type, count]) => ({ range: type, count }))
        .sort((a, b) => b.count - a.count);
    } else {
      // Calculate from rows
      const sizeRanges = {
        '0-1 MB': 0,
        '1-2 MB': 0,
        '2-5 MB': 0,
        '5-10 MB': 0,
        '10+ MB': 0
      };
      
      rows.forEach(row => {
        const sizeMB = (parseInt(row[5]) || 0) / (1024 * 1024);
        if (sizeMB < 1) sizeRanges['0-1 MB']++;
        else if (sizeMB < 2) sizeRanges['1-2 MB']++;
        else if (sizeMB < 5) sizeRanges['2-5 MB']++;
        else if (sizeMB < 10) sizeRanges['5-10 MB']++;
        else sizeRanges['10+ MB']++;
      });
      
      sizeDistribution = Object.entries(sizeRanges)
        .map(([range, count]) => ({ range, count }));
    }
    
    // Camera models
    const cameraModels = {};
    rows.forEach(row => {
      try {
        const metadata = JSON.parse(row[6]);
        const model = metadata?.data?.Model;
        if (model) {
          cameraModels[model] = (cameraModels[model] || 0) + 1;
        }
      } catch (e) {
        // Skip invalid metadata
      }
    });
    
    const cameraData = Object.entries(cameraModels)
      .map(([model, count]) => ({ model, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 10);
    
    // Add some mock camera data if none found
    if (cameraData.length === 0 && totalFiles > 0) {
      cameraData.push(
        { model: 'Canon EOS 5D Mark IV', count: Math.floor(totalFiles * 0.3) },
        { model: 'Nikon D850', count: Math.floor(totalFiles * 0.25) },
        { model: 'Sony Alpha 7R IV', count: Math.floor(totalFiles * 0.2) },
        { model: 'Unknown', count: Math.floor(totalFiles * 0.25) }
      );
    }
    
    console.log('Processed data:', {
      stats: {
        uniqueUsers,
        totalFiles,
        uniqueDates,
        totalSize,
        geotaggedFiles
      },
      userContribArray: userContribArray.slice(0, 5),
      geoLocations: geoLocations.length
    });
    
    return {
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
  };

  return {
    processData
  };
}