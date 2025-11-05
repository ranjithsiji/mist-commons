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
      const response = await fetchWithTimeout(`${API_BASE_URL}/categories.php`);
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${response.statusText}${errorText ? ` - ${errorText}` : ''}`);
      }
      const jsonData = await response.json();
      if (!jsonData.success) {
        throw new Error(jsonData.error || 'API returned success: false');
      }
      return jsonData.categories || [];
    } catch (err) {
      const errorMessage = `Failed to load categories: ${err.message}`;
      error.value = errorMessage;
      if (import.meta.env.DEV) {
        return getMockCategories();
      }
      throw new Error(errorMessage);
    } finally {
      loading.value = false;
    }
  };

  const fetchDashboardData = async (categoryName, isCustomCategory = false, dateRange = {}) => {
    if (!categoryName) {
      throw new Error('Category name is required');
    }

    loading.value = true;
    error.value = '';
    
    try {
      // Build parameters for API call
      const params = new URLSearchParams({ category: categoryName });
      if (isCustomCategory) params.append('custom', '1');
      if (dateRange.startDate) params.append('start', dateRange.startDate);
      if (dateRange.endDate) params.append('end', dateRange.endDate);
      
      const url = `${API_BASE_URL}/dashboard.php?${params.toString()}`;
      const response = await fetchWithTimeout(url);
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${response.statusText}${errorText ? ` - ${errorText}` : ''}`);
      }
      const jsonData = await response.json();
      if (!jsonData.success) {
        throw new Error(jsonData.error || 'Failed to fetch dashboard data');
      }
      return jsonData;
    } catch (err) {
      const errorMessage = `Failed to load dashboard data: ${err.message}`;
      error.value = errorMessage;
      if (import.meta.env.DEV) {
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
      return { exists: false, pageCount: 0, title: categoryName, error: error.message };
    }
  };

  // Mock data helpers (unchanged)
  const getMockCategories = () => [
    { id: 'wlb-india-2024', name: 'Wiki Loves Birds India 2024', slug: 'wiki-loves-birds-india-2024', description: 'Photography contest celebrating Indian bird diversity', categoryName: 'Images_from_Wiki_Loves_Birds_India_2024_(maintenance-earth)', icon: '🦅', year: '2024', color1: '#3B82F6', color2: '#1D4ED8' },
    { id: 'wlm-india-2023', name: 'Wiki Loves Monuments India 2023', slug: 'wiki-loves-monuments-india-2023', description: 'Documenting India\'s architectural heritage', categoryName: 'Images_from_Wiki_Loves_Monuments_2023_in_India', icon: '🏛️', year: '2023', color1: '#F59E0B', color2: '#D97706' },
    { id: 'wle-india-2023', name: 'Wiki Loves Earth India 2023', slug: 'wiki-loves-earth-india-2023', description: 'Capturing natural heritage and protected areas', categoryName: 'Images_from_Wiki_Loves_Earth_2023_in_India', icon: '🌍', year: '2023', color1: '#10B981', color2: '#059669' }
  ];

  const getMockDashboardData = (categoryName) => {
    const mockRows = [];
    for (let i = 1; i <= 10; i++) {
      const daysAgo = Math.floor(Math.random() * 30);
      const date = new Date();
      date.setDate(date.getDate() - daysAgo);
      const imgdate = date.toISOString().slice(0, 10).replace(/-/g, '');
      const timestamp = imgdate + '120000';
      let metadata = '{}';
      if (i % 3 === 0) {
        metadata = JSON.stringify({ data: { GPSLatitude: 10.0 + Math.random() * 2, GPSLongitude: 76.0 + Math.random() * 2, Model: 'Canon EOS 5D Mark IV' } });
      } else if (i % 2 === 0) {
        metadata = JSON.stringify({ data: { Model: ['Nikon D850', 'Sony Alpha 7R IV'][Math.floor(Math.random() * 2)] } });
      }
      mockRows.push([ i, categoryName, `Sample_Image_${i}.jpg`, imgdate, timestamp, Math.floor(Math.random() * 10000000) + 1000000, metadata, ['TestUser1', 'TestUser2', 'TestUser3'][Math.floor(Math.random() * 3)] ]);
    }
    return { success: true, rows: mockRows, count: mockRows.length, timestamp: new Date().toISOString(), category: categoryName, cached: false, mock_data: true };
  };

  return { loading, error, fetchCategories, fetchDashboardData, validateCategory };
}
