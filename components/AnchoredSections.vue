<template>
  <div>
    <!-- Overview Anchor -->
    <div id="overview" class="scroll-mt-20">
      <StatsCards :stats="stats" />
    </div>

    <!-- Map Anchor -->
    <div id="map" class="scroll-mt-20">
      <PhotoMap v-if="geoData.length > 0" :geo-data="geoData" />
    </div>

    <!-- Activity Anchor -->
    <div id="activity" class="scroll-mt-20">
      <DashboardCharts :data="dashboardData" />
    </div>

    <!-- Daily Uploads Anchor -->
    <div id="daily" class="scroll-mt-20">
      <DailyUploads
        :daily-uploads="dashboardData.dailyUploads"
        :files-by-date="dashboardData.filesByDate"
        :total-files="stats.totalFiles"
      />
    </div>

    <!-- Contributors Anchor -->
    <div id="contributors" class="scroll-mt-20">
      <ContributorsTable
        :user-contributions="dashboardData.userContributions"
        @show-files="$emit('show-files', $event)"
      />
    </div>

    <!-- Files by Contributor Anchor -->
    <div id="user-files" class="scroll-mt-20">
      <UserFiles
        :user-contributions="dashboardData.userContributions"
        :files-by-user="dashboardData.filesByUser"
      />
    </div>
  </div>
</template>

<script setup>
import StatsCards from './StatsCards.vue';
import PhotoMap from './PhotoMap.vue';
import DashboardCharts from './DashboardCharts.vue';
import DailyUploads from './DailyUploads.vue';
import UserFiles from './UserFiles.vue';
import ContributorsTable from './ContributorsTable.vue';

const props = defineProps({
  dashboardData: { type: Object, required: true },
  stats: { type: Object, required: true },
  geoData: { type: Array, required: true }
});

defineEmits(['show-files']);
</script>
