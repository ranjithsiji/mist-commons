<template>
  <div class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Daily Uploads</h2>
        <p class="text-sm text-gray-500 mt-1">Browse the files uploaded to this category on a given day</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button
          @click="step(-1)"
          :disabled="!hasPrev"
          class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
          title="Previous day with uploads"
        >
          ‹ Prev
        </button>

        <input
          v-model="selectedDate"
          type="date"
          :min="dateBounds.min"
          :max="dateBounds.max"
          class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-wikimedia-blue/30 focus:border-wikimedia-blue"
        />

        <select v-model="selectedDate" class="max-w-xs px-3 py-2 text-sm border border-gray-300 rounded-lg">
          <option v-for="day in sortedDays" :key="day.date" :value="day.date">
            {{ day.date }} — {{ day.uploads }} file{{ day.uploads === 1 ? '' : 's' }}
          </option>
        </select>

        <button
          @click="step(1)"
          :disabled="!hasNext"
          class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
          title="Next day with uploads"
        >
          Next ›
        </button>

        <div class="h-6 w-px bg-gray-200 hidden sm:block" />

        <button
          @click="jumpToBusiest"
          class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
          title="Jump to the day with the most uploads"
        >
          Busiest day
        </button>
      </div>
    </div>

    <!-- Summary for the selected day -->
    <div v-if="selectedDay" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div class="bg-blue-50 rounded-lg p-4">
        <p class="text-xs text-gray-600 uppercase tracking-wide">Files</p>
        <p class="text-2xl font-bold text-blue-600">{{ selectedDay.uploads }}</p>
      </div>
      <div class="bg-green-50 rounded-lg p-4">
        <p class="text-xs text-gray-600 uppercase tracking-wide">Contributors</p>
        <p class="text-2xl font-bold text-green-600">{{ selectedDay.contributors }}</p>
      </div>
      <div class="bg-orange-50 rounded-lg p-4">
        <p class="text-xs text-gray-600 uppercase tracking-wide">Total Size</p>
        <p class="text-2xl font-bold text-orange-600">{{ formatBytes(selectedDay.size) }}</p>
      </div>
      <div class="bg-purple-50 rounded-lg p-4">
        <p class="text-xs text-gray-600 uppercase tracking-wide">Share of Category</p>
        <p class="text-2xl font-bold text-purple-600">{{ shareOfTotal }}%</p>
      </div>
    </div>

    <div v-if="!sortedDays.length" class="text-center py-12 text-gray-500">
      No upload activity found for this category.
    </div>

    <div v-else-if="!dayFiles.length" class="text-center py-12 text-gray-500">
      No files were uploaded to this category on {{ selectedDate }}.
      <div v-if="hasPrev || hasNext" class="mt-3 flex justify-center gap-2">
        <button
          v-if="hasPrev"
          @click="step(-1)"
          class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          ‹ {{ prevDay.date }}
        </button>
        <button
          v-if="hasNext"
          @click="step(1)"
          class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          {{ nextDay.date }} ›
        </button>
      </div>
    </div>

    <template v-else>
      <!-- Masonry grid; thumbnails keep their aspect ratio like the heritage tool -->
      <div class="gap-4 columns-2 sm:columns-3 lg:columns-4 xl:columns-5">
        <figure
          v-for="file in visibleFiles"
          :key="file.filename"
          class="group mb-4 break-inside-avoid border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200"
        >
          <a :href="file.commonsUrl" target="_blank" rel="noopener noreferrer" class="block bg-gray-100">
            <img
              :src="file.thumbnail"
              :alt="file.title"
              loading="lazy"
              class="w-full group-hover:opacity-90 transition-opacity duration-200"
              @error="onImageError"
            />
          </a>
          <figcaption class="p-3">
            <a
              :href="file.commonsUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="block text-sm font-medium text-gray-900 hover:text-wikimedia-blue truncate"
              :title="file.title"
            >
              {{ file.title }}
            </a>
            <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
              <a
                :href="userPageUrl(file.user)"
                target="_blank"
                rel="noopener noreferrer"
                class="truncate hover:text-wikimedia-blue"
                :title="file.user"
              >
                {{ file.user }}
              </a>
              <span class="flex-shrink-0 ml-2">{{ file.time }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-400">{{ file.sizeMB }} MB</p>
          </figcaption>
        </figure>
      </div>

      <div v-if="hasMore" class="mt-6 text-center">
        <button
          @click="visibleCount += PAGE_SIZE"
          class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          Show more ({{ dayFiles.length - visibleFiles.length }} remaining)
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  dailyUploads: { type: Array, required: true },
  filesByDate: { type: Object, required: true },
  totalFiles: { type: Number, default: 0 }
});

const PAGE_SIZE = 20;

const selectedDate = ref('');
const visibleCount = ref(PAGE_SIZE);

// Most recent day first, so the picker opens on the freshest activity
const sortedDays = computed(() =>
  [...(props.dailyUploads || [])].sort((a, b) => b.date.localeCompare(a.date))
);

const selectedDay = computed(() => sortedDays.value.find(d => d.date === selectedDate.value) || null);
const dayFiles = computed(() => props.filesByDate?.[selectedDate.value] || []);
const visibleFiles = computed(() => dayFiles.value.slice(0, visibleCount.value));
const hasMore = computed(() => dayFiles.value.length > visibleFiles.value.length);

const dateBounds = computed(() => {
  const days = sortedDays.value;
  return days.length
    ? { min: days[days.length - 1].date, max: days[0].date }
    : { min: '', max: '' };
});

// sortedDays runs newest → oldest. The date input can land on a day with no
// uploads, so prev/next step to the nearest active day on either side rather
// than relying on the current date being present in the list.
const prevDay = computed(() => sortedDays.value.find(d => d.date < selectedDate.value) || null);
const nextDay = computed(() => [...sortedDays.value].reverse().find(d => d.date > selectedDate.value) || null);
const hasPrev = computed(() => prevDay.value !== null);
const hasNext = computed(() => nextDay.value !== null);

const shareOfTotal = computed(() => {
  if (!props.totalFiles || !selectedDay.value) return '0.0';
  return ((selectedDay.value.uploads / props.totalFiles) * 100).toFixed(1);
});

const step = (direction) => {
  // direction -1 = older day, +1 = newer day
  const target = direction < 0 ? prevDay.value : nextDay.value;
  if (target) selectedDate.value = target.date;
};

const jumpToBusiest = () => {
  const busiest = [...sortedDays.value].sort((a, b) => b.uploads - a.uploads)[0];
  if (busiest) selectedDate.value = busiest.date;
};

const userPageUrl = (username) =>
  `https://commons.wikimedia.org/wiki/User:${encodeURIComponent((username || '').trim())}`;

const formatBytes = (bytes) => {
  if (!bytes) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

// Some Commons files (videos, PDFs, deleted revisions) have no renderable thumbnail
const onImageError = (event) => {
  event.target.style.display = 'none';
};

// Default to the most recent day, and follow the data when the category changes
watch(sortedDays, (days) => {
  if (!days.length) {
    selectedDate.value = '';
  } else if (!days.some(d => d.date === selectedDate.value)) {
    selectedDate.value = days[0].date;
  }
}, { immediate: true });

watch(selectedDate, () => { visibleCount.value = PAGE_SIZE; });
</script>
