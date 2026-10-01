<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { useAksesPublikStore } from '../../stores/aksesPublik'
import { 
  AlertTriangle, 
  CheckCircle2, 
  Send, 
  Check, 
  RefreshCw,
  ShieldCheck,
  Lock,
  Mail,
  ArrowLeft,
  Clock
} from '@lucide/vue'

const route = useRoute()
const router = useRouter()
const aksesPublikStore = useAksesPublikStore()

// State
const emailTersamar = ref(route.query.email || '')
const pengujianId = ref(aksesPublikStore.pengujianId)
const nomorPengujian = ref(aksesPublikStore.nomorPengujian)

// State OTP 6-Digit Box (UX 1 B)
const otpDigits = ref(['', '', '', '', '', ''])
const inputRefs = ref([])

const otpKode = computed(() => otpDigits.value.join(''))

const isLoading = ref(false)
const isOtpSent = ref(false)
const cooldownTime = ref(0)
const errorMessage = ref('')
const successMessage = ref('')

let cooldownInterval = null

// Format waktu MM:SS
const formatTime = (seconds) => {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
}

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
  if (isLoading.value) return
  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/api/public/otp/kirim', {
      pengujian_id: pengujianId.value,
      nomor_pengujian: nomorPengujian.value
    })
    
    isOtpSent.value = true
    successMessage.value = response.data.message || 'Kode OTP telah berhasil dikirimkan ke email Anda.'
    startCooldown()
    
    // Auto focus ke kotak pertama
    setTimeout(() => {
      inputRefs.value[0]?.focus()
    }, 100)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengirim kode OTP. Silakan coba kembali.'
  } finally {
    isLoading.value = false
  }
}

// Verify OTP
const handleVerifyOtp = async () => {
  if (isLoading.value) return
  if (otpKode.value.length !== 6) {
    errorMessage.value = 'Kode OTP harus berjumlah 6 digit angka.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/api/public/otp/verifikasi', {
      pengujian_id: pengujianId.value,
      nomor_pengujian: nomorPengujian.value,
      kode: otpKode.value
    })

    const token = response.data.token
    aksesPublikStore.setAkses(token, pengujianId.value, nomorPengujian.value)

    successMessage.value = 'Verifikasi berhasil! Mengalihkan ke dokumen...'

    const statusResponse = await api.get('/api/public/pengujian/status')
    aksesPublikStore.setSkmFilled(statusResponse.data.skm_diisi)

    setTimeout(() => {
      if (statusResponse.data.skm_diisi) {
        router.push({ name: 'HasilUnduh' })
      } else {
        router.push({ name: 'FormSkm' })
      }
    }, 1200)

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Verifikasi OTP gagal. Periksa kembali kode Anda.'
  } finally {
    isLoading.value = false
  }
}

// Handler Input Per-Digit
const onDigitInput = (index, event) => {
  const val = event.target.value.replace(/\D/g, '')
  
  if (val.length > 1) {
    const chars = val.slice(0, 6).split('')
    chars.forEach((c, i) => {
      if (index + i < 6) {
        otpDigits.value[index + i] = c
      }
    })
    const nextIdx = Math.min(index + chars.length, 5)
    inputRefs.value[nextIdx]?.focus()
    return
  }

  otpDigits.value[index] = val

  if (val && index < 5) {
    inputRefs.value[index + 1]?.focus()
  }

  // Jika 6 digit terisi penuh, otomatis verifikasi
  if (otpKode.value.length === 6 && !isLoading.value) {
    handleVerifyOtp()
  }
}

// Handler Navigasi Keyboard
const onKeyDown = (index, event) => {
  if (event.key === 'Backspace') {
    if (!otpDigits.value[index] && index > 0) {
      otpDigits.value[index - 1] = ''
      inputRefs.value[index - 1]?.focus()
    } else {
      otpDigits.value[index] = ''
    }
  } else if (event.key === 'ArrowLeft' && index > 0) {
    inputRefs.value[index - 1]?.focus()
  } else if (event.key === 'ArrowRight' && index < 5) {
    inputRefs.value[index + 1]?.focus()
  }
}

// Handler Paste kode OTP dari Clipboard
const onPaste = (event) => {
  event.preventDefault()
  const pastedData = (event.clipboardData || window.clipboardData)
    .getData('text')
    .replace(/\D/g, '')
    .slice(0, 6)

  if (!pastedData) return

  const digits = pastedData.split('')
  for (let i = 0; i < 6; i++) {
    otpDigits.value[i] = digits[i] || ''
  }

  const focusIndex = Math.min(pastedData.length, 5)
  inputRefs.value[focusIndex]?.focus()

  if (otpKode.value.length === 6 && !isLoading.value) {
    handleVerifyOtp()
  }
}
</script>

