<template>
  <div class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800 mb-1">Photo Locations Map</h2>
        <p class="text-gray-600">
          {{ geoData.length }} photos with geolocation data. Click on markers to view photo details.
        </p>
      </div>
      
      <!-- Map Controls -->
      <div class="flex items-center space-x-3">
        <div class="text-sm text-gray-500">
          <span class="font-medium">Clusters:</span> {{ clusterCount }}
        </div>
        <button
          @click="toggleClustering"
          :class="[
            'px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200',
            clusteringEnabled
              ? 'bg-wikimedia-blue text-white hover:bg-blue-700'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
          ]"
        >
          {{ clusteringEnabled ? 'Clustering ON' : 'Clustering OFF' }}
        </button>
      </div>
    </div>
    
    <!-- Clustering Info Bar -->
    <div v-if="clusteringEnabled" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
      <div class="flex items-center text-sm text-blue-800">
        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>
          Markers are grouped into clusters. Zoom in or click on clusters to expand them and see individual photos.
        </span>
      </div>
    </div>
    
    <div ref="mapContainer" class="h-[500px] w-full rounded-lg border border-gray-300 overflow-hidden"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Import marker clustering plugin
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';

const props = defineProps({
  geoData: {
    type: Array,
    required: true
  }
});

const mapContainer = ref(null);
let mapInstance = null;
let markerClusterGroup = null;
let individualMarkers = [];

const clusteringEnabled = ref(true);
const clusterCount = ref(0);

const initMap = () => {
  if (!mapContainer.value || props.geoData.length === 0) return;
  
  if (mapInstance) {
    mapInstance.remove();
    mapInstance = null;
  }
  
  const validGeoData = props.geoData.filter(d => d.lat && d.lon);
  if (validGeoData.length === 0) return;
  
  const avgLat = validGeoData.reduce((sum, d) => sum + d.lat, 0) / validGeoData.length;
  const avgLon = validGeoData.reduce((sum, d) => sum + d.lon, 0) / validGeoData.length;
  
  mapInstance = L.map(mapContainer.value, {
    zoomControl: true,
    attributionControl: true
  }).setView([avgLat, avgLon], 8);
  
  // Borderless (no-label, minimal) basemap
  // Using Carto light without labels and minimal borders
  const cartoNoLabels = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    subdomains: 'abcd',
    maxZoom: 19
  }).addTo(mapInstance);
  
  // Optional labels layer to toggle (kept off by default)
  // const cartoLabels = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png', {
  //   attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
  //   subdomains: 'abcd',
  //   maxZoom: 19
  // });
  
  // Create custom location pin marker icon
  const locationIcon = L.divIcon({
    className: 'custom-location-marker',
    html: `
      <div style="position: relative; width: 20px; height: 20px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#EF4444" stroke="#DC2626" stroke-width="0.5"/>
          <circle cx="12" cy="9" r="1.5" fill="white"/>
        </svg>
      </div>
    `,
    iconSize: [20, 20],
    iconAnchor: [10, 20],
    popupAnchor: [0, -20]
  });
  
  // Initialize marker cluster group with custom options
  markerClusterGroup = L.markerClusterGroup({
    chunkedLoading: true,
    chunkInterval: 200,
    chunkDelay: 50,
    maxClusterRadius: 60,
    disableClusteringAtZoom: 15,
    animate: true,
    animateAddingMarkers: true,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
    iconCreateFunction: function(cluster) {
      const count = cluster.getChildCount();
      let colorClass = 'cluster-small';
      if (count >= 50) colorClass = 'cluster-large';
      else if (count >= 10) colorClass = 'cluster-medium';
      return L.divIcon({
        html: `
          <div class="custom-cluster-marker ${colorClass}">
            <div class="cluster-inner">
              <span class="cluster-count">${count}</span>
            </div>
          </div>
        `,
        className: 'custom-cluster-container',
        iconSize: L.point(40, 40),
        iconAnchor: [20, 20]
      });
    }
  });
  
  // Create markers and add them to appropriate groups
  individualMarkers = [];
  validGeoData.forEach(item => {
    const marker = L.marker([item.lat, item.lon], { icon: locationIcon });
    const popupContent = `
      <div style="min-width: 200px;">
        <img src="${item.thumbnail}" alt="${item.filename}" style="width: 100%; max-width: 300px; height: auto; border-radius: 4px; margin-bottom: 8px;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22150%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo thumbnail%3C/text%3E%3C/svg%3E'" />
        <div style="font-weight: 600; margin-bottom: 4px; word-break: break-word;">${item.filename}</div>
        <div style="font-size: 12px; color: #666; margin-bottom: 4px;">By: <a href="https://commons.wikimedia.org/wiki/User:${encodeURIComponent(item.author)}" target="_blank" rel="noopener noreferrer" style="color: #3B82F6; text-decoration: none; font-weight: 500;">${item.author}</a></div>
        <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Date: ${item.date}</div>
        <a href="${item.commonsUrl}" target="_blank" rel="noopener noreferrer" style="display: inline-block; background-color: #3B82F6; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 500;">View on Commons</a>
      </div>
    `;
    marker.bindPopup(popupContent, { maxWidth: 350 });
    markerClusterGroup.addLayer(marker);
    individualMarkers.push(marker);
  });
  
  if (clusteringEnabled.value) {
    mapInstance.addLayer(markerClusterGroup);
  } else {
    individualMarkers.forEach(marker => marker.addTo(mapInstance));
  }
  
  updateClusterCount();
  
  if (validGeoData.length > 0) {
    const bounds = L.latLngBounds(validGeoData.map(d => [d.lat, d.lon]));
    mapInstance.fitBounds(bounds, { padding: [0, 0] });
  }
  
  if (markerClusterGroup) {
    markerClusterGroup.on('clustermouseover', updateClusterCount);
    markerClusterGroup.on('clusterclick', updateClusterCount);
    mapInstance.on('zoomend', updateClusterCount);
  }
};

