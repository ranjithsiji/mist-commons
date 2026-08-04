<template>
  <div class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Files by Contributor</h2>
        <p class="text-sm text-gray-500 mt-1">Browse every file a user uploaded to this category</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <input
          v-model.trim="userQuery"
          type="search"
          list="contributor-options"
          placeholder="Search contributor…"
          class="w-56 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-wikimedia-blue/30 focus:border-wikimedia-blue"
        />
        <datalist id="contributor-options">
          <option v-for="user in contributorNames" :key="user" :value="user" />
        </datalist>

        <select v-model="selectedUser" class="max-w-xs px-3 py-2 text-sm border border-gray-300 rounded-lg">
          <option v-for="user in matchingContributors" :key="user.name" :value="user.name">
            {{ user.name }} — {{ user.files }} file{{ user.files === 1 ? '' : 's' }}
          </option>
        </select>

        <select v-model="sortKey" class="px-3 py-2 text-sm border border-gray-300 rounded-lg">
          <option value="newest">Newest first</option>
          <option value="oldest">Oldest first</option>
          <option value="largest">Largest first</option>
          <option value="name">By filename</option>
        </select>

        <button
          @click="exportCSV"
          :disabled="!userFiles.length"
          class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Export CSV
        </button>
      </div>
    </div>

    <div v-if="!contributorNames.length" class="text-center py-12 text-gray-500">
      No contributors found for this category.
    </div>

    <div v-else-if="!matchingContributors.length" class="text-center py-12 text-gray-500">
      No contributor matches “{{ userQuery }}”.
    </div>

    <template v-else>
      <!-- Summary for the selected contributor -->
      <div v-if="selectedUser" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-xs text-gray-600 uppercase tracking-wide">Files</p>
          <p class="text-2xl font-bold text-blue-600">{{ userFiles.length }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-xs text-gray-600 uppercase tracking-wide">Active Days</p>
          <p class="text-2xl font-bold text-green-600">{{ summary.activeDays }}</p>
        </div>
        <div class="bg-orange-50 rounded-lg p-4">
          <p class="text-xs text-gray-600 uppercase tracking-wide">Total Size</p>
          <p class="text-2xl font-bold text-orange-600">{{ formatBytes(summary.totalSize) }}</p>
        </div>
        <div class="bg-cyan-50 rounded-lg p-4">
          <p class="text-xs text-gray-600 uppercase tracking-wide">Geotagged</p>
          <p class="text-2xl font-bold text-cyan-600">{{ summary.geotagged }}</p>
        </div>
      </div>

      <div v-if="selectedUser" class="mb-4 text-sm">
        <a
          :href="userPageUrl(selectedUser)"
          target="_blank"
          rel="noopener noreferrer"
          class="text-wikimedia-blue hover:text-wikimedia-green transition-colors duration-200"
        >
          View {{ selectedUser }}'s user page on Commons →
        </a>
      </div>

      <!-- Only a short preview inline; the full set opens in a popup -->
      <div class="gap-4 columns-2 sm:columns-3 lg:columns-4 xl:columns-5">
        <FileCard
          v-for="file in previewFiles"
          :key="file.filename"
          :file="file"
          :show-user="false"
        />
      </div>

      <div v-if="hasMore" class="mt-6 text-center">
        <button
          @click="galleryOpen = true"
          class="inline-flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:shadow transition-all duration-200"
        >
          <svg class="w-4 h-4 mr-2 text-wikimedia-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
          </svg>
          View all {{ userFiles.length }} files
        </button>
      </div>
    </template>

    <!-- Full contributor gallery -->
    <FileGalleryModal
      :open="galleryOpen"
      :title="`Files by ${selectedUser}`"
      :subtitle="gallerySubtitle"
      :files="userFiles"
      :show-user="false"
      @close="galleryOpen = false"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import FileCard from './FileCard.vue';
import FileGalleryModal from './FileGalleryModal.vue';

const props = defineProps({
  userContributions: { type: Array, required: true },
  filesByUser: { type: Object, required: true }
});

// Keep the inline block short; the rest lives in the popup
const PREVIEW_COUNT = 5;

const selectedUser = ref('');
const userQuery = ref('');
const sortKey = ref('newest');
const galleryOpen = ref(false);

const contributorNames = computed(() => (props.userContributions || []).map(u => u.name));

const matchingContributors = computed(() => {
  const q = userQuery.value.toLowerCase();
  if (!q) return props.userContributions || [];
  return (props.userContributions || []).filter(u => (u.name || '').toLowerCase().includes(q));
});

const userFiles = computed(() => {
  const files = props.filesByUser?.[selectedUser.value] || [];
  const sorted = [...files];
  switch (sortKey.value) {
    case 'oldest':
      return sorted.sort((a, b) => a.timestamp.localeCompare(b.timestamp));
    case 'largest':
      return sorted.sort((a, b) => b.size - a.size);
    case 'name':
      return sorted.sort((a, b) => a.title.localeCompare(b.title));
    default:
      return sorted.sort((a, b) => b.timestamp.localeCompare(a.timestamp));
  }
});

const previewFiles = computed(() => userFiles.value.slice(0, PREVIEW_COUNT));
const hasMore = computed(() => userFiles.value.length > PREVIEW_COUNT);

const gallerySubtitle = computed(() => {
  const { activeDays, totalSize } = summary.value;
  return `${activeDays} active day${activeDays === 1 ? '' : 's'} · ${formatBytes(totalSize)}`;
});

const summary = computed(() => {
  const files = userFiles.value;
  return {
    activeDays: new Set(files.map(f => f.date).filter(Boolean)).size,
    totalSize: files.reduce((sum, f) => sum + f.size, 0),
    geotagged: files.filter(f => f.lat !== null && f.lon !== null).length
  };
});

const userPageUrl = (username) =>
  `https://commons.wikimedia.org/wiki/User:${encodeURIComponent((username || '').trim())}`;

const formatBytes = (bytes) => {
  if (!bytes) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const exportCSV = () => {
  const header = ['Filename', 'Uploader', 'Date', 'Time (UTC)', 'Size (MB)', 'Camera', 'Commons URL'];
  const escape = (v) => {
    const s = String(v ?? '');
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const lines = [header.join(',')];
  userFiles.value.forEach(f => {
    lines.push([f.title, f.user, f.date, f.time, f.sizeMB, f.camera, f.commonsUrl].map(escape).join(','));
  });

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `${selectedUser.value || 'contributor'}-files.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};

// Keep the selection valid as the category or the search filter changes
watch(matchingContributors, (users) => {
  if (!users.length) {
    selectedUser.value = '';
  } else if (!users.some(u => u.name === selectedUser.value)) {
    selectedUser.value = users[0].name;
  }
}, { immediate: true });

// Switching contributor shouldn't leave the previous one's gallery open
watch(selectedUser, () => { galleryOpen.value = false; });
</script>
