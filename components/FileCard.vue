<template>
  <figure class="group mb-4 break-inside-avoid border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200">
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
        <!-- The uploader is redundant when every card shows the same user -->
        <a
          v-if="showUser"
          :href="userPageUrl(file.user)"
          target="_blank"
          rel="noopener noreferrer"
          class="truncate hover:text-wikimedia-blue"
          :title="file.user"
        >
          {{ file.user }}
        </a>
        <span v-else>{{ file.date }}</span>
        <span class="flex-shrink-0 ml-2">{{ showUser ? file.time : `${file.sizeMB} MB` }}</span>
      </div>
      <p v-if="showUser" class="mt-1 text-xs text-gray-400">{{ file.sizeMB }} MB</p>
      <p v-else-if="file.camera" class="mt-1 text-xs text-gray-400 truncate" :title="file.camera">
        {{ file.camera }}
      </p>
    </figcaption>
  </figure>
</template>

<script setup>
defineProps({
  file: { type: Object, required: true },
  // Daily view shows who uploaded; the per-user view shows date/camera instead
  showUser: { type: Boolean, default: true }
});

const userPageUrl = (username) =>
  `https://commons.wikimedia.org/wiki/User:${encodeURIComponent((username || '').trim())}`;

// Some Commons files (videos, PDFs, deleted revisions) have no renderable thumbnail
const onImageError = (event) => {
  event.target.style.display = 'none';
};
</script>
