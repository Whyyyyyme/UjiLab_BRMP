<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { useAksesPublikStore } from '../../stores/aksesPublik'
import { AlertTriangle, CheckCircle2, Send, Check, RefreshCw } from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const aksesPublikStore = useAksesPublikStore()

// State
const emailTersamar = ref(route.query.email || '')
const pengujianId = ref(aksesPublikStore.pengujianId)
const nomorPengujian = ref(aksesPublikStore.nomorPengujian)

const otpKode = ref('')
const isOtpSent = ref(false)
const isLoading = ref(false)
const cooldownTime = ref(0)
const errorMessage = ref('')
const successMessage = ref('')

let cooldownInterval = null

// Proteksi jika data pengujian kosong (akses langsung halaman ini)
onMounted(() => {
  if (!pengujianId.value || !nomorPengujian.value) {
    router.replace({ name: 'CariPengujian' })
  }
})

onBeforeUnmount(() => {
  clearInterval(cooldownInterval)
})

// Start Cooldown Timer
const startCooldown = () => {
  cooldownTime.value = 60
  clearInterval(cooldownInterval)
  cooldownInterval = setInterval(() => {
    if (cooldownTime.value > 0) {
      cooldownTime.value--
    } else {
      clearInterval(cooldownInterval)
    }
  }, 1000)
}

// Request OTP
const handleSendOtp = async () => {
  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/api/public/otp/kirim', {
      pengujian_id: pengujianId.value
    })
    
    isOtpSent.value = true
    successMessage.value = response.data.message
    startCooldown()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengirim kode OTP.'
  } finally {
    isLoading.value = false
  }
}

// Verify OTP
const handleVerifyOtp = async () => {
  if (otpKode.value.length !== 6) {
    errorMessage.value = 'Kode OTP harus berjumlah 6 digit.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/api/public/otp/verifikasi', {
      pengujian_id: pengujianId.value,
      kode: otpKode.value
    })

    // Sesi token di-simpan dalam memory (Pinia) untuk keamanan (tidak di localStorage)
    const token = response.data.token
    
    // Set token ke Axios/Pinia
    aksesPublikStore.setAkses(token, pengujianId.value, nomorPengujian.value)

    successMessage.value = 'Verifikasi berhasil! Mengalihkan...'

    // Panggil status check
    const statusResponse = await api.get('/api/public/pengujian/status')
    aksesPublikStore.setSkmFilled(statusResponse.data.skm_diisi)

    setTimeout(() => {
      if (statusResponse.data.skm_diisi) {
        router.push({ name: 'HasilUnduh' })
      } else {
        router.push({ name: 'FormSkm' })
      }
    }, 1500)

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Verifikasi OTP gagal.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="public-container">
    <div class="landing-card">
      <div class="brand">
        <img src="../../assets/logo-kementan.png" alt="Logo Kementan" class="brand-logo" />
        <h2>BRMP BIOGEN</h2>
        <p class="brand-sub">Verifikasi Kode Pengamanan</p>
      </div>

      <h1 class="page-title">Verifikasi OTP</h1>
      
      <div class="info-box">
        <div class="info-row">
          <span class="info-label">Nomor Pengujian</span>
          <span class="info-value text-primary">{{ nomorPengujian }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Email Pemohon</span>
          <span class="info-value">{{ emailTersamar }}</span>
        </div>
      </div>

      <div v-if="errorMessage" class="alert alert-danger">
        <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
      </div>

      <div v-if="successMessage" class="alert alert-success">
        <CheckCircle2 :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ successMessage }}
      </div>

      <!-- Step 1: Kirim OTP -->
      <div v-if="!isOtpSent" class="action-box">
        <p class="step-desc">Demi alasan keamanan dan pelindungan data pribadi pemohon, kami perlu mengirimkan kode keamanan OTP ke email yang terdaftar pada sampel pengujian ini.</p>
        <button @click="handleSendOtp" class="btn-primary btn-full" :disabled="isLoading">
          <span v-if="isLoading">Mengirim Kode...</span>
          <span v-else class="flex-icon-center"><Send :size="16" /> Kirim Kode OTP</span>
        </button>
      </div>

      <!-- Step 2: Input OTP -->
      <form v-else @submit.prevent="handleVerifyOtp" class="otp-form">
        <p class="step-desc">Masukkan 6 digit kode OTP yang kami kirimkan ke email Anda. Kode ini berlaku selama 10 menit.</p>
        
        <div class="form-group">
          <input 
            type="text" 
            v-model="otpKode" 
            maxlength="6" 
            placeholder="0 0 0 0 0 0" 
            class="otp-input"
            required
            pattern="[0-9]{6}"
            inputmode="numeric"
            :disabled="isLoading"
          />
        </div>

        <button type="submit" class="btn-primary btn-full" :disabled="isLoading">
          <span v-if="isLoading">Memverifikasi...</span>
          <span v-else class="flex-icon-center"><Check :size="16" /> Verifikasi &amp; Lanjutkan</span>
        </button>

        <div class="cooldown-area">
          <span v-if="cooldownTime > 0" class="cooldown-text">
            Kirim ulang kode dalam <strong>{{ cooldownTime }} detik</strong>
          </span>
          <button 
            v-else 
            type="button" 
            @click="handleSendOtp" 
            class="btn-text flex-icon-center" 
            :disabled="isLoading"
          >
            <RefreshCw :size="14" /> Kirim Ulang OTP
          </button>
        </div>
      </form>

      <button @click="router.push({ name: 'CariPengujian' })" class="btn-back" :disabled="isLoading">
        ← Ganti Nomor Pengujian
      </button>
    </div>
  </div>
