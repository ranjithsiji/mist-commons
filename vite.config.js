import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ mode }) => {
  return {
    plugins: [vue()],
    
    // Environment variables
    define: {
      __DEV__: mode === 'development',
    },
    
    server: {
      port: 3000,
      open: true,
      proxy: {
        '/api': {
          target: process.env.VITE_PHP_SERVER_URL || 'https://mist.toolforge.org',
          changeOrigin: true,
          secure: false,
          configure: (proxy, options) => {
            proxy.on('error', (err, req, res) => {
              console.log('Proxy error:', err)
            })
            proxy.on('proxyReq', (proxyReq, req, res) => {
              console.log('Proxying request:', req.method, req.url, '→', options.target + req.url)
            })
            proxy.on('proxyRes', (proxyRes, req, res) => {
              console.log('Proxy response:', proxyRes.statusCode, req.url)
            })
          },
        }
      }
    },
    
    css: {
      postcss: './postcss.config.cjs',
    },
    
    build: {
      outDir: 'dist',
      assetsDir: 'assets',
      sourcemap: mode === 'development',
      rollupOptions: {
        output: {
          manualChunks: {
            'vendor': ['vue'],
            'charts': ['chart.js'],
            'maps': ['leaflet']
          }
        }
      }
    },
    
    // Environment variables prefix
    envPrefix: 'VITE_',
  }
})