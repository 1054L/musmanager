import { defineStore } from 'pinia'
import { locationService } from '../services/api'

export const useLocationStore = defineStore('location', {
  state: () => ({
    provinces: [],
    towns: [],
    loading: false,
    loaded: false
  }),
  actions: {
    async fetchLocations() {
      if (this.loaded) return

      // Try loading from localStorage first
      const cached = localStorage.getItem('mus_locations')
      if (cached) {
        const { provinces, towns, timestamp } = JSON.parse(cached)
        // Cache valid for 24 hours
        if (Date.now() - timestamp < 24 * 60 * 60 * 1000) {
          this.provinces = provinces
          this.towns = towns
          this.loaded = true
          return
        }
      }

      this.loading = true
      try {
        const data = await locationService.getAll()
        this.provinces = data.provinces
        this.towns = data.towns
        this.loaded = true
        
        // Save to localStorage
        localStorage.setItem('mus_locations', JSON.stringify({
          provinces: data.provinces,
          towns: data.towns,
          timestamp: Date.now()
        }))
      } catch (err) {
        console.error('Error fetching locations:', err)
      } finally {
        this.loading = false
      }
    },
    getTownsByProvince(provinceId) {
      return this.towns.filter(t => t.provinceId === provinceId)
    }
  }
})
