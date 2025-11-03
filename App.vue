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
    <div v-else class="min-h-screen bg-gradient-to-br from-blue-50 to-green-50">
      <div class="p-4 md:p-8">
        <div class="max-w-7xl mx-auto">
          <!-- Header with Back Button -->
          <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 animate-fade-in">
            <div>
              <button
                @click="backToHome"
                class="mb-4 px-6 py-3 bg-white/80 backdrop-blur-sm text-gray-700 rounded-xl hover:bg-white hover:shadow-lg transition-all duration-300 inline-flex items-center border border-gray-200 hover:border-wikimedia-blue hover:text-wikimedia-blue"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Categories
              </button>
              <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-wikimedia-blue to-wikimedia-green bg-clip-text text-transparent mb-4">
                {{ selectedCategory.name }}
              </h1>
              <p class="text-lg text-gray-600 leading-relaxed max-w-3xl">
                {{ selectedCategory.description }}
              </p>
            </div>
            <button
              @click="refreshData"
              :disabled="loading"
              class="px-6 py-3 bg-gradient-to-r from-wikimedia-blue to-wikimedia-green text-white rounded-xl hover:shadow-lg transition-all duration-300 h-fit disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              <svg 
                v-if="loading" 
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" 
                xmlns="http://www.w3.org/2000/svg" 
                fill="none" 
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              {{ loading ? 'Refreshing...' : 'Refresh Data' }}
            </button>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-6 rounded-xl mb-8 shadow-lg animate-slide-up">
            <div class="flex items-center">
              <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
              <div>
                <h3 class="font-medium">Error Loading Data</h3>
                <p class="text-sm mt-1">{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading && !dashboardData" class="text-center py-24 animate-fade-in">
            <div class="relative inline-block mb-8">
              <div class="w-20 h-20 border-4 border-wikimedia-blue/20 border-t-wikimedia-blue rounded-full animate-spin"></div>
              <div class="absolute inset-0 w-20 h-20 border-4 border-transparent border-r-wikimedia-green rounded-full animate-spin" style="animation-delay: -0.5s; animation-direction: reverse;"></div>
            </div>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Loading Dashboard Data</h2>
            <p class="text-gray-600 mb-2">Fetching analytics from Wikimedia Commons...</p>
            <div class="flex justify-center items-center space-x-2 text-sm text-gray-500">
              <div class="w-2 h-2 bg-wikimedia-blue rounded-full animate-bounce"></div>
              <div class="w-2 h-2 bg-wikimedia-green rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
              <div class="w-2 h-2 bg-wikimedia-orange rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
          </div>

          <!-- Dashboard Content -->
          <div v-if="dashboardData && stats" class="space-y-8 animate-slide-up">
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