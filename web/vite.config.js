import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    port: 5173,
    proxy: {
      '/api': {
        target: process.env.VITE_API_PROXY || 'http://127.0.0.1:8002',
        changeOrigin: true
      },
      '/uploads': {
        target: process.env.VITE_API_PROXY || 'http://127.0.0.1:8002',
        changeOrigin: true
      }
    },
    watch: {
      usePolling: true,
    },
  },
})
