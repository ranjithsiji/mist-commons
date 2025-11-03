<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-green-50 flex flex-col">
    <!-- Cover Page -->
    <CoverPage
      v-if="!selectedCategory"
      :categories="categories"
      :loading="loadingCategories"
      :error="error"
      @select="selectCategory"
    />

    <!-- Dashboard Page -->
    <div v-else class="flex-1 bg-gradient-to-br from-blue-50 to-green-50 flex flex-col">
      <!-- Header with Navigation -->
      <header class="bg-white/90 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
        <!-- existing header content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center h-16">
            <!-- Logo and Title -->
            <div class="flex items-center space-x-4">
              <button
                @click="backToHome"
                class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-wikimedia-blue to-wikimedia-green rounded-lg hover:shadow-lg transition-all duration-200"
                title="Back to Categories"
              >
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
              </button>
              <div>
                <h1 class="text-xl font-bold text-gray-900">{{ selectedCategory.name }}</h1>
                <p class="text-sm text-gray-500 hidden sm:block">Analytics Dashboard</p>
              </div>
            </div>
            
            <!-- Top Navigation Menu and refresh (unchanged) -->
            <div class="flex items-center space-x-4">
              <nav class="hidden md:flex items-center space-x-6">
                <a href="#" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Overview</a>
                <a href="#" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Statistics</a>
                <a href="#" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Contributors</a>
                <a href="https://commons.wikimedia.org" target="_blank" rel="noopener noreferrer" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Commons</a>
              </nav>
              
              <button
                @click="refreshData"
                :disabled="loading"
                class="px-4 py-2 bg-gradient-to-r from-wikimedia-blue to-wikimedia-green text-white rounded-lg hover:shadow-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center text-sm font-medium"
              >
                <svg 
                  v-if="loading" 
                  class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" 
                  xmlns="http://www.w3.org/2000/svg" 
                  fill="none" 
                  viewBox="0 0 24 24"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{ loading ? 'Refreshing...' : 'Refresh' }}
              </button>
              
              <div class="md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-700 hover:text-wikimedia-blue p-2">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Mobile menu -->
          <div v-if="mobileMenuOpen" class="md:hidden border-t border-gray-200 py-4">
            <nav class="flex flex-col space-y-2">
              <a href="#" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Overview</a>
              <a href="#" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Statistics</a>
              <a href="#" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Contributors</a>
              <a href="https://commons.wikimedia.org" target="_blank" rel="noopener noreferrer" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Commons</a>
            </nav>
          </div>
        </div>
      </header>

      <!-- Main Dashboard Content -->
      <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <!-- existing main content -->
        <div class="mb-8 animate-fade-in">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
            {{ selectedCategory.name }} Analytics
          </h2>
          <p class="text-lg text-gray-600 leading-relaxed max-w-4xl">
            {{ selectedCategory.description }}
          </p>
        </div>

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

        <div v-if="loading && !dashboardData" class="text-center py-24 animate-fade-in">
          <div class="relative inline-block mb-8">
            <div class="w-20 h-20 border-4 border-wikimedia-blue/20 border-t-wikimedia-blue rounded-full animate-spin"></div>
            <div class="absolute inset-0 w-20 h-20 border-4 border-transparent border-r-wikimedia-green rounded-full animate-spin" style="animation-delay: -0.5s; animation-direction: reverse;"></div>
          </div>
          <h3 class="text-2xl font-semibold text-gray-700 mb-4">Loading Dashboard Data</h3>
          <p class="text-gray-600 mb-2">Fetching analytics from Wikimedia Commons...</p>
          <div class="flex justify-center items-center space-x-2 text-sm text-gray-500">
            <div class="w-2 h-2 bg-wikimedia-blue rounded-full animate-bounce"></div>
            <div class="w-2 h-2 bg-wikimedia-green rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
            <div class="w-2 h-2 bg-wikimedia-orange rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
          </div>
        </div>

        <div v-if="dashboardData && stats" class="space-y-8 animate-slide-up">
          <StatsCards :stats="stats" />
          <PhotoMap v-if="geoData.length > 0" :geo-data="geoData" />
          <DashboardCharts :data="dashboardData" />
          <ContributorsTable :user-contributions="dashboardData.userContributions" />
        </div>
      </main>
    </div>

    <!-- Global Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import CoverPage from './components/CoverPage.vue';
import StatsCards from './components/StatsCards.vue';
import PhotoMap from './components/PhotoMap.vue';
import DashboardCharts from './components/DashboardCharts.vue';
import ContributorsTable from './components/ContributorsTable.vue';
import Footer from './components/Footer.vue';
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
const mobileMenuOpen = ref(false);

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
  mobileMenuOpen.value = false;
  updateURL(category.slug);
  fetchData(category.categoryName);
};

const backToHome = () => {
  selectedCategory.value = null;
  dashboardData.value = null;
  stats.value = null;
  geoData.value = [];
  mobileMenuOpen.value = false;
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
