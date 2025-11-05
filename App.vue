<template>
  <div>
    <!-- Cover Page -->
    <CoverPage
      v-if="!selectedCategory"
      :categories="categories"
      :loading="loadingCategories"
      :error="error"
      @select="selectCategory"
      @custom="selectCustomCategory"
    />

    <!-- Dashboard Page -->
    <div v-else class="flex-1 bg-gradient-to-br from-blue-50 to-green-50 flex flex-col">
      <!-- Header with Navigation -->
      <header class="bg-white/90 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center h-16">
            <!-- Back + Title -->
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
                <h1 class="text-xl font-bold text-gray-900">{{ selectedCategory.displayName }}</h1>
                <p class="text-sm text-gray-500 hidden sm:block">
                  {{ selectedCategory.isCustom ? 'Custom Category Analytics' : 'Analytics Dashboard' }}
                </p>
              </div>
            </div>

            <!-- Anchored Nav -->
            <nav class="hidden md:flex items-center space-x-6">
              <a href="#overview" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Overview</a>
              <a href="#map" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Map</a>
              <a href="#activity" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Activity</a>
              <a href="#contributors" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Contributors</a>
            </nav>

            <div class="md:hidden">
              <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-700 hover:text-wikimedia-blue p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Mobile menu -->
          <div v-if="mobileMenuOpen" class="md:hidden border-t border-gray-200 py-4">
            <nav class="flex flex-col space-y-2">
              <a href="#overview" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Overview</a>
              <a href="#map" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Map</a>
              <a href="#activity" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Activity</a>
              <a href="#contributors" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Contributors</a>
            </nav>
          </div>
        </div>
      </header>

      <!-- Main Dashboard Content -->
      <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <AnchoredSections :dashboard-data="dashboardData" :stats="stats" :geo-data="geoData" />
      </main>
    </div>

    <!-- Global Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import CoverPage from './components/CoverPage.vue';
import Footer from './components/Footer.vue';
import AnchoredSections from './components/AnchoredSections.vue';
import { useApi } from './composables/useApi';
import { useDataProcessor } from './composables/useData';

const { loading, error, fetchCategories, fetchDashboardData, validateCategory } = useApi();
const { processData } = useDataProcessor();

const categories = ref([]);
const selectedCategory = ref(null);
const dashboardData = ref(null);
const stats = ref(null);
const geoData = ref([]);
const loadingCategories = ref(false);
const mobileMenuOpen = ref(false);
const categoryValidation = ref(null);
const urlCopied = ref(false);

// ... rest of existing App.vue script content remains unchanged
</script>