const updateClusterCount = () => {
  if (markerClusterGroup && clusteringEnabled.value) {
    let visibleClusters = 0;
    mapInstance.eachLayer(layer => {
      if (layer._group === markerClusterGroup && layer._icon) {
        visibleClusters++;
      }
    });
    clusterCount.value = visibleClusters;
  } else {
    clusterCount.value = individualMarkers.length;
  }
};

const toggleClustering = () => {
  if (!mapInstance || !markerClusterGroup) return;
  clusteringEnabled.value = !clusteringEnabled.value;
  if (clusteringEnabled.value) {
    individualMarkers.forEach(marker => { if (mapInstance.hasLayer(marker)) mapInstance.removeLayer(marker); });
    mapInstance.addLayer(markerClusterGroup);
  } else {
    if (mapInstance.hasLayer(markerClusterGroup)) mapInstance.removeLayer(markerClusterGroup);
    individualMarkers.forEach(marker => marker.addTo(mapInstance));
  }
  updateClusterCount();
};

onMounted(() => { if (props.geoData.length > 0) { initMap(); } });
watch(() => props.geoData, (newData) => { if (newData.length > 0) { initMap(); } }, { deep: true });
onBeforeUnmount(() => { if (mapInstance) { mapInstance.remove(); mapInstance = null; } markerClusterGroup = null; individualMarkers = []; });
</script>

<style scoped>
:deep(.custom-cluster-container) { background: transparent !important; border: none !important; }
:deep(.custom-cluster-marker) { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; text-align: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); border: 3px solid white; transition: all 0.2s ease; }
:deep(.custom-cluster-marker:hover) { transform: scale(1.1); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); }
:deep(.cluster-small) { background: linear-gradient(135deg, #3B82F6, #1D4ED8); }
:deep(.cluster-medium) { background: linear-gradient(135deg, #F59E0B, #D97706); }
:deep(.cluster-large) { background: linear-gradient(135deg, #EF4444, #DC2626); }
:deep(.cluster-inner) { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; }
:deep(.cluster-count) { font-size: 14px; font-weight: 700; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2); }
:deep(.custom-location-marker) { background: transparent !important; border: none !important; }
:deep(.leaflet-popup) { z-index: 1000; }
:deep(.marker-cluster) { background-clip: padding-box; border-radius: 20px; }
:deep(.marker-cluster div) { width: 30px; height: 30px; margin-left: 5px; margin-top: 5px; text-align: center; border-radius: 15px; font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif; }
:deep(.marker-cluster span) { line-height: 30px; }
</style>
