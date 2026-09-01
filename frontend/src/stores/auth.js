import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('petugas_token') || null,
    user: JSON.parse(localStorage.getItem('petugas_user')) || null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    role: (state) => state.user?.role || null,
  },
  actions: {
    setToken(token) {
      this.token = token
      localStorage.setItem('petugas_token', token)
    },
    setUser(user) {
      this.user = user
      localStorage.setItem('petugas_user', JSON.stringify(user))
    },
    clearAuth() {
      this.token = null
      this.user = null
      localStorage.removeItem('petugas_token')
      localStorage.removeItem('petugas_user')
    },
    async login(username, password) {
      try {
        const response = await api.post('/api/admin/login', { username, password })
        const { token, user } = response.data
        this.setToken(token)
        this.setUser(user)
        return { success: true, user }
      } catch (error) {
        this.clearAuth()
        const message = error.response?.data?.message || 'Login gagal. Silakan coba lagi.'
        return { success: false, message }
      }
    },
    async logout() {
      try {
        await api.post('/api/admin/logout')
      } catch (error) {
        console.error('Logout error on server:', error)
      } finally {
        this.clearAuth()
      }
    }
  }
})