<template>
  <div class="portal-page">
    <div class="portal-card-wrapper">
      
      <!-- SISI KIRI: BRANDING & KEAMANAN AKSES (KONSISTEN DENGAN CARI PENGUJIAN) -->
      <div class="branding-pane">
        <div class="pane-content">
          <!-- Logo & Nama Instansi -->
          <div class="brand-header">
            <img src="../../assets/logo-kementan.png" alt="Logo Kementerian Pertanian" class="kementan-logo" />
            <div class="brand-text">
              <span class="ministry-tag">KEMENTERIAN PERTANIAN</span>
              <span class="agency-title">BRMP BIOGEN</span>
            </div>
          </div>

          <!-- Headline Ringkas -->
          <div class="hero-text-block">
            <h1 class="portal-heading">Verifikasi Keamanan Akses</h1>
            <p class="portal-subheading">
              Sistem perlindungan kode OTP menjamin dokumen laporan hasil pengujian hanya dapat diakses oleh pemohon yang sah.
            </p>
          </div>

          <!-- 3 Poin Keamanan -->
          <div class="feature-pills">
            <div class="feature-pill">
              <Lock :size="18" class="pill-icon" />
              <span>Kode OTP 6 Digit Dinamis</span>
            </div>
            <div class="feature-pill">
              <Mail :size="18" class="pill-icon" />
              <span>Terkirim Langsung ke Email Resmi</span>
            </div>
            <div class="feature-pill">
              <ShieldCheck :size="18" class="pill-icon" />
              <span>Kepatuhan Standar Privasi Data</span>
            </div>
          </div>
        </div>

        <!-- Footer Ganti Nomor -->
        <div class="pane-footer">
          <span class="pane-footer-text">Salah memasukkan nomor pengujian?</span>
          <router-link :to="{ name: 'CariPengujian' }" class="change-link">
            <ArrowLeft :size="13" class="change-link-icon" />
            <span>Ganti Nomor</span>
          </router-link>
        </div>
      </div>

      <!-- SISI KANAN: FORM VERIFIKASI OTP INTERAKTIF -->
      <div class="otp-pane">
        <div class="otp-box-content">
          
          <!-- Step Indicator Ringkas -->
          <div class="step-indicator-bar">
            <span class="step-badge">Langkah 2 dari 3</span>
            <span class="step-label">Verifikasi Kode Pengamanan</span>
          </div>

          <!-- Judul Form -->
          <div class="form-title-group">
            <h2 class="form-title">Verifikasi Identitas</h2>
            <p class="form-desc">
              Kode verifikasi keamanan dikirimkan ke alamat email pemohon yang terdaftar.
            </p>
          </div>

          <!-- Info Box: Nomor & Email -->
          <div class="target-info-card">
            <div class="info-item">
              <span class="info-label">Nomor Pengujian</span>
              <div class="info-val-row">
                <span class="info-val-badge">{{ nomorPengujian }}</span>
                <!-- <router-link :to="{ name: 'CariPengujian' }" class="btn-change-number" title="Ubah Nomor Pengujian">
                  <ArrowLeft :size="11" />
                  <span>Ganti</span>
                </router-link> -->
              </div>
            </div>
            <div class="info-divider"></div>
            <div class="info-item">
              <span class="info-label">Email Terdaftar</span>
              <span class="info-val-email">{{ emailTersamar }}</span>
            </div>
          </div>

          <!-- Alert Error & Success -->
          <div v-if="errorMessage" class="alert-error">
            <AlertTriangle :size="18" class="alert-icon" />
            <span>{{ errorMessage }}</span>
          </div>

          <div v-if="successMessage" class="alert-success">
            <CheckCircle2 :size="18" class="alert-icon" />
            <span>{{ successMessage }}</span>
          </div>

          <!-- STEP 1: Belum Kirim OTP -->
          <div v-if="!isOtpSent" class="otp-initial-box">
            <p class="initial-instruction">
              Demi keamanan dan pelindungan data hasil uji, silakan klik tombol di bawah untuk mengirim kode OTP ke email Anda.
            </p>
            <button 
              type="button" 
              @click="handleSendOtp" 
              class="submit-action-btn" 
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="loading-state-box">
                <span class="spinner-ring"></span>
                <span>Mengirim Kode OTP...</span>
              </span>
              <span v-else class="btn-text-wrap">
                <Send :size="16" />
                <span>Kirim Kode OTP ke Email</span>
              </span>
            </button>
          </div>

          <!-- STEP 2: Input 6-Digit OTP Box -->
          <form v-else @submit.prevent="handleVerifyOtp" class="main-form">
            <div class="form-field">
              <label class="otp-input-label">Masukkan 6 Digit Kode OTP</label>
              
              <!-- 6-Segmented Input Boxes -->
              <div class="otp-segmented-wrapper" @paste="onPaste">
                <input 
                  v-for="(digit, index) in otpDigits" 
                  :key="index"
                  :ref="el => inputRefs[index] = el"
                  type="text" 
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="1"
                  :value="digit"
                  @input="onDigitInput(index, $event)"
                  @keydown="onKeyDown(index, $event)"
                  class="otp-digit-cell"
                  :class="{ 'filled': digit !== '' }"
                  :disabled="isLoading"
                  autocomplete="one-time-code"
                />
              </div>
            </div>

            <!-- Submit Button -->
            <button 
              type="submit" 
              class="submit-action-btn" 
              :disabled="isLoading || otpKode.length !== 6"
            >
              <span v-if="isLoading" class="loading-state-box">
                <span class="spinner-ring"></span>
                <span>Memverifikasi Kode...</span>
              </span>
              <span v-else class="btn-text-wrap">
                <Check :size="18" />
                <span>Verifikasi &amp; Lanjutkan</span>
              </span>
            </button>

            <!-- Cooldown / Resend Button -->
            <div class="cooldown-row">
              <span v-if="cooldownTime > 0" class="cooldown-active">
                <Clock :size="14" />
                <span>Kirim ulang kode dalam <strong>{{ formatTime(cooldownTime) }}</strong></span>
              </span>
              <button 
                v-else 
                type="button" 
                @click="handleSendOtp" 
                class="resend-action-btn" 
                :disabled="isLoading"
              >
                <RefreshCw :size="14" />
                <span>Kirim Ulang Kode OTP</span>
              </button>
            </div>
          </form>

          <!-- Security Note -->
          <div class="card-security-note">
            <ShieldCheck :size="14" />
            <span>Kode verifikasi berlaku 10 menit &bull; BRMP Biogen</span>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Base Page Setup */
