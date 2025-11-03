<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
      <h2 class="text-xl font-bold text-gray-800">Detailed User Contributions</h2>
      <div class="flex items-center gap-3">
        <input
          v-model.trim="query"
          type="search"
          placeholder="Search user…"
          class="w-56 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-wikimedia-blue/30 focus:border-wikimedia-blue"
        />
        <select v-model="sortKey" class="px-3 py-2 text-sm border border-gray-300 rounded-lg">
          <option value="files">Sort by Files</option>
          <option value="sizeMB">Sort by Size</option>
          <option value="name">Sort by Name</option>
        </select>
        <button @click="toggleDir" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
          {{ sortDir === 'desc' ? 'Desc' : 'Asc' }}
        </button>
      </div>
    </div>

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
          <tr v-for="(user, index) in visibleRows" :key="user.name" class="hover:bg-gray-50">
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
import { computed, ref } from 'vue';

const props = defineProps({
  userContributions: {
    type: Array,
    required: true
  }
});

const query = ref('');
const sortKey = ref('files');
const sortDir = ref('desc');

const toggleDir = () => {
  sortDir.value = sortDir.value === 'desc' ? 'asc' : 'desc';
};

const getUserPageUrl = (username) => {
  const encodedUsername = encodeURIComponent(username.trim());
  return `https://commons.wikimedia.org/wiki/User:${encodedUsername}`;
};

const visibleRows = computed(() => {
  const q = query.value.toLowerCase();
  let rows = (props.userContributions || []).filter(u => !q || (u.name || '').toLowerCase().includes(q));

  const key = sortKey.value;
  const dir = sortDir.value === 'desc' ? -1 : 1;

  rows = rows.slice().sort((a, b) => {
    let av = a[key];
    let bv = b[key];
    if (key === 'sizeMB') {
      av = parseFloat(av);
      bv = parseFloat(bv);
    }
    if (typeof av === 'string') av = av.toLowerCase();
    if (typeof bv === 'string') bv = bv.toLowerCase();
    if (av < bv) return -1 * dir;
    if (av > bv) return 1 * dir;
    return 0;
  });

  return rows;
});
</script>
