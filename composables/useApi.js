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
      // For custom categories, we need to send them as custom parameter
      const params = new URLSearchParams({
        sample: '1',
        category: categoryName
      });
      
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
    return {
      success: true,
      rows: [
        [1, categoryName, 'Sample_Image_1.jpg', '20241001', '20241001120000', 2048000, '{"data":{"Model":"Canon EOS 5D","GPSLatitude":28.6139,"GPSLongitude":77.2090}}', 'SampleUser1'],
        [2, categoryName, 'Sample_Image_2.jpg', '20241002', '20241002130000', 3072000, '{"data":{"Model":"Nikon D850"}}', 'SampleUser2'],
        [3, categoryName, 'Sample_Image_3.jpg', '20241003', '20241003140000', 1536000, '{"data":{"Model":"Sony Alpha 7R"}}', 'SampleUser1'],
      ],
      count: 3,
      timestamp: new Date().toISOString(),
      category: categoryName,
      cached: false
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