.portal-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 20px;
  background-color: #f8fafc;
  background-image: radial-gradient(at 100% 0%, rgba(27, 77, 62, 0.04) 0px, transparent 50%),
                    radial-gradient(at 0% 100%, rgba(234, 179, 8, 0.05) 0px, transparent 50%);
  font-family: var(--font-sans);
  box-sizing: border-box;
}

/* Split-Screen Master Container */
.portal-card-wrapper {
  width: 100%;
  max-width: 1040px;
  min-height: 560px;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06), 0 4px 12px -2px rgba(15, 23, 42, 0.03);
  border: 1px solid #e2e8f0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  overflow: hidden;
}

/* =========================================================
   SISI KIRI: BRANDING MINIMALIS
   ========================================================= */
.branding-pane {
  background: linear-gradient(155deg, #1B4D3E 0%, #13392E 100%);
  color: #ffffff;
  padding: 48px 40px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
}

.branding-pane::after {
  content: '';
  position: absolute;
  top: -80px;
  right: -80px;
  width: 240px;
  height: 240px;
  background: radial-gradient(circle, rgba(234, 179, 8, 0.15) 0%, transparent 70%);
  pointer-events: none;
}

.pane-content {
  display: flex;
  flex-direction: column;
  gap: 28px;
  position: relative;
  z-index: 1;
}

.brand-header {
  display: flex;
  align-items: center;
  gap: 14px;
}

.kementan-logo {
  height: 52px;
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2));
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.ministry-tag {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.8px;
  color: rgba(255, 255, 255, 0.7);
  text-transform: uppercase;
}

.agency-title {
  font-size: 19px;
  font-weight: 800;
  letter-spacing: 0.5px;
  color: #ffffff;
}

