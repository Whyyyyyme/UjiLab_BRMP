<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { Lock, AlertTriangle, CheckCircle2, Save, Loader2 } from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()

const passwordLama = ref('')
const passwordBaru = ref('')
const passwordBaruConfirmation = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const handleGantiPassword = async () => {
  if (passwordBaru.value !== passwordBaruConfirmation.value) {
    errorMessage.value = 'Konfirmasi password baru tidak cocok.'
    return
  }
  
  if (passwordBaru.value.length < 8) {
    errorMessage.value = 'Password baru minimal harus 8 karakter.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/api/admin/ganti-password', {
      password_lama: passwordLama.value,
      password_baru: passwordBaru.value,
      password_baru_confirmation: passwordBaruConfirmation.value
    })
    
    successMessage.value = response.data.message || 'Password berhasil diperbarui!'
    
    // Update state di Pinia store
    if (authStore.user) {
      authStore.user.wajib_ganti_password = false
      authStore.setUser(authStore.user)
    }

    setTimeout(() => {
      router.push({ name: 'AdminDashboard' })
    }, 2000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengubah password. Silakan periksa kembali data Anda.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="change-password-page">
    <div class="card shadow-lg">
      <div class="card-header text-center">
        <div class="icon-wrapper">
          <Lock :size="32" class="icon-lock" />
        </div>
        <h3>Ubah Password Akun</h3>
        
        <div v-if="authStore.user?.wajib_ganti_password" class="warning-banner">
          <AlertTriangle :size="18" class="warning-icon" />
          <div class="warning-text">
            <strong>Perhatian:</strong> Akun Anda baru saja dibuat/direset. Anda wajib mengubah password default sebelum dapat melanjutkan ke sistem.
          </div>
        </div>
        <p v-else class="header-desc">Untuk keamanan akun, silakan ubah password Anda secara berkala.</p>
      </div>

      <form @submit.prevent="handleGantiPassword" class="password-form">
        <div v-if="errorMessage" class="alert alert-danger">
          <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
        </div>
        
        <div v-if="successMessage" class="alert alert-success">
          <CheckCircle2 :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ successMessage }} - dialihkan ke dashboard...
        </div>

        <div class="form-group">
          <label for="password_lama">Password Lama</label>
          <input 
            type="password" 
            id="password_lama" 
            v-model="passwordLama" 
            placeholder="Masukkan password saat ini" 
            :disabled="isLoading"
            required
            autocomplete="current-password"
          />
        </div>

        <div class="form-group">
          <label for="password_baru">Password Baru</label>
          <input 
            type="password" 
            id="password_baru" 
            v-model="passwordBaru" 
            placeholder="Minimal 8 karakter" 
            :disabled="isLoading"
            required
            autocomplete="new-password"
          />
        </div>

        <div class="form-group">
          <label for="password_baru_confirm">Konfirmasi Password Baru</label>
          <input 
            type="password" 
            id="password_baru_confirm" 
            v-model="passwordBaruConfirmation" 
            placeholder="Ulangi password baru" 
            :disabled="isLoading"
            required
            autocomplete="new-password"
          />
        </div>

        <button type="submit" class="btn-submit" :disabled="isLoading">
          <span v-if="isLoading" class="flex-icon-center"><Loader2 class="animate-spin" :size="16" /> Menyimpan...</span>
          <span v-else class="flex-icon-center"><Save :size="16" /> Simpan Password Baru</span>
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.change-password-page {
  max-width: 480px;
  margin: 30px auto;
  font-family: 'Inter', sans-serif;
}

.card {
  background: #ffffff;
  padding: 36px 32px;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  border-top: 4px solid #1B4D3E;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
}

.card-header {
  text-align: center;
  margin-bottom: 24px;
}

.icon-wrapper {
  background: rgba(27, 77, 62, 0.08);
  color: #1B4D3E;
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin: 0 auto 16px auto;
}

.icon-lock {
  color: #1B4D3E;
}

.card-header h3 {
  margin: 0 0 8px 0;
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
}

.header-desc {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
}

.warning-banner {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
  padding: 14px 16px;
  border-radius: 12px;
  font-size: 13px;
  line-height: 1.5;
  margin-top: 14px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  text-align: left;
}

.warning-icon {
  flex-shrink: 0;
  margin-top: 2px;
  color: #d97706;
}

.warning-text strong {
  color: #78350f;
}

.password-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 500;
  line-height: 1.5;
}

.alert-danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.alert-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.form-group input {
  padding: 12px 16px;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: 14px;
  color: #1e293b;
  outline: none;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.form-group input:focus {
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
}

.btn-submit {
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  padding: 14px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 14px rgba(27, 77, 62, 0.25);
  margin-top: 8px;
}

.btn-submit:hover:not(:disabled) {
  background: #13382D;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(27, 77, 62, 0.35);
}

.btn-submit:disabled {
  background: #94a3b8;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.flex-icon-center {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
