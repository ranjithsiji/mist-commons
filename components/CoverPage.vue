<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
      <div class="absolute top-10 left-10 w-20 h-20 bg-wikimedia-blue rounded-full"></div>
      <div class="absolute top-32 right-20 w-16 h-16 bg-wikimedia-green rounded-full"></div>
      <div class="absolute bottom-20 left-20 w-24 h-24 bg-wikimedia-orange rounded-full"></div>
      <div class="absolute bottom-40 right-10 w-12 h-12 bg-wikimedia-red rounded-full"></div>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
      <div class="max-w-7xl w-full">
        <!-- Header -->
        <div class="text-center mb-16 animate-fade-in">
          <div class="mb-6">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-wikimedia-blue to-wikimedia-green rounded-2xl mb-6 animate-bounce-gentle">
              <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
              </svg>
            </div>
          </div>
          <h1 class="text-5xl md:text-7xl font-bold bg-gradient-to-r from-wikimedia-blue via-wikimedia-green to-wikimedia-blue bg-clip-text text-transparent mb-6 leading-tight">
            Wikimedia Commons
          </h1>
          <h2 class="text-3xl md:text-4xl font-semibold text-gray-700 mb-4">
            Analytics Dashboard
          </h2>
          <p class="text-xl md:text-2xl text-gray-600 mb-4 max-w-3xl mx-auto leading-relaxed">
            Explore contest statistics, contributor insights, and media analytics
          </p>
          <p class="text-base text-gray-500 max-w-2xl mx-auto">
            Select a category below to dive into detailed visualizations and data insights
          </p>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-6 rounded-lg mb-8 max-w-3xl mx-auto shadow-lg animate-slide-up">
          <div class="flex items-center">
            <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium">{{ error }}</span>
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

        <!-- Categories Grid -->
        <div v-else-if="categories.length > 0" class="space-y-8 animate-slide-up">
          <div class="text-center mb-12">
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Available Contest Categories</h3>
            <p class="text-gray-600">{{ categories.length }} contest{{ categories.length !== 1 ? 's' : '' }} available for analysis</p>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            <div
              v-for="(category, index) in categories"
              :key="category.id"
              @click="$emit('select', category)"
              class="group bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 cursor-pointer transform hover:-translate-y-2 hover:scale-105 overflow-hidden border border-gray-100"
              :style="{ animationDelay: `${index * 100}ms` }"
            >
              <div 
                class="h-40 bg-gradient-to-br flex items-center justify-center relative overflow-hidden group-hover:scale-110 transition-transform duration-500"
                :style="{ background: `linear-gradient(135deg, ${category.color1 || '#0645ad'} 0%, ${category.color2 || '#00af89'} 100%)` }"
              >
                <div class="absolute inset-0 bg-white/10 group-hover:bg-white/20 transition-colors duration-300"></div>
                <span class="text-7xl relative z-10 transform group-hover:scale-110 transition-transform duration-300">{{ category.icon || '📊' }}</span>
              </div>
              <div class="p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-wikimedia-blue transition-colors duration-300">
                  {{ category.name }}
                </h3>
                <p class="text-gray-600 text-base mb-6 leading-relaxed line-clamp-3">
                  {{ category.description }}
                </p>
                <div class="flex items-center justify-between">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                    {{ category.year || 'Contest' }}
                  </span>
                  <div class="flex items-center text-wikimedia-blue font-semibold group-hover:translate-x-2 transition-transform duration-300">
                    <span class="mr-2">View Analytics</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
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
          <p class="text-gray-500">No contest categories found. Please check your configuration.</p>
        </div>

        <!-- Footer -->
        <div class="text-center mt-20 animate-fade-in">
          <div class="flex items-center justify-center space-x-6 mb-6">
            <div class="flex items-center space-x-2 text-gray-600">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
              <span class="font-medium">Open Source</span>
            </div>
            <div class="flex items-center space-x-2 text-gray-600">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              <span class="font-medium">Community Driven</span>
            </div>
            <div class="flex items-center space-x-2 text-gray-600">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
              </svg>
              <span class="font-medium">Global Impact</span>
            </div>
          </div>
          <p class="text-gray-500 text-sm mb-2">
            Powered by <span class="font-semibold text-wikimedia-blue">Wikimedia Commons</span> • 
            Built with <span class="font-semibold text-wikimedia-green">Vue.js</span> & 
            <span class="font-semibold text-wikimedia-purple">TailwindCSS</span>
          </p>
          <p class="text-gray-400 text-xs">
            Analyzing media contributions from the world's largest free media repository
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
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

defineEmits(['select']);
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>