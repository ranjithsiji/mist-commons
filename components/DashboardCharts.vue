<template>
  <div>
    <!-- Daily Uploads Chart -->
    <div id="daily-uploads" class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Daily Upload Activity</h2>
      <canvas ref="dailyChart"></canvas>
    </div>

    <!-- User Contributions -->
    <div id="top-users" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Top Contributors (Files)</h2>
        <canvas ref="userBarChart"></canvas>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Top Camera Models</h2>
        <canvas ref="cameraChart"></canvas>
      </div>
    </div>

    <!-- Full Width Pie Chart -->
    <div id="distribution" class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Contribution Distribution</h2>
      <div class="flex justify-center">
        <div class="w-full max-w-2xl">
          <canvas ref="userPieChart"></canvas>
        </div>
      </div>
    </div>

    <!-- File Size Distribution and Top Upload Days -->
    <div id="file-activity" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">File Size Distribution</h2>
        <canvas ref="sizeChart"></canvas>
      </div>
      
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Top Upload Days</h2>
        <canvas ref="topDaysChart"></canvas>
      </div>
    </div>

    <!-- Upload Time Distribution -->
    <div id="time-activity" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Upload Hours Distribution</h2>
        <canvas ref="hourlyChart"></canvas>
      </div>
      
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Monthly Activity</h2>
        <canvas ref="monthlyChart"></canvas>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
});

const dailyChart = ref(null);
const userBarChart = ref(null);
const userPieChart = ref(null);
const sizeChart = ref(null);
const cameraChart = ref(null);
const topDaysChart = ref(null);
const hourlyChart = ref(null);
const monthlyChart = ref(null);

let charts = {};

const getDateBounds = () => {
  const dates = (props.data?.dailyUploads || []).map(d => new Date(d.date));
  if (!dates.length) return null;
  dates.sort((a,b) => a - b);
  return { min: dates[0], max: dates[dates.length - 1] };
};

const initCharts = () => {
  // Destroy existing charts
  Object.values(charts).forEach(chart => chart.destroy());
  charts = {};

  const bounds = getDateBounds();
  
  // Daily Uploads Chart
  if (dailyChart.value && props.data.dailyUploads) {
    charts.daily = new Chart(dailyChart.value, {
      type: 'line',
      data: {
        labels: props.data.dailyUploads.map(d => d.date),
        datasets: [{
          label: 'Uploads',
          data: props.data.dailyUploads.map(d => d.uploads),
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: true } },
        scales: bounds ? {
          x: {
            type: 'time',
            time: { unit: 'day' },
            min: bounds.min,
            max: bounds.max
          }
        } : {}
      }
    });
  }

  // Other charts unchanged ...
  if (userBarChart.value && props.data.userContributions) {
    const topUsers = props.data.userContributions.slice(0, 10);
    charts.userBar = new Chart(userBarChart.value, {
      type: 'bar',
      data: {
        labels: topUsers.map(u => u.name),
        datasets: [{ label: 'Files', data: topUsers.map(u => u.files), backgroundColor: '#10B981' }]
      },
      options: { responsive: true, maintainAspectRatio: true, indexAxis: 'y' }
    });
  }

  if (userPieChart.value && props.data.userContributions) {
    const topUsers = props.data.userContributions.slice(0, 8);
    charts.userPie = new Chart(userPieChart.value, {
      type: 'pie',
      data: {
        labels: topUsers.map(u => u.name),
        datasets: [{
          data: topUsers.map(u => u.files),
          backgroundColor: [
            '#0088FE', '#00C49F', '#FFBB28', '#FF8042',
            '#8884D8', '#82CA9D', '#FFC658', '#FF6B9D'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'right' } }
      }
    });
  }

  if (sizeChart.value && props.data.sizeDistribution) {
    charts.size = new Chart(sizeChart.value, {
      type: 'bar',
      data: {
        labels: props.data.sizeDistribution.map(s => s.range),
        datasets: [{ label: 'Files', data: props.data.sizeDistribution.map(s => s.count), backgroundColor: '#F59E0B' }]
      },
      options: { responsive: true, maintainAspectRatio: true }
    });
  }

  if (topDaysChart.value && props.data.dailyUploads) {
    const topDays = [...props.data.dailyUploads].sort((a,b) => b.uploads - a.uploads).slice(0,10);
    charts.topDays = new Chart(topDaysChart.value, {
      type: 'bar',
      data: {
        labels: topDays.map(d => d.date),
        datasets: [{ label: 'Uploads', data: topDays.map(d => d.uploads), backgroundColor: '#EF4444' }]
      },
      options: { responsive: true, maintainAspectRatio: true, indexAxis: 'y' }
    });
  }

  if (hourlyChart.value && props.data.hourlyDistribution) {
    charts.hourly = new Chart(hourlyChart.value, {
      type: 'bar',
      data: {
        labels: props.data.hourlyDistribution.map(h => `${h.hour}:00`),
        datasets: [{ label: 'Uploads', data: props.data.hourlyDistribution.map(h => h.count), backgroundColor: '#8B5CF6' }]
      },
      options: { responsive: true, maintainAspectRatio: true }
    });
  }

  if (monthlyChart.value && props.data.monthlyActivity) {
    charts.monthly = new Chart(monthlyChart.value, {
      type: 'line',
      data: {
        labels: props.data.monthlyActivity.map(m => m.month),
        datasets: [{
          label: 'Uploads',
          data: props.data.monthlyActivity.map(m => m.count),
          borderColor: '#06B6D4',
          backgroundColor: 'rgba(6, 182, 212, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: { responsive: true, maintainAspectRatio: true }
    });
  }

  if (cameraChart.value && props.data.cameraData) {
    charts.camera = new Chart(cameraChart.value, {
      type: 'bar',
      data: {
        labels: props.data.cameraData.map(c => c.model),
        datasets: [{ label: 'Photos', data: props.data.cameraData.map(c => c.count), backgroundColor: '#8B5CF6' }]
      },
      options: { responsive: true, maintainAspectRatio: true, indexAxis: 'y' }
    });
  }
};

onMounted(() => { if (props.data) initCharts(); });
watch(() => props.data, (n) => { if (n) initCharts(); }, { deep: true });
onBeforeUnmount(() => { Object.values(charts).forEach(c => c.destroy()); charts = {}; });
</script>
