<template>
  <div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Photo Locations Map</h2>
    <p class="text-gray-600 mb-4">
      {{ geoData.length }} photos with geolocation data. Click on markers to view photo details.
    </p>
    <div ref="mapContainer" class="h-[500px] w-full rounded-lg border border-gray-300"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  geoData: {
    type: Array,
    required: true
  }
});

const mapContainer = ref(null);
let mapInstance = null;

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
  
  mapInstance = L.map(mapContainer.value).setView([avgLat, avgLon], 8);
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(mapInstance);
  
  const birdIcon = L.divIcon({
    className: 'custom-bird-marker',
    html: '<div style="background-color: #3B82F6; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
    iconSize: [30, 30],
    iconAnchor: [15, 30],
    popupAnchor: [0, -30]
  });
  
  validGeoData.forEach(item => {
    const marker = L.marker([item.lat, item.lon], { icon: birdIcon }).addTo(mapInstance);
    
    const popupContent = `
      <div style="min-width: 200px;">
        <img src="${item.thumbnail}" alt="${item.filename}" style="width: 100%; max-width: 300px; height: auto; border-radius: 4px; margin-bottom: 8px;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22150%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo thumbnail%3C/text%3E%3C/svg%3E'" />
        <div style="font-weight: 600; margin-bottom: 4px; word-break: break-word;">${item.filename}</div>
        <div style="font-size: 12px; color: #666; margin-bottom: 4px;">By: ${item.author}</div>
        <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Date: ${item.date}</div>
        <a href="${item.commonsUrl}" target="_blank" rel="noopener noreferrer" style="display: inline-block; background-color: #3B82F6; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 500;">View on Commons</a>
      </div>
    `;
    
    marker.bindPopup(popupContent, { maxWidth: 350 });
  });
  
  if (validGeoData.length > 0) {
    const bounds = L.latLngBounds(validGeoData.map(d => [d.lat, d.lon]));
    mapInstance.fitBounds(bounds, { padding: [50, 50] });
  }
};

onMounted(() => {
  if (props.geoData.length > 0) {
    initMap();
  }
});

watch(() => props.geoData, (newData) => {
  if (newData.length > 0) {
    initMap();
  }
}, { deep: true });

onBeforeUnmount(() => {
  if (mapInstance) {
    mapInstance.remove();
    mapInstance = null;
  }
});
</script>