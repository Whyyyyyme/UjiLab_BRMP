import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useAksesPublikStore } from '../stores/aksesPublik'
import router from '../router'

// Membuat instance axios dengan konfigurasi dasar
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  timeout: 60000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

// Request Interceptor: Menambahkan token Sanctum ke header Authorization
api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers['Authorization'] = `Bearer ${authStore.token}`
    }
    
    const aksesPublikStore = useAksesPublikStore()
    if (aksesPublikStore.token) {
      config.headers['X-Akses-Token'] = aksesPublikStore.token
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response Interceptor: Menangani error autentikasi secara global
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const authStore = useAuthStore()
    
    if (error.response) {
      const status = error.response.status
      
      // Jika token tidak valid / kedaluwarsa (401 Unauthorized)
      if (status === 401) {
        // Hanya arahkan ke login admin jika route saat ini adalah area admin
        const currentRoute = router.currentRoute.value
        if (currentRoute.path.startsWith('/admin')) {
          authStore.clearAuth()
          router.push({ name: 'AdminLogin', query: { redirect: currentRoute.fullPath } })
        }
      }
    }
    
    return Promise.reject(error)
  }
)

export default api

