<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-green-50">
    <!-- Cover Page -->
    <CoverPage
      v-if="!selectedCategory"
      :categories="categories"
      :loading="loadingCategories"
      :error="error"
      @select="selectCategory"
    />

    <!-- Dashboard Page -->
    <div v-else class="p-4 md:p-8">
      <div class="max-w-7xl mx-auto">
        <!-- Header with Back Button -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <button
              @click="backToHome"
              class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors inline-flex items-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Back to Categories
            </button>
            <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ selectedCategory.name }}</h1>
            <p class="text-gray-600">{{ selectedCategory.description }}</p>
          </div>
          <button
            @click="refreshData"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors h-fit"
            :disabled="loading"
          >
            {{ loading ? 'Loading...' : 'Refresh Data' }}
          </button>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {{ error }}
        </div>

        <!-- Loading State -->
        <div v-if="loading && !dashboardData" class="text-center py-20">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          <p class="mt-4 text-gray-600">Loading dashboard data...</p>
        </div>

        <!-- Dashboard Content -->
        <div v-if="dashboardData && stats">
          <!-- Stats Cards -->
          <StatsCards :stats="stats" />

          <!-- Map Section -->
          <PhotoMap v-if="geoData.length > 0" :geo-data="geoData" />

          <!-- Charts -->
          <DashboardCharts :data="dashboardData" />

          <!-- User Contributions Table -->
          <ContributorsTable :user-contributions="dashboardData.userContributions" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import CoverPage from './components/CoverPage.vue';
import StatsCards from './components/StatsCards.vue';
import PhotoMap from './components/PhotoMap.vue';
import DashboardCharts from './components/DashboardCharts.vue';
import ContributorsTable from './components/ContributorsTable.vue';
import { useApi } from './composables/useApi';
import { useDataProcessor } from './composables/useData';

const { loading, error, fetchCategories, fetchDashboardData } = useApi();
const { processData } = useDataProcessor();

const categories = ref([]);
const selectedCategory = ref(null);
const dashboardData = ref(null);
const stats = ref(null);
const geoData = ref([]);
const loadingCategories = ref(false);

const loadCategories = async () => {
  loadingCategories.value = true;
  try {
    const cats = await fetchCategories();
    categories.value = cats;
  } catch (err) {
    console.error('Error loading categories:', err);
  } finally {
    loadingCategories.value = false;
  }
};

const selectCategory = (category) => {
  selectedCategory.value = category;
  updateURL(category.slug);
  fetchData(category.categoryName);
};

const backToHome = () => {
  selectedCategory.value = null;
  dashboardData.value = null;
  stats.value = null;
  geoData.value = [];
  updateURL('');
};

const updateURL = (slug) => {
  const newURL = slug ? `?category=${slug}` : window.location.pathname;
  window.history.pushState({}, '', newURL);
};

const loadFromURL = () => {
  const params = new URLSearchParams(window.location.search);
  const categorySlug = params.get('category');
  
  if (categorySlug && categories.value.length > 0) {
    const category = categories.value.find(cat => cat.slug === categorySlug);
    if (category) {
      selectCategory(category);
    }
  }
};

const fetchData = async (categoryName) => {
  try {
    const jsonData = await fetchDashboardData(categoryName);
    const processed = processData(jsonData);
    
    stats.value = processed.stats;
    dashboardData.value = processed.data;
    geoData.value = processed.geoData;
  } catch (err) {
    console.error('Error fetching data:', err);
  }
};

const refreshData = () => {
  if (selectedCategory.value) {
    fetchData(selectedCategory.value.categoryName);
  }
};

onMounted(() => {
  loadCategories();
});

watch(categories, () => {
  if (categories.value.length > 0) {
    loadFromURL();
  }
});
</script>