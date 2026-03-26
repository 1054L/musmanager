import { defineStore } from 'pinia'
import { tournamentService } from '../services/api'

export const useTournamentStore = defineStore('tournament', {
  state: () => ({
    tournaments: [],
    loading: false,
    error: null,
    lastFetched: null
  }),

  getters: {
    activeTournaments: (state) => state.tournaments.filter(t => t.status === 'active'),
    pendingTournaments: (state) => state.tournaments.filter(t => t.status === 'pending'),
    hasTournaments: (state) => state.tournaments.length > 0
  },

  actions: {
    async fetchPublicTournaments(force = false) {
      // Basic caching logic: don't refetch if fetched in last 30 seconds unless forced
      if (!force && this.tournaments.length > 0 && this.lastFetched && (Date.now() - this.lastFetched < 30000)) {
        return
      }

      this.loading = true
      this.error = null
      try {
        const data = await tournamentService.getPublicTournaments()
        this.tournaments = data
        this.lastFetched = Date.now()
      } catch (err) {
        this.error = err.message || 'Failed to fetch tournaments'
        console.error('TournamentStore Error:', err)
      } finally {
        this.loading = false
      }
    }
  }
})
