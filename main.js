import { createApp } from 'vue'
import './style.css'
import App from './App.vue'

// Create and mount the Vue application
const app = createApp(App)

// Global error handler
app.config.errorHandler = (err, instance, info) => {
  console.error('Global error:', err)
  console.error('Error info:', info)
  console.error('Component:', instance)
}

// Global warning handler for development
app.config.warnHandler = (msg, instance, trace) => {
  console.warn('Warning:', msg)
  console.warn('Trace:', trace)
}

// Mount the app
app.mount('#app')

// Log application info
console.log('Wikimedia Commons Dashboard initialized')
console.log('Environment:', import.meta.env.MODE)
