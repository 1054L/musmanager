import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import fs from 'node:fs'

const defaultApiTarget = fs.existsSync('/.dockerenv') ? 'http://api:80' : 'http://127.0.0.1:8002'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    port: 5173,
    proxy: {
      '/api': {
        target: process.env.VITE_API_PROXY || defaultApiTarget,
        changeOrigin: true
      },
      '/uploads': {
        target: process.env.VITE_API_PROXY || defaultApiTarget,
        changeOrigin: true
      }
    },
    watch: {
      usePolling: true,
    },
  },
})
