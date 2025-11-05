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

    <!-- Contributors Anchor -->
    <div id="contributors" class="scroll-mt-20">
      <ContributorsTable :user-contributions="dashboardData.userContributions" />
    </div>
  </div>
</template>

<script setup>
import StatsCards from './components/StatsCards.vue';
import PhotoMap from './components/PhotoMap.vue';
import DashboardCharts from './components/DashboardCharts.vue';
import ContributorsTable from './components/ContributorsTable.vue';

const props = defineProps({
  dashboardData: { type: Object, required: true },
  stats: { type: Object, required: true },
  geoData: { type: Array, required: true }
});
</script>
