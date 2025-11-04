import { ref } from 'vue';

// Use environment variables with fallback to local development
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';
const API_TIMEOUT = import.meta.env.VITE_API_TIMEOUT || 30000;

export function useApi() {
  const loading = ref(false);
  const error = ref('');

  // Create fetch with timeout
  const fetchWithTimeout = async (url, options = {}) => {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT);
    
    try {
      const response = await fetch(url, {
        ...options,
        signal: controller.signal,
        headers: {
          'Content-Type': 'application/json',
          ...options.headers,
        }
      });
      clearTimeout(timeoutId);
      return response;
    } catch (err) {
      clearTimeout(timeoutId);
      if (err.name === 'AbortError') {
        throw new Error('Request timeout - server may be unavailable');
      }
      throw err;
    }
  };

  const fetchCategories = async () => {
    loading.value = true;
    error.value = '';
    
    try {
      console.log('Fetching categories from:', `${API_BASE_URL}/categories.php`);
      
      const response = await fetchWithTimeout(`${API_BASE_URL}/categories.php`);
      
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${response.statusText}${errorText ? ` - ${errorText}` : ''}`);
      }
      
      const jsonData = await response.json();
      console.log('Categories response:', jsonData);
      
      if (!jsonData.success) {
        throw new Error(jsonData.error || 'API returned success: false');
      }
      
      return jsonData.categories || [];
    } catch (err) {
      const errorMessage = `Failed to load categories: ${err.message}`;
      error.value = errorMessage;
      console.error('Categories fetch error:', err);
      
      // For development, provide fallback mock data
      if (import.meta.env.DEV) {
        console.warn('Using fallback mock data for development');
        return getMockCategories();
      }
      
      throw new Error(errorMessage);
    } finally {
      loading.value = false;
    }
  };

  const fetchDashboardData = async (categoryName, isCustomCategory = false) => {
    if (!categoryName) {
      throw new Error('Category name is required');
    }

    loading.value = true;
    error.value = '';
    
    try {
      // Build parameters for API call
      const params = new URLSearchParams({
        category: categoryName
      });
      
      // Add mock parameter for testing (you can remove this later)
      if (import.meta.env.DEV || import.meta.env.VITE_DEV_MOCK_DATA) {
        params.append('mock', '1');
      }
      
      // Add custom flag if it's a user-defined category
      if (isCustomCategory) {
        params.append('custom', '1');
      }
      
      const url = `${API_BASE_URL}/dashboard.php?${params.toString()}`;
      console.log('Fetching dashboard data from:', url);
      
      const response = await fetchWithTimeout(url);
      
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${response.statusText}${errorText ? ` - ${errorText}` : ''}`);
      }
      
      const jsonData = await response.json();
      console.log('Dashboard response:', jsonData);
      
      if (!jsonData.success) {
        throw new Error(jsonData.error || 'Failed to fetch dashboard data');
      }
      
      return jsonData;
    } catch (err) {
      const errorMessage = `Failed to load dashboard data: ${err.message}`;
      error.value = errorMessage;
      console.error('Dashboard fetch error:', err);
      
      // For development, provide fallback mock data
      if (import.meta.env.DEV) {
        console.warn('Using fallback mock data for development');
        return getMockDashboardData(categoryName);
      }
      
      throw new Error(errorMessage);
    } finally {
      loading.value = false;
    }
  };

  // Function to validate if a category exists on Commons
  const validateCategory = async (categoryName) => {
    try {
      const apiUrl = 'https://commons.wikimedia.org/w/api.php';
      let searchQuery = categoryName;
      
      // Add "Category:" prefix if not present
      if (!searchQuery.startsWith('Category:')) {
        searchQuery = `Category:${categoryName}`;
      }
      
      const params = new URLSearchParams({
        action: 'query',
        format: 'json',
        origin: '*',
        titles: searchQuery,
        prop: 'categoryinfo|info',
        formatversion: '2'
      });
      
      const response = await fetch(`${apiUrl}?${params}`);
      const data = await response.json();
      
      if (data.query && data.query.pages && data.query.pages.length > 0) {
        const page = data.query.pages[0];
        return {
          exists: !page.missing,
          pageCount: page.categoryinfo ? page.categoryinfo.pages : 0,
          title: page.title
        };
      }
      
      return { exists: false, pageCount: 0, title: searchQuery };
    } catch (error) {
      console.error('Category validation error:', error);
      return { exists: false, pageCount: 0, title: categoryName, error: error.message };
    }
  };

  // Mock data for development/fallback
  const getMockCategories = () => {
    return [
      {
        id: 'wlb-india-2024',
        name: 'Wiki Loves Birds India 2024',
        slug: 'wiki-loves-birds-india-2024',
        description: 'Photography contest celebrating Indian bird diversity',
        categoryName: 'Images_from_Wiki_Loves_Birds_India_2024_(maintenance-earth)',
        icon: '🦅',
        year: '2024',
        color1: '#3B82F6',
        color2: '#1D4ED8'
      },
      {
        id: 'wlm-india-2023',
        name: 'Wiki Loves Monuments India 2023',
        slug: 'wiki-loves-monuments-india-2023',
        description: 'Documenting India\'s architectural heritage',
        categoryName: 'Images_from_Wiki_Loves_Monuments_2023_in_India',
        icon: '🏛️',
        year: '2023',
        color1: '#F59E0B',
        color2: '#D97706'
      },
      {
        id: 'wle-india-2023',
        name: 'Wiki Loves Earth India 2023',
        slug: 'wiki-loves-earth-india-2023',
        description: 'Capturing natural heritage and protected areas',
        categoryName: 'Images_from_Wiki_Loves_Earth_2023_in_India',
        icon: '🌍',
        year: '2023',
        color1: '#10B981',
        color2: '#059669'
      }
    ];
  };

  const getMockDashboardData = (categoryName) => {
    // Return data in new API format to match backend
    return {
      success: true,
      data: [
        {
          id: 1,
          category: categoryName,
          filename: 'Sample_Image_1.jpg',
          page_title: 'Sample_Image_1.jpg',
          upload_date: '2024-10-01',
          imgdate: '20241001',
          timestamp: '20241001120000',
          size_bytes: 2048000,
          size_mb: 1.95,
          dimensions: { width: 3000, height: 2000 },
          media_type: 'BITMAP',
          mime_type: 'image/jpeg',
          uploader: 'SampleUser1',
          has_gps: true,
          metadata_available: true
        },
        {
          id: 2,
          category: categoryName,
          filename: 'Sample_Image_2.jpg',
          page_title: 'Sample_Image_2.jpg',
          upload_date: '2024-10-02',
          imgdate: '20241002',
          timestamp: '20241002130000',
          size_bytes: 3072000,
          size_mb: 2.93,
          dimensions: { width: 4000, height: 3000 },
          media_type: 'BITMAP',
          mime_type: 'image/jpeg',
          uploader: 'SampleUser2',
          has_gps: false,
          metadata_available: true
        },
        {
          id: 3,
          category: categoryName,
          filename: 'Sample_Image_3.jpg',
          page_title: 'Sample_Image_3.jpg',
          upload_date: '2024-10-03',
          imgdate: '20241003',
          timestamp: '20241003140000',
          size_bytes: 1536000,
          size_mb: 1.46,
          dimensions: { width: 2500, height: 1800 },
          media_type: 'BITMAP',
          mime_type: 'image/jpeg',
          uploader: 'SampleUser1',
          has_gps: true,
          metadata_available: true
        }
      ],
      statistics: {
        total_files: 3,
        total_size_bytes: 6656000,
        total_size_mb: 6.35,
        uploaders: {
          'SampleUser1': 2,
          'SampleUser2': 1
        },
        file_types: {
          'image/jpeg': 3
        },
        upload_timeline: {
          '2024-10': 3
        },
        gps_enabled_count: 2,
        unique_uploaders: 2,
        gps_percentage: 66.67,
        top_uploaders: {
          'SampleUser1': 2,
          'SampleUser2': 1
        }
      },
      meta: {
        category: categoryName,
        query_time_ms: 50,
        total_records: 3,
        timestamp: new Date().toISOString().replace('T', ' ').slice(0, 19),
        database: 'mock_data',
        mock_data: true,
        cached: false
      }
    };
  };

  return {
    loading,
    error,
    fetchCategories,
    fetchDashboardData,
    validateCategory
  };
}