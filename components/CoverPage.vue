<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50">
    <!-- Header with Navigation -->
    <header class="bg-white/90 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo and Title -->
          <div class="flex items-center space-x-4">
            <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-wikimedia-blue to-wikimedia-green rounded-lg">
              <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
              </svg>
            </div>
            <div>
              <h1 class="text-xl font-bold text-gray-900">MIST : Analytics</h1>
              <p class="text-sm text-gray-500 hidden sm:block">Media Statistics and Analytics Dashboard for Campaigns</p>
            </div>
          </div>
          
          <!-- Top Navigation Menu -->
          <nav class="hidden md:flex items-center space-x-8">
            <a href="#" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Home</a>
            <a href="https://w.wiki/t9" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Wikimedians of Kerala</a>
            <a href="https://gitlab.wikimedia.org/toolforge-repos/mist-tool" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Gitlab</a>
            <a href="https://commons.wikimedia.org" target="_blank" rel="noopener noreferrer" class="text-gray-700 hover:text-wikimedia-blue font-medium transition-colors duration-200">Wikimedia Commons</a>
          </nav>
          
          <!-- Mobile menu button -->
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
            <a href="#" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Home</a>
            <a href="https://w.wiki/t9" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Wikimedians of Kerala</a>
            <a href="https://gitlab.wikimedia.org/toolforge-repos/mist-tool" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Gitlab</a>
            <a href="https://commons.wikimedia.org" target="_blank" rel="noopener noreferrer" class="text-gray-700 hover:text-wikimedia-blue font-medium py-2 transition-colors duration-200">Wikimedia Commons</a>
          </nav>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Hero Section -->
      <div class="text-center mb-4 animate-fade-in">
        <!-- Logo and Main Heading Container -->
        <div class="flex flex-col md:flex-row items-center justify-center gap-4 md:gap-6 mb-4">
          <!-- MIST Logo -->
          <div class="flex-shrink-0">
            <div class="w-20 h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 transition-transform duration-300 hover:scale-105">
              <img src="/mistlogo.svg" alt="MIST Logo" class="w-full h-full object-contain drop-shadow-lg" />
            </div>
          </div>
          
          <!-- Main Heading -->
          <div class="flex-1">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-wikimedia-blue via-wikimedia-green to-wikimedia-blue bg-clip-text text-transparent leading-tight">
              Wikimedia Commons Analytics
            </h2>
          </div>
        </div>
        
        <p class="text-lg md:text-xl text-gray-600 mb-1 max-w-3xl mx-auto leading-relaxed">
         View Statistics of a Wikimedia Commons photography campaign
        </p>
      </div>
      <!-- Custom Category Search Section -->
      <div class="mb-16 animate-fade-in">
        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg border border-gray-100 p-6 max-w-3xl mx-auto">
          <div class="text-center mb-4">
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Category of Campaign</h3>
            <p class="text-gray-600">Search for any Wikimedia Commons category of a campaign to analyze</p>
          </div>
          
          <div class="relative">
            <div class="flex flex-col md:flex-row gap-3">
              <div class="flex-1 relative">
                <input
                  ref="categoryInput"
                  v-model="customCategoryQuery"
                  @input="handleCategoryInput"
                  @keydown.down.prevent="navigateSuggestion(1)"
                  @keydown.up.prevent="navigateSuggestion(-1)"
                  @keydown.enter.prevent="selectCurrentSuggestion"
                  @keydown.escape="hideSuggestions"
                  @focus="showSuggestions = customCategoryQuery.length >= 3"
                  type="text"
                  placeholder="Type category name:"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-wikimedia-blue focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-500"
                />
                
                <!-- Loading indicator in input -->
                <div v-if="searchingCategories" class="absolute right-4 top-1/2 transform -translate-y-1/2">
                  <div class="w-5 h-5 border-2 border-wikimedia-blue/20 border-t-wikimedia-blue rounded-full animate-spin"></div>
                </div>
                
                <!-- Autocomplete dropdown -->
                <div
                  v-if="showSuggestions && categorySuggestions.length > 0"
                  class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                >
                  <div
                    v-for="(suggestion, index) in categorySuggestions"
                    :key="suggestion.title"
                    @click="selectSuggestion(suggestion)"
                    @mouseenter="selectedSuggestionIndex = index"
                    :class="[
                      'px-4 py-3 cursor-pointer transition-colors duration-150 border-b border-gray-100 last:border-b-0',
                      selectedSuggestionIndex === index
                        ? 'bg-wikimedia-blue text-white'
                        : 'hover:bg-gray-50 text-gray-900'
                    ]"
                  >
                    <div class="font-medium">{{ suggestion.title.replace('Category:', '') }}</div>
                    <div 
                      v-if="suggestion.categoryinfo && suggestion.categoryinfo.pages"
                      :class="[
                        'text-sm mt-1',
                        selectedSuggestionIndex === index ? 'text-blue-100' : 'text-gray-500'
                      ]"
                    >
                      {{ suggestion.categoryinfo.pages }} pages
                    </div>
                    <!-- Commons Link -->
                    <div 
                      :class="[
                        'text-xs mt-1 flex items-center',
                        selectedSuggestionIndex === index ? 'text-blue-200' : 'text-gray-400'
                      ]"
                    >
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                      </svg>
                      commons.wikimedia.org/wiki/{{ suggestion.title.replace(' ', '_') }}
                    </div>
                  </div>
                </div>
              </div>
              
              <button
                @click="analyzeCustomCategory"
                :disabled="!customCategoryQuery.trim() || searchingCategories"
                class="px-6 py-3 bg-gradient-to-r from-wikimedia-blue to-wikimedia-green text-white rounded-lg hover:shadow-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center font-medium"
              >
                <svg v-if="searchingCategories" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                {{ searchingCategories ? 'Searching...' : 'View Statistics' }}
              </button>
            </div>
            
            <!-- Current category Commons link -->
            <div v-if="currentCommonsUrl" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-blue-700">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                  </svg>
                 

                <a 
                  :href="currentCommonsUrl" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="text-blue-600 hover:text-blue-800 text-sm font-medium hover:underline transition-colors duration-200"
                >
                  View Category on Wikimedia Commons →
                </a>
                </div>
              </div>
              <div class="mt-1 text-xs text-blue-600 font-mono break-all">
               URL :  {{ currentCommonsUrl }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error || categorySearchError" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-6 rounded-lg mb-8 max-w-3xl mx-auto shadow-lg animate-slide-up">
        <div class="flex items-center">
          <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span class="font-medium">{{ error || categorySearchError }}</span>
        </div>
      </div>

      <!-- Loading Categories -->
      <div v-if="loading" class="text-center py-20 animate-fade-in">
        <div class="relative inline-block">
          <div class="w-16 h-16 border-4 border-wikimedia-blue/20 border-t-wikimedia-blue rounded-full animate-spin"></div>
          <div class="absolute inset-0 w-16 h-16 border-4 border-transparent border-r-wikimedia-green rounded-full animate-spin" style="animation-delay: -0.5s; animation-direction: reverse;"></div>
        </div>
        <p class="mt-6 text-xl text-gray-600 font-medium">Loading categories...</p>
        <p class="mt-2 text-gray-500">Fetching contest data from Wikimedia Commons</p>
      </div>

      <!-- Categories Section -->
      <div v-else-if="categories.length > 0" class="animate-slide-up">
        <div class="text-center mb-12">
          <h3 class="text-3xl font-bold text-gray-800 mb-3">Featured Contest Categories</h3>
          <p class="text-gray-600 text-lg">{{ categories.length }} contest{{ categories.length !== 1 ? 's' : '' }} available for analysis</p>
        </div>
        
        <!-- Horizontal Cards -->
        <div class="space-y-6">
          <div
            v-for="(category, index) in categories"
            :key="category.id"
            @click="$emit('select', category)"
            class="group bg-white/80 backdrop-blur-sm rounded-xl shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1 border border-gray-100 overflow-hidden"
            :style="{ animationDelay: `${index * 100}ms` }"
          >
            <div class="flex items-center p-6">
              <!-- Icon Section -->
              <div 
                class="flex items-center justify-center w-16 h-16 rounded-lg mr-6 flex-shrink-0 group-hover:scale-110 transition-transform duration-300"
                :style="{ background: `linear-gradient(135deg, ${category.color1 || '#0645ad'} 0%, ${category.color2 || '#00af89'} 100%)` }"
              >
                <span class="text-2xl">{{ category.icon || '📊' }}</span>
              </div>
              
              <!-- Content Section -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-wikimedia-blue transition-colors duration-300">
                      {{ category.name }}
                    </h3>
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2 leading-relaxed">
                      {{ category.description }}
                    </p>
                    <div class="flex items-center space-x-3">
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                        {{ category.year || 'Contest' }}
                      </span>
                      <div class="flex items-center text-wikimedia-blue font-medium text-sm group-hover:translate-x-1 transition-transform duration-300">
                        <span class="mr-1">View Analytics</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading" class="text-center py-20 animate-fade-in">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Categories Available</h3>
        <p class="text-gray-500">No contest categories found. Use the custom search above to find categories.</p>
      </div>

      <!-- Footer -->
      <footer class="text-center mt-20 pt-12 border-t border-gray-200 animate-fade-in">
        <div class="flex items-center justify-center space-x-8 mb-6">
          <div class="flex items-center space-x-2 text-gray-600">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <a href="https://wikitech.wikimedia.org/wiki/Portal:Toolforge" target="_blank">
            <span class="font-medium">Wikimedia Toolforge</span>
            </a>
          </div>
          <div class="flex items-center space-x-2 text-gray-600">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <a href="https://hay.toolforge.org/directory/" target="_blank">
            <span class="font-medium">Tools Directory</span>
            </a>
          </div>
          <div class="flex items-center space-x-2 text-gray-600">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
            </svg>
            <a href="https://w.wiki/t9" target="_blank">
            <span class="font-medium">Wikimedians of Kerala</span>
            </a>
          </div>
        </div>
        <p class="text-gray-500 text-sm mb-2">
          Powered by <a href="https://commons.wikimedia.org/" target="_blank"></a><span class="font-semibold text-wikimedia-blue">Wikimedia Commons API</span> • 
          Built with <a href="https://vuejs.org/" target="_blank"><span class="font-semibold text-wikimedia-green">Vue.js</span></a> & 
        <a href="https://tailwindcss.com/" target="_blank"><span class="font-semibold text-wikimedia-purple">TailwindCSS</span></a>
        </p>
        <p class="text-gray-400 text-xs">
          Analyzing media contributions from the world's largest free media repository
        </p>
      </footer>
    </main>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted, computed, watch } from 'vue';

