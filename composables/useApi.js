import { ref } from 'vue';

const CATEGORIES_API_URL = '/api/categories.php';
const DASHBOARD_API_URL = '/api/dashboard.php';

export function useApi() {
  const loading = ref(false);
  const error = ref('');

  const fetchCategories = async () => {
    loading.value = true;
    error.value = '';
    
    try {
      const response = await fetch(CATEGORIES_API_URL);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const jsonData = await response.json();
      return jsonData.categories || [];
    } catch (err) {
      error.value = 'Error loading categories: ' + err.message;
      console.error('Fetch error:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchDashboardData = async (categoryName) => {
    loading.value = true;
    error.value = '';
    
    try {
      const response = await fetch(`${DASHBOARD_API_URL}?category=${encodeURIComponent(categoryName)}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const jsonData = await response.json();
      
      if (!jsonData.success) {
        throw new Error(jsonData.error || 'Failed to fetch data');
      }
      
      return jsonData;
    } catch (err) {
      error.value = 'Error loading data: ' + err.message;
      console.error('Fetch error:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    fetchCategories,
    fetchDashboardData
  };
}