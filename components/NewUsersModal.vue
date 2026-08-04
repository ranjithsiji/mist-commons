<template>
  <transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/50"
      @click.self="close"
    >
      <div
        class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col"
        role="dialog"
        aria-modal="true"
        aria-labelledby="new-users-title"
      >
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 p-6 border-b border-gray-200">
          <div>
            <h2 id="new-users-title" class="text-xl font-bold text-gray-800">New Contributors</h2>
            <p class="text-sm text-gray-500 mt-1">
              Uploaders who registered on Commons for this campaign
            </p>
          </div>
          <button
            @click="close"
            class="p-2 -m-2 text-gray-400 hover:text-gray-700 rounded-lg"
            aria-label="Close"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="loading" class="text-center py-16">
            <div class="w-12 h-12 mx-auto mb-4 border-4 border-wikimedia-blue/20 border-t-wikimedia-blue rounded-full animate-spin"></div>
            <p class="text-gray-600">Checking registration dates on Commons…</p>
            <p class="text-sm text-gray-400 mt-1">This can take a few seconds for large categories.</p>
          </div>

          <div v-else-if="error" class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-lg">
            <h3 class="font-medium">Could not load new contributors</h3>
            <p class="text-sm mt-1">{{ error }}</p>
            <button
              @click="load(true)"
              class="mt-3 px-3 py-2 text-sm bg-white border border-red-300 rounded-lg hover:bg-red-50"
            >
              Try again
            </button>
          </div>

          <template v-else-if="result">
            <!-- Summary -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
              <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-xs text-gray-600 uppercase tracking-wide">New Contributors</p>
                <p class="text-3xl font-bold text-blue-600">{{ result.newUserCount }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-4">
                <p class="text-xs text-gray-600 uppercase tracking-wide">All Uploaders</p>
                <p class="text-3xl font-bold text-green-600">{{ result.totalUploaders }}</p>
              </div>
              <div class="bg-purple-50 rounded-lg p-4">
                <p class="text-xs text-gray-600 uppercase tracking-wide">Share of Uploaders</p>
                <p class="text-3xl font-bold text-purple-600">{{ sharePercent }}%</p>
              </div>
            </div>

            <p class="text-xs text-gray-500 mb-4">
              Counts accounts registered between
              <strong>{{ formatWindowDate(result.window.start) }}</strong> and
              <strong>{{ formatWindowDate(result.window.end) }}</strong>
              — the campaign period plus {{ result.window.graceDays }} days before it started.
            </p>

            <div v-if="!result.newUsers.length" class="text-center py-12 text-gray-500">
              No contributors registered on Commons during this campaign window.
            </div>

            <template v-else>
              <!-- Controls -->
              <div class="flex flex-wrap items-center gap-2 mb-4">
                <input
                  v-model.trim="query"
                  type="search"
                  placeholder="Search user…"
                  class="flex-1 min-w-[12rem] px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-wikimedia-blue/30 focus:border-wikimedia-blue"
                />
                <select v-model="sortKey" class="px-3 py-2 text-sm border border-gray-300 rounded-lg">
                  <option value="registration">Newest account first</option>
                  <option value="files">Most files first</option>
                  <option value="name">By username</option>
                </select>
                <button @click="exportCSV" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                  Export CSV
                </button>
              </div>

              <!-- List -->
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size (MB)</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Upload</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(user, index) in visibleRows" :key="user.username" class="hover:bg-gray-50">
                      <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ index + 1 }}</td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm">
                        <a
                          :href="userPageUrl(user.username)"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-wikimedia-blue hover:text-wikimedia-green font-medium hover:underline"
                        >
                          {{ user.username }}
                        </a>
                      </td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ formatStamp(user.registration) }}</td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ user.files }}</td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ toMB(user.size) }}</td>
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ formatStamp(user.firstUpload) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <p v-if="!visibleRows.length" class="text-center py-8 text-gray-500">
                No new contributor matches “{{ query }}”.
              </p>
            </template>
          </template>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-t border-gray-200">
          <span v-if="result?.cached" class="text-xs text-gray-400">
            Cached result{{ result.cache_age_seconds != null ? ` (${Math.round(result.cache_age_seconds / 60)} min old)` : '' }}
          </span>
          <span v-else class="text-xs text-gray-400"></span>
          <button @click="close" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
            Close
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useApi } from '../composables/useApi';

const props = defineProps({
  open: { type: Boolean, default: false },
  category: { type: String, default: '' },
  dateRange: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['close']);

const { fetchNewUsers } = useApi();

const loading = ref(false);
const error = ref('');
const result = ref(null);
const query = ref('');
const sortKey = ref('registration');

// Which category/date combination the current result belongs to, so switching
// campaigns refetches but reopening the same one reuses what we already have.
let loadedKey = '';

const close = () => emit('close');

const load = async (force = false) => {
  const key = `${props.category}|${props.dateRange?.startDate}|${props.dateRange?.endDate}`;
  if (!force && result.value && loadedKey === key) return;

  loading.value = true;
  error.value = '';
  result.value = null;
  try {
    const data = await fetchNewUsers(props.category, props.dateRange);
    result.value = data;
    loadedKey = key;
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const sharePercent = computed(() => {
  if (!result.value?.totalUploaders) return '0.0';
  return ((result.value.newUserCount / result.value.totalUploaders) * 100).toFixed(1);
});

const visibleRows = computed(() => {
  const users = result.value?.newUsers || [];
  const q = query.value.toLowerCase();
  const rows = q ? users.filter(u => u.username.toLowerCase().includes(q)) : [...users];

  switch (sortKey.value) {
    case 'files':
      return rows.sort((a, b) => b.files - a.files);
    case 'name':
      return rows.sort((a, b) => a.username.localeCompare(b.username));
    default:
      return rows.sort((a, b) => String(b.registration).localeCompare(String(a.registration)));
  }
});

const userPageUrl = (username) =>
  `https://commons.wikimedia.org/wiki/User:${encodeURIComponent((username || '').trim())}`;

// MediaWiki timestamps are YYYYMMDDHHMMSS
const formatStamp = (stamp) => {
  const s = String(stamp || '');
  return s.length >= 8 ? `${s.slice(0, 4)}-${s.slice(4, 6)}-${s.slice(6, 8)}` : '—';
};
const formatWindowDate = (stamp) => formatStamp(stamp);

const toMB = (bytes) => ((bytes || 0) / (1024 * 1024)).toFixed(2);

const exportCSV = () => {
  const header = ['Rank', 'Username', 'Registered', 'Files', 'Size (MB)', 'First Upload', 'Last Upload'];
  const escape = (v) => {
    const s = String(v ?? '');
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const lines = [header.join(',')];
  visibleRows.value.forEach((u, i) => {
    lines.push([
      i + 1, u.username, formatStamp(u.registration), u.files,
      toMB(u.size), formatStamp(u.firstUpload), formatStamp(u.lastUpload)
    ].map(escape).join(','));
  });

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'new-contributors.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};

// Fetch only when the popup is actually opened
watch(() => props.open, (isOpen) => {
  if (isOpen) load();
});

// A different campaign invalidates whatever we cached
watch(() => props.category, () => {
  result.value = null;
  error.value = '';
  loadedKey = '';
});

// Close on Escape while open
const onKeydown = (e) => {
  if (e.key === 'Escape' && props.open) close();
};
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    window.addEventListener('keydown', onKeydown);
    document.body.style.overflow = 'hidden';
  } else {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
  }
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