const props = defineProps({
  categories: {
    type: Array,
    required: true,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['select', 'custom']);

const mobileMenuOpen = ref(false);

// Custom category search
const customCategoryQuery = ref('');
const categorySuggestions = ref([]);
const showSuggestions = ref(false);
const searchingCategories = ref(false);
const selectedSuggestionIndex = ref(-1);
const categorySearchError = ref('');
const categoryInput = ref(null);

let searchTimeout = null;

// Computed property for current Commons URL
const currentCommonsUrl = computed(() => {
  if (!customCategoryQuery.value.trim()) return null;
  
  let categoryName = customCategoryQuery.value.trim();
  // Add "Category:" prefix if not present
  if (!categoryName.toLowerCase().startsWith('category:')) {
    categoryName = `Category:${categoryName}`;
  }
  
  const encodedName = encodeURIComponent(categoryName.replace(/ /g, '_'));
  return `https://commons.wikimedia.org/wiki/${encodedName}`;
});

// Watch for URL changes to populate the input field
watch(() => {
  const params = new URLSearchParams(window.location.search);
  return params.get('category');
}, (categorySlug) => {
  if (categorySlug && !customCategoryQuery.value) {
    const decodedSlug = decodeURIComponent(categorySlug);
    customCategoryQuery.value = decodedSlug.replace(/_/g, ' ');
  }
}, { immediate: true });

const handleCategoryInput = () => {
  const query = customCategoryQuery.value.trim();
  
  if (query.length < 3) {
    showSuggestions.value = false;
    categorySuggestions.value = [];
    return;
  }

  // Clear previous timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  
  // Debounce search
  searchTimeout = setTimeout(() => {
    searchCategories(query);
  }, 300);
};

const searchCategories = async (query) => {
  if (!query || query.length < 3) return;
  
  searchingCategories.value = true;
  categorySearchError.value = '';
  
  try {
    // Add "Category:" prefix if not present
    let searchQuery = query;
    if (!searchQuery.toLowerCase().startsWith('category:')) {
      searchQuery = `Category:${query}`;
    }
    
    // Use MediaWiki API to search for categories
    const apiUrl = 'https://commons.wikimedia.org/w/api.php';
    const params = new URLSearchParams({
      action: 'query',
      format: 'json',
      origin: '*',
      list: 'search',
      srnamespace: '14', // Category namespace
      srsearch: searchQuery,
      srlimit: '10',
      srprop: 'size|wordcount|timestamp',
      formatversion: '2'
    });
    
    const response = await fetch(`${apiUrl}?${params}`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    
    if (data.error) {
      throw new Error(data.error.info || 'API Error');
    }
    
    // Get category info for the results
    if (data.query && data.query.search && data.query.search.length > 0) {
      const categoryTitles = data.query.search.map(item => item.title);
      await getCategoryInfo(categoryTitles);
    } else {
      categorySuggestions.value = [];
      showSuggestions.value = false;
    }
    
  } catch (error) {
    console.error('Category search error:', error);
    categorySearchError.value = `Failed to search categories: ${error.message}`;
    categorySuggestions.value = [];
    showSuggestions.value = false;
  } finally {
    searchingCategories.value = false;
  }
};

const getCategoryInfo = async (categoryTitles) => {
  try {
    const apiUrl = 'https://commons.wikimedia.org/w/api.php';
    const params = new URLSearchParams({
      action: 'query',
      format: 'json',
      origin: '*',
      titles: categoryTitles.join('|'),
      prop: 'categoryinfo',
      formatversion: '2'
    });
    
    const response = await fetch(`${apiUrl}?${params}`);
    const data = await response.json();
    
    if (data.query && data.query.pages) {
      categorySuggestions.value = data.query.pages.filter(page => !page.missing);
      showSuggestions.value = categorySuggestions.value.length > 0;
      selectedSuggestionIndex.value = -1;
    }
  } catch (error) {
    console.error('Category info error:', error);
  }
};

const navigateSuggestion = (direction) => {
  if (!showSuggestions.value || categorySuggestions.value.length === 0) return;
  
  selectedSuggestionIndex.value += direction;
  
  if (selectedSuggestionIndex.value < -1) {
    selectedSuggestionIndex.value = categorySuggestions.value.length - 1;
  } else if (selectedSuggestionIndex.value >= categorySuggestions.value.length) {
    selectedSuggestionIndex.value = -1;
  }
};

const selectCurrentSuggestion = () => {
  if (selectedSuggestionIndex.value >= 0 && selectedSuggestionIndex.value < categorySuggestions.value.length) {
    selectSuggestion(categorySuggestions.value[selectedSuggestionIndex.value]);
  } else if (customCategoryQuery.value.trim()) {
    analyzeCustomCategory();
  }
};

const selectSuggestion = (suggestion) => {
  customCategoryQuery.value = suggestion.title;
  showSuggestions.value = false;
  categorySuggestions.value = [];
  selectedSuggestionIndex.value = -1;
  
  nextTick(() => {
    if (categoryInput.value) {
      categoryInput.value.focus();
    }
  });
};

const hideSuggestions = () => {
  showSuggestions.value = false;
  selectedSuggestionIndex.value = -1;
};

const analyzeCustomCategory = () => {
  const query = customCategoryQuery.value.trim();
  if (!query) return;
  
  // Create a custom category object
  const customCategory = {
    id: `custom-${Date.now()}`,
    name: query.replace(/\ /g, '_').replace('Category:', ''),
    slug: query.replace(/\ /g, '_'),
    description: `Custom analysis for ${query}`,
    categoryName: query.startsWith('Category:') ? query.substring(9) : query,
    icon: '🔍',
    year: 'Custom',
    color1: '#8B5CF6',
    color2: '#7C3AED',
    isCustom: true
  };
  console.log(customCategory);
  
  hideSuggestions();
  emit('custom', customCategory);
};

// Handle click outside to hide suggestions
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    hideSuggestions();
  }
};

// Add event listener when component mounts
onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

// Remove event listener when component unmounts
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>