<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-green-50 dark:from-gray-950 dark:to-gray-900">
    <header class="sticky top-0 z-30 backdrop-blur bg-white/80 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-800">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a href="/" class="text-xl font-bold text-gray-900 dark:text-gray-100">Mist Commons</a>
          <nav class="hidden md:flex items-center gap-4 ml-6">
            <a href="/" class="text-gray-700 dark:text-gray-300 hover:text-blue-600">Home</a>
            <a href="/about" class="text-gray-700 dark:text-gray-300 hover:text-blue-600">About</a>
          </nav>
        </div>
        <div class="flex items-center gap-2">
          <button @click="toggleTheme" class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white/80 dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition" title="Toggle dark mode">
            <span v-if="!isDark">🌙</span>
            <span v-else>☀️</span>
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useTheme } from './composables/useTheme';
const { isDark, toggleTheme } = useTheme();
</script>
