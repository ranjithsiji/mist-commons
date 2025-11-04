<template>
  <div class="max-w-5xl mx-auto px-4 py-10 text-gray-800 dark:text-gray-100">
    <div class="rounded-2xl p-6 md:p-10 bg-white/80 dark:bg-gray-900/70 border border-gray-200 dark:border-gray-800 shadow-sm">
      <h1 class="text-3xl font-bold mb-4">About Mist Commons</h1>
      <p class="mb-4">
        Mist Commons is an analytics dashboard for Wikimedia Commons categories and photo contests. It connects to the Commons replica database to compute real statistics, contributors, and timelines.
      </p>
      <ul class="list-disc ml-6 space-y-2">
        <li>Live stats from commonswiki replica</li>
        <li>Geo support when GPS EXIF is available</li>
        <li>Charts for trends and distribution</li>
        <li>Fast caching and responsive UI</li>
      </ul>

      <div class="mt-8 grid md:grid-cols-2 gap-6">
        <div class="p-5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold mb-2">Data Sources</h2>
          <p>Wikimedia Commons replica database and Commons API.</p>
        </div>
        <div class="p-5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold mb-2">Tech Stack</h2>
          <p>Vue 3, Tailwind CSS, Chart.js, Leaflet, PHP, MySQL/MariaDB.</p>
        </div>
      </div>

      <div class="mt-8">
        <a href="https://commons.wikimedia.org" target="_blank" rel="noopener"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
          Visit Commons
        </a>
        <a href="https://github.com/ranjithsiji/mist-commons" target="_blank" rel="noopener"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 ml-3 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
          GitHub
        </a>
      </div>
    </div>

    <Team class="mt-10" />
  </div>
</template>

<script setup>
import Team from '../components/Team.vue';
</script>
