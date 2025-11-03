<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Detailed User Contributions</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files Uploaded</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Size (MB)</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(user, index) in userContributions" :key="index" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ index + 1 }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <a 
                :href="getUserPageUrl(user.name)" 
                target="_blank" 
                rel="noopener noreferrer"
                class="text-wikimedia-blue hover:text-wikimedia-green transition-colors duration-200 font-medium hover:underline"
                :title="`View ${user.name}'s user page on Wikimedia Commons`"
              >
                {{ user.name }}
              </a>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ user.files }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ user.sizeMB }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  userContributions: {
    type: Array,
    required: true
  }
});

/**
 * Generate Wikimedia Commons user page URL
 * @param {string} username - The username
 * @returns {string} - The full URL to the user's Commons page
 */
const getUserPageUrl = (username) => {
  // Handle special cases and encode the username properly
  const encodedUsername = encodeURIComponent(username.trim());
  return `https://commons.wikimedia.org/wiki/User:${encodedUsername}`;
};
</script>