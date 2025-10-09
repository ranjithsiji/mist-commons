<template>
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-6xl w-full">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-5xl md:text-6xl font-bold text-gray-800 mb-4">
          📊 Wikimedia Commons Analytics
        </h1>
        <p class="text-xl text-gray-600 mb-2">
          Contest Dashboard & Statistics
        </p>
        <p class="text-sm text-gray-500">
          Select a category to view detailed analytics and visualizations
        </p>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 max-w-2xl mx-auto">
        {{ error }}
      </div>

      <!-- Loading Categories -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">Loading categories...</p>
      </div>

      <!-- Categories Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="category in categories"
          :key="category.id"
          @click="$emit('select', category)"
          class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1 overflow-hidden"
        >
          <div 
            class="h-32 bg-gradient-to-br flex items-center justify-center"
            :style="{ background: `linear-gradient(135deg, ${category.color1} 0%, ${category.color2} 100%)` }"
          >
            <span class="text-6xl">{{ category.icon }}</span>
          </div>
          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ category.name }}</h3>
            <p class="text-gray-600 text-sm mb-4">{{ category.description }}</p>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">{{ category.year }}</span>
              <span class="text-blue-600 font-semibold">View Analytics →</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-12 text-gray-500 text-sm">
        <p>Powered by Wikimedia Commons | Built with Vue.js</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  categories: {
    type: Array,
    required: true
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