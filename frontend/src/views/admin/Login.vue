<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { AlertTriangle, Loader2, LogIn } from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => {
  if (!username.value || !password.value) {
    errorMessage.value = 'Silakan isi username dan password.'
    return
  }
  
  isLoading.value = true
  errorMessage.value = ''
  
  const result = await authStore.login(username.value, password.value)
  
  isLoading.value = false
  if (result.success) {
    // Cek jika wajib ganti password
    if (result.user.wajib_ganti_password) {
      // Kita akan buat halaman ganti password / modal ganti password
      // Untuk saat ini kita arahkan ke dashboard
      router.push({ name: 'AdminDashboard' })
    } else {
      router.push({ name: 'AdminDashboard' })
    }
  } else {
    errorMessage.value = result.message
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <div class="card-header">
        <div class="logo">
          <img src="../../assets/logo-kementan.png" alt="Logo Kementan" class="login-logo-img" />
        </div>
        <h1>Portal Distribusi Hasil Lab</h1>
        <p>Balai Besar Perakitan dan Modernisasi Bioteknologi dan Sumber Daya Genetik Pertanian</p>
      </div>
      
      <form @submit.prevent="handleLogin" class="login-form">
        <div v-if="errorMessage" class="alert alert-danger">
          <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
        </div>
        
        <div class="form-group">
          <label for="username">Username</label>
          <input 
            type="text" 
            id="username" 
            v-model="username" 
            placeholder="Masukkan username petugas" 
            :disabled="isLoading"
            required
            autocomplete="username"
          />
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input 
            type="password" 
            id="password" 
            v-model="password" 
            placeholder="Masukkan password" 
            :disabled="isLoading"
            required
            autocomplete="current-password"
          />
        </div>
        
        <button type="submit" class="btn-login" :disabled="isLoading">
          <span v-if="isLoading" class="flex-center-gap">
            <Loader2 class="animate-spin" :size="16" /> Memproses...
          </span>
          <span v-else class="flex-center-gap">
            Masuk ke Portal <LogIn :size="16" />
          </span>
        </button>
      </form>
      
      <div class="card-footer">
        <router-link to="/" class="back-link">← Kembali ke Pencarian Publik</router-link>
        <span class="copyright">BRMP Biogen &copy; 2026</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #0F3328 0%, #1B4D3E 50%, #0D2B22 100%);
  padding: 20px;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.login-card {
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 40px;
  border-radius: 20px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  color: #f8fafc;
}

.card-header {
  text-align: center;
  margin-bottom: 30px;
}

.logo {
  margin-bottom: 16px;
  background: rgba(255, 255, 255, 0.12);
  width: 96px;
  height: 96px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin-left: auto;
  margin-right: auto;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.3);
  border: 2px solid rgba(255, 255, 255, 0.2);
  padding: 8px;
  box-sizing: border-box;
}

.login-logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.card-header h1 {
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 10px 0;
  color: #ffffff;
}

.card-header p {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
  line-height: 1.5;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
}

.alert-danger {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #fca5a5;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #cbd5e1;
}

.form-group input {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 12px 16px;
  border-radius: 10px;
  color: #ffffff;
  font-size: 14px;
  transition: all 0.2s ease;
}

.form-group input:focus {
  outline: none;
  background: rgba(255, 255, 255, 0.1);
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.3);
}

.form-group input::placeholder {
  color: #64748b;
}

.btn-login {
  width: 100%;
  padding: 14px;
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(27, 77, 62, 0.3);
  display: flex;
  justify-content: center;
  align-items: center;
}

.flex-center-gap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.btn-login:hover {
  background: #13382D;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(27, 77, 62, 0.4);
}

.btn-login:disabled {
  background: #475569;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.card-footer {
  margin-top: 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 20px;
}

.back-link {
  color: #94a3b8;
  text-decoration: none;
  transition: color 0.2s ease;
}

.back-link:hover {
  color: #1B4D3E;
}

.copyright {
  color: #64748b;
}
</style>