.hero-text-block {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.portal-heading {
  font-size: 26px;
  font-weight: 800;
  line-height: 1.25;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.4px;
}

.portal-subheading {
  font-size: 14.5px;
  color: rgba(255, 255, 255, 0.82);
  line-height: 1.6;
  margin: 0;
}

/* Feature Pills */
.feature-pills {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 4px;
}

.feature-pill {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(4px);
}

.pill-icon {
  color: #facc15;
  flex-shrink: 0;
}

.pane-footer {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
  border-top: 1px solid rgba(255, 255, 255, 0.12);
  padding-top: 16px;
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  line-height: 1.4;
}

.pane-footer-text {
  display: inline-flex;
  align-items: center;
}

.change-link {
  color: #facc15;
  text-decoration: none;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  line-height: 1;
  padding: 3px 8px;
  border-radius: 6px;
  background: rgba(250, 204, 21, 0.1);
  border: 1px solid rgba(250, 204, 21, 0.22);
  transition: all 0.2s ease;
}

.change-link:hover {
  background: rgba(250, 204, 21, 0.2);
  color: #fef08a;
  border-color: rgba(250, 204, 21, 0.35);
}

.change-link-icon {
  display: block;
  flex-shrink: 0;
}

/* =========================================================
   SISI KANAN: FORM VERIFIKASI OTP
   ========================================================= */
.otp-pane {
  padding: 48px 44px;
  display: flex;
  align-items: center;
  background: #ffffff;
}

.otp-box-content {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

/* Step Indicator */
.step-indicator-bar {
  display: flex;
  align-items: center;
  gap: 10px;
}

.step-badge {
  background: rgba(27, 77, 62, 0.1);
  color: #1B4D3E;
  font-size: 11.5px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.step-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

/* Form Title Group */
.form-title-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-title {
  font-size: 23px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.3px;
}

.form-desc {
  font-size: 14px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

/* Target Info Card */
.target-info-card {
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 16px;
  gap: 14px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}

.info-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-val-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.info-val-badge {
  font-size: 14px;
  font-weight: 800;
  color: #1B4D3E;
  letter-spacing: 0.3px;
}

.btn-change-number {
  font-size: 11px;
  font-weight: 700;
  color: #1B4D3E;
  background: rgba(27, 77, 62, 0.08);
  border: 1px solid rgba(27, 77, 62, 0.2);
  padding: 2px 7px;
  border-radius: 6px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 3px;
  transition: all 0.2s ease;
  line-height: 1.2;
}

.btn-change-number:hover {
  background: rgba(27, 77, 62, 0.15);
  color: #13392E;
  border-color: #1B4D3E;
}

.info-val-email {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  font-family: var(--font-mono);
}

.info-divider {
  width: 1px;
  height: 28px;
  background: #cbd5e1;
}

/* Alerts */
.alert-error {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fef2f2;
  border: 1px solid #fee2e2;
  color: #991b1b;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 13.5px;
  line-height: 1.4;
}

.alert-success {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f0fdf4;
  border: 1px solid #dcfce7;
  color: #166534;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 13.5px;
  line-height: 1.4;
}

.alert-icon {
  flex-shrink: 0;
}

/* Step 1 Initial Box */
.otp-initial-box {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.initial-instruction {
  font-size: 13.5px;
  color: #475569;
  line-height: 1.6;
  margin: 0;
}

/* Step 2 Form */
.main-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.otp-input-label {
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
}

/* 6-Segmented OTP Container */
.otp-segmented-wrapper {
  display: flex;
  justify-content: space-between;
  gap: 8px;
}

.otp-digit-cell {
  width: 50px;
  height: 56px;
  border: 1.8px solid #cbd5e1;
  border-radius: 12px;
  text-align: center;
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  background: #f8fafc;
  outline: none;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-sizing: border-box;
}

.otp-digit-cell:focus {
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
  transform: translateY(-2px);
}

.otp-digit-cell.filled {
  border-color: #1B4D3E;
  background: #ffffff;
  color: #1B4D3E;
}

/* Action Primary Button */
.submit-action-btn {
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  padding: 14px 20px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(27, 77, 62, 0.25);
  width: 100%;
}

.submit-action-btn:hover:not(:disabled) {
  background: #13392E;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(27, 77, 62, 0.35);
}

.submit-action-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-text-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.loading-state-box {
  display: flex;
  align-items: center;
  gap: 8px;
}

.spinner-ring {
  width: 15px;
  height: 15px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Cooldown Area */
.cooldown-row {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 24px;
}

.cooldown-active {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #64748b;
}

.cooldown-active strong {
  color: #0f172a;
}

.resend-action-btn {
  background: none;
  border: none;
  color: #1B4D3E;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.resend-action-btn:hover {
  background: rgba(27, 77, 62, 0.08);
  text-decoration: underline;
}

/* Card Security Note */
.card-security-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 11.5px;
  color: #94a3b8;
  padding-top: 6px;
}

/* =========================================================
   RESPONSIVE LAYOUT (TABLET & MOBILE)
   ========================================================= */
@media (max-width: 900px) {
  .portal-card-wrapper {
    grid-template-columns: 1fr;
    max-width: 520px;
    min-height: auto;
  }

  .branding-pane {
    padding: 32px 28px;
    gap: 20px;
  }

  .portal-heading {
    font-size: 22px;
  }

  .portal-subheading {
    font-size: 13.5px;
  }

  .feature-pills {
    display: none;
  }

  .otp-pane {
    padding: 36px 28px;
  }

  .form-title {
    font-size: 20px;
  }

  .otp-digit-cell {
    width: 44px;
    height: 50px;
    font-size: 20px;
  }
}

@media (max-width: 480px) {
  .portal-page {
    padding: 16px 12px;
  }

  .branding-pane {
    padding: 24px 20px;
  }

  .otp-pane {
    padding: 28px 20px;
  }

  .agency-title {
    font-size: 17px;
  }

  .target-info-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .info-divider {
    display: none;
  }

  .otp-digit-cell {
    width: 38px;
    height: 46px;
    font-size: 18px;
    border-radius: 8px;
  }

  .submit-action-btn {
    padding: 13px;
    font-size: 14px;
  }
}
</style>
