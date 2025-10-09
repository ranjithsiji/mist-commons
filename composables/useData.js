import { ref } from 'vue';

export function useDataProcessor() {
  const processData = (jsonData) => {
    const rows = jsonData.rows || [];
    
    // Basic statistics
    const uniqueUsers = new Set(rows.map(r => r[7])).size;
    const totalFiles = rows.length;
    const uniqueDates = new Set(rows.map(r => r[3])).size;
    const totalSize = rows.reduce((sum, r) => sum + (parseInt(r[5]) || 0), 0);
    
    // Process geolocation data
    const geoLocations = [];
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
    
    // User contributions
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
    
    // Daily uploads
    const dailyUploads = {};
    rows.forEach(row => {
      const date = row[3];
      if (date) {
        const formattedDate = `${date.slice(0,4)}-${date.slice(4,6)}-${date.slice(6,8)}`;
        dailyUploads[formattedDate] = (dailyUploads[formattedDate] || 0) + 1;
      }
    });
    
    const dailyUploadArray = Object.entries(dailyUploads)
      .map(([date, count]) => ({ date, uploads: count }))
      .sort((a, b) => a.date.localeCompare(b.date));
    
    // File size distribution
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
    
    const sizeDistribution = Object.entries(sizeRanges)
      .map(([range, count]) => ({ range, count }));
    
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
    
    return {
      stats: {
        uniqueUsers,
        totalFiles,
        uniqueDates,
        totalSize,
        avgFileSize: totalSize / totalFiles,
        geotaggedFiles: geoLocations.length
      },
      data: {
        userContributions: userContribArray,
        dailyUploads: dailyUploadArray,
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