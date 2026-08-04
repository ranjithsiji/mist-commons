<template>
  <transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/50"
      @click.self="close"
    >
      <div
        class="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-[85vh] flex flex-col"
        role="dialog"
        aria-modal="true"
        aria-labelledby="file-gallery-title"
      >
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 p-6 border-b border-gray-200">
          <div>
            <h2 id="file-gallery-title" class="text-xl font-bold text-gray-800">{{ title }}</h2>
            <p class="text-sm text-gray-500 mt-1">
              {{ files.length }} file{{ files.length === 1 ? '' : 's' }}
              <span v-if="subtitle"> · {{ subtitle }}</span>
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
          <div v-if="!files.length" class="text-center py-12 text-gray-500">
            No files to show.
          </div>

          <template v-else>
            <div class="gap-4 columns-2 sm:columns-3 lg:columns-4 xl:columns-5">
              <FileCard
                v-for="file in visibleFiles"
                :key="file.filename"
                :file="file"
                :show-user="showUser"
              />
            </div>

            <div v-if="hasMore" class="mt-6 text-center">
              <button
                @click="visibleCount += PAGE_SIZE"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Show more ({{ files.length - visibleFiles.length }} remaining)
              </button>
            </div>
          </template>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-t border-gray-200">
          <span class="text-xs text-gray-400">
            Showing {{ visibleFiles.length }} of {{ files.length }}
          </span>
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
import FileCard from './FileCard.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Files' },
  subtitle: { type: String, default: '' },
  files: { type: Array, default: () => [] },
  showUser: { type: Boolean, default: true }
});

const emit = defineEmits(['close']);

const PAGE_SIZE = 30;
const visibleCount = ref(PAGE_SIZE);

const visibleFiles = computed(() => props.files.slice(0, visibleCount.value));
const hasMore = computed(() => props.files.length > visibleFiles.value.length);

const close = () => emit('close');

// Start from the top whenever the popup opens on a different set of files
watch(() => [props.open, props.files], () => {
  visibleCount.value = PAGE_SIZE;
});

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
