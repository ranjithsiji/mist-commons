<template>
  <section>
    <h2 class="text-2xl font-bold mb-4">Team</h2>
    <p class="text-gray-600 dark:text-gray-300 mb-6">The people behind Mist Commons.</p>
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-6">
      <div v-for="m in members" :key="m.name" class="p-5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-4">
          <img :src="m.avatar" alt="" class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-700" />
          <div>
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ m.name }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ m.role }}</p>
          </div>
        </div>
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">{{ m.bio }}</p>
        <div class="mt-4 flex gap-3">
          <a v-if="m.github" :href="m.github" target="_blank" class="text-gray-600 dark:text-gray-300 hover:text-blue-600">GitHub</a>
          <a v-if="m.commons" :href="m.commons" target="_blank" class="text-gray-600 dark:text-gray-300 hover:text-blue-600">Commons</a>
          <a v-if="m.website" :href="m.website" target="_blank" class="text-gray-600 dark:text-gray-300 hover:text-blue-600">Website</a>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const members = [
  {
    name: 'Ranjith Siji',
    role: 'Project Lead / Engineering',
    bio: 'Designs, builds, and maintains Mist Commons and analytics queries.',
    avatar: 'https://avatars.githubusercontent.com/u/1093783?v=4',
    github: 'https://github.com/ranjithsiji',
    commons: 'https://commons.wikimedia.org/wiki/User:Ranjithsiji',
    website: ''
  },
];
</script>
