<template>
  <footer class="bg-white border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-wikimedia-blue to-wikimedia-green flex items-center justify-center">
            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-900">Commons Analytics</p>
            <p class="text-xs text-gray-500">Wikimedia Commons Dashboard</p>
          </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
          <a href="#" class="text-gray-600 hover:text-wikimedia-blue">About</a>
          <a href="#" class="text-gray-600 hover:text-wikimedia-blue">Documentation</a>
          <a href="https://commons.wikimedia.org" target="_blank" rel="noopener noreferrer" class="text-gray-600 hover:text-wikimedia-blue">Commons</a>
          <a href="https://github.com/ranjithsiji/mist-commons" target="_blank" rel="noopener noreferrer" class="text-gray-600 hover:text-wikimedia-blue">GitHub</a>
        </div>
      </div>
      <div class="mt-6 pt-6 border-t border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <p class="text-xs text-gray-500">© {{ new Date().getFullYear() }} Commons Analytics. Built with Vue.js and TailwindCSS.</p>
        <div class="flex items-center gap-4 text-xs text-gray-500">
          <span>Open Source</span>
          <span>Community Driven</span>
          <span>Global Impact</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
</script>
