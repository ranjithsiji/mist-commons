import { createApp } from 'vue'
import './style.css'
import LayoutShell from './src/LayoutShell.vue'
import router from './src/router/index.js'

createApp(LayoutShell).use(router).mount('#app')