</template>

<style scoped>
.public-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at 10% 20%, rgba(243, 244, 246, 1) 0%, rgba(229, 231, 235, 1) 90%);
  padding: 20px;
}

.landing-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  width: 100%;
  max-width: 480px;
  padding: 40px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(226, 232, 240, 0.8);
  animation: cardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes cardEnter {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.brand {
  text-align: center;
  margin-bottom: 24px;
}

.brand-logo {
  height: 64px;
  object-fit: contain;
  display: inline-block;
  margin-bottom: 8px;
}

.brand h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: 1px;
}

.brand-sub {
  margin: 4px 0 0 0;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.page-title {
  font-size: 22px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 20px 0;
  text-align: center;
}

.info-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 16px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 24px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  font-size: 13px;
}

.info-label {
  color: #64748b;
  font-weight: 500;
  white-space: nowrap;
}

.info-value {
  color: #1e293b;
  font-weight: 600;
  word-break: break-all;
  text-align: right;
}

.info-value.text-primary {
  color: #1B4D3E;
}

.alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  line-height: 1.5;
  margin-bottom: 20px;
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

.action-box {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.step-desc {
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
  text-align: center;
  margin: 0 0 16px 0;
}

.otp-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.otp-input {
  width: 100%;
  box-sizing: border-box;
  padding: 14px 10px;
  border: 1.5px solid #cbd5e1;
  border-radius: 12px;
  font-size: 24px;
  font-weight: 800;
  letter-spacing: 10px;
  text-align: center;
  color: #1e293b;
  outline: none;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.otp-input::placeholder {
  letter-spacing: 6px;
  color: #cbd5e1;
}

.otp-input:focus {
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 4px rgba(27, 77, 62, 0.15);
}

.btn-primary {
  background: #1B4D3E;
  color: white;
  border: none;
  padding: 14px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(27, 77, 62, 0.25);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-primary:hover:not(:disabled) {
  background: #13382D;
  transform: translateY(-1px);
}

.btn-primary:disabled {
  background: #94a3b8;
  box-shadow: none;
  cursor: not-allowed;
}

.cooldown-area {
  text-align: center;
  margin-top: 4px;
}

.cooldown-text {
  font-size: 13px;
  color: #64748b;
}

.btn-text {
  background: none;
  border: none;
  color: #1B4D3E;
  font-weight: 600;
  cursor: pointer;
  font-size: 13px;
  padding: 0;
  transition: color 0.2s ease;
}

.btn-text:hover {
  color: #13382D;
  text-decoration: underline;
}

.btn-back {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  margin-top: 24px;
  width: 100%;
  text-align: center;
  transition: color 0.2s ease;
}

.btn-back:hover {
  color: #1e293b;
}

.flex-icon-center {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

@media (max-width: 640px) {
  .public-container {
    padding: 16px 12px;
  }
  .landing-card {
    padding: 24px 18px;
    border-radius: 20px;
  }
  .brand {
    margin-bottom: 16px;
  }
  .brand-logo {
    height: 52px;
  }
  .page-title {
    font-size: 18px;
  }
  .otp-input {
    font-size: 18px;
    letter-spacing: 6px;
    padding: 12px 6px;
  }
}
</style>
