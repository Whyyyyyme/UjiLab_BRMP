<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { 
  AlertTriangle, 
  Loader2, 
  LogIn, 
  ArrowLeft, 
  User, 
  Lock, 
  Eye, 
  EyeOff, 
  ShieldCheck 
} from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()

// Form States
const username = ref('')
const password = ref('')
const rememberMe = ref(false)
const showPassword = ref(false)
const isCapsLock = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

// Load saved username if rememberMe was active
onMounted(() => {
  const savedIdentifier = localStorage.getItem('remembered_admin_identifier')
  if (savedIdentifier) {
    username.value = savedIdentifier
    rememberMe.value = true
  }
})

// Caps Lock Detector
const checkCapsLock = (event) => {
  if (event.getModifierState) {
    isCapsLock.value = event.getModifierState('CapsLock')
  }
}

// Handle Login
const handleLogin = async () => {
  if (!username.value || !password.value) {
    errorMessage.value = 'Silakan isi username atau email dan password.'
    return
  }
  
  isLoading.value = true
  errorMessage.value = ''
  
  const result = await authStore.login(username.value, password.value)
  
  isLoading.value = false
  if (result.success) {
    // Simpan atau hapus preferensi Remember Me
    if (rememberMe.value) {
      localStorage.setItem('remembered_admin_identifier', username.value.trim())
    } else {
      localStorage.removeItem('remembered_admin_identifier')
    }

    // Arahkan ke dashboard admin
    router.push({ name: 'AdminDashboard' })
  } else {
    errorMessage.value = result.message
  }
}
</script>

<template>
  <div class="login-page">
    <!-- Top Decorative Accent Bar (Kementan Green & Gold) -->
    <div class="top-accent-bar"></div>

    <div class="login-container">
      <div class="login-card">
        
        <!-- Header Identitas Resmi BRMP Biogen -->
        <header class="card-header">
          <div class="brand-logo-wrap">
            <img src="../../assets/logo-kementan.png" alt="Logo Kementerian Pertanian" class="kementan-logo" />
          </div>
          
          <div class="brand-titles">
            <span class="ministry-tag">KEMENTERIAN PERTANIAN REPUBLIK INDONESIA</span>
            <h1 class="portal-title">BRMP BIOGEN BOGOR</h1>
            <p class="portal-subtitle">Portal Pengujian Laboratorium</p>
          </div>

        </header>

        <!-- Form Login Utama -->
        <form @submit.prevent="handleLogin" class="login-form">
          
          <!-- Banner Pesan Error / Lockout -->
          <div v-if="errorMessage" class="alert-error" role="alert">
            <AlertTriangle :size="18" class="alert-icon" />
            <div class="alert-content">
              <span>{{ errorMessage }}</span>
            </div>
          </div>
          
          <!-- Input Field: Username atau Email -->
          <div class="form-group">
            <label for="username" class="input-label">Username atau Email</label>
            <div class="input-wrap">
              <User :size="18" class="input-lead-icon" />
              <input 
                type="text" 
                id="username" 
                v-model="username" 
                placeholder="Contoh: admin atau nama@gmail.com" 
                :disabled="isLoading"
                required
                autocomplete="username"
                class="form-input"
              />
            </div>
          </div>
          
          <!-- Input Field: Password dengan Toggle Show/Hide -->
          <div class="form-group">
            <label for="password" class="input-label">Password Akun</label>
            <div class="input-wrap">
              <Lock :size="18" class="input-lead-icon" />
              <input 
                :type="showPassword ? 'text' : 'password'" 
                id="password" 
                v-model="password" 
                placeholder="Masukkan kata sandi akun Anda" 
                :disabled="isLoading"
                required
                autocomplete="current-password"
                class="form-input has-toggle"
                @keyup="checkCapsLock"
                @keydown="checkCapsLock"
              />
              <button 
                type="button" 
                @click="showPassword = !showPassword" 
                class="password-toggle-btn"
                :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                tabindex="-1"
              >
                <EyeOff v-if="showPassword" :size="18" />
                <Eye v-else :size="18" />
              </button>
            </div>

            <!-- Peringatan Caps Lock Aktif -->
            <div v-if="isCapsLock" class="capslock-warning">
              <AlertTriangle :size="13" />
              <span>Peringatan: <strong>Caps Lock</strong> sedang menyala</span>
            </div>
          </div>

          <!-- Opsi Ingat Kredensial -->
          <div class="form-options">
            <label class="remember-label">
              <input 
                type="checkbox" 
                v-model="rememberMe" 
                :disabled="isLoading" 
                class="checkbox-input"
              />
              <span class="checkbox-text">Ingat username / email di perangkat ini</span>
            </label>
          </div>
          
          <!-- Tombol Submit Login Primer (Refactoring UI Focal Point) -->
          <button type="submit" class="btn-submit" :disabled="isLoading">
            <span v-if="isLoading" class="btn-inner">
              <Loader2 class="spinner" :size="18" />
              <span>Memverifikasi Akun...</span>
            </span>
            <span v-else class="btn-inner">
              <span>Masuk ke Dashboard</span>
              <LogIn :size="18" />
            </span>
          </button>
        </form>
        
        <!-- Footer Navigasi & Security Stamp -->
        <footer class="card-footer">
          <router-link to="/" class="back-link">
            <ArrowLeft :size="14" />
            <span>Kembali ke Pencarian Publik</span>
          </router-link>
          
          <div class="security-stamp">
            <ShieldCheck :size="13" />
            <span>Koneksi Aman SSL/TLS Terenkripsi</span>
          </div>
        </footer>

      </div>

      <!-- Micro Copyright Note -->
      <div class="bottom-copyright">
        <span>&copy; 2026 Balai Besar Perakitan &amp; Modernisasi Bioteknologi Pertanian (BRMP Biogen)</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ==========================================================================
   PAGE SETUP & BACKGROUND (Concept A: Clean Institutional Center Card)
   ========================================================================== */
.login-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f8fafc;
  background-image: 
    radial-gradient(at 0% 0%, rgba(27, 77, 62, 0.06) 0px, transparent 50%),
    radial-gradient(at 100% 100%, rgba(234, 179, 8, 0.05) 0px, transparent 50%);
  padding: 40px 16px;
  font-family: var(--font-sans);
  color: #0f172a;
  position: relative;
  box-sizing: border-box;
}

/* Top Accent Bar (Kementan Green & Gold Brand Identity) */
.top-accent-bar {
  height: 4px;
  width: 100%;
  background: linear-gradient(90deg, #1B4D3E 0%, #246B56 70%, #EAB308 100%);
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1000;
}

.login-container {
  width: 100%;
  max-width: 440px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
}

/* ==========================================================================
   CENTER CARD (Crisp White with Soft Depth Elevation)
   ========================================================================== */
.login-card {
  width: 100%;
  background: #ffffff;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 
    0 10px 30px -5px rgba(15, 23, 42, 0.06), 
    0 4px 16px -2px rgba(27, 77, 62, 0.04);
  padding: 36px 32px;
  box-sizing: border-box;
  animation: cardEnter 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes cardEnter {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ==========================================================================
   HEADER IDENTITAS RESMI
   ========================================================================== */
.card-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 24px;
}

.brand-logo-wrap {
  margin-bottom: 12px;
}

.kementan-logo {
  height: 60px;
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.08));
}

.brand-titles {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-bottom: 12px;
}

.ministry-tag {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.8px;
  color: #64748b;
  text-transform: uppercase;
}

.portal-title {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  color: #1B4D3E;
  letter-spacing: -0.3px;
}

.portal-subtitle {
  margin: 0;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
}

.access-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: 11.5px;
  font-weight: 700;
  letter-spacing: 0.2px;
}

.pill-icon {
  color: #15803d;
}

/* ==========================================================================
   FORM CONTROLS & INPUT AFFORDANCE
   ========================================================================== */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* Alert Error */
.alert-error {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  padding: 12px 14px;
  border-radius: 12px;
  font-size: 13px;
  line-height: 1.45;
  animation: shake 0.3s ease-in-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}

.alert-icon {
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-content {
  flex: 1;
}

/* Form Group & Input */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.input-label {
  font-size: 12.5px;
  font-weight: 700;
  color: #334155;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-lead-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  pointer-events: none;
  transition: color 0.2s ease;
}

.form-input {
  width: 100%;
  box-sizing: border-box;
  background: #f8fafc;
  border: 1.5px solid #cbd5e1;
  border-radius: 12px;
  padding: 12px 14px 12px 42px;
  font-size: 14px;
  color: #0f172a;
  outline: none;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.form-input.has-toggle {
  padding-right: 44px;
}

.form-input::placeholder {
  color: #94a3b8;
  font-size: 13px;
}

.form-input:focus {
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.12);
}

.input-wrap:focus-within .input-lead-icon {
  color: #1B4D3E;
}

/* Password Toggle Button */
.password-toggle-btn {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.15s ease;
}

.password-toggle-btn:hover {
  color: #0f172a;
  background: #f1f5f9;
}

/* Caps Lock Warning */
.capslock-warning {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fef3c7;
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 11.5px;
  margin-top: 2px;
}

/* Remember Me Checkbox */
.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.remember-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
}

.checkbox-input {
  width: 16px;
  height: 16px;
  accent-color: #1B4D3E;
  cursor: pointer;
}

.checkbox-text {
  font-size: 12.5px;
  color: #475569;
}

/* ==========================================================================
   PRIMARY ACTION BUTTON (Refactoring UI Focal Point)
   ========================================================================== */
.btn-submit {
  width: 100%;
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  padding: 13px 20px;
  border-radius: 12px;
  font-size: 14.5px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(27, 77, 62, 0.25);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  margin-top: 4px;
}

.btn-inner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:hover:not(:disabled) {
  background: #13392E;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(27, 77, 62, 0.35);
}

.btn-submit:active:not(:disabled) {
  transform: translateY(0);
}

.btn-submit:disabled {
  background: #94a3b8;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ==========================================================================
   CARD FOOTER & SECURITY NOTE
   ========================================================================== */
.card-footer {
  margin-top: 24px;
  padding-top: 18px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.back-link {
  color: #475569;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  transition: all 0.15s ease;
}

.back-link:hover {
  color: #1B4D3E;
  background: #f1f5f9;
}

.security-stamp {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  color: #94a3b8;
}

.bottom-copyright {
  text-align: center;
  font-size: 11.5px;
  color: #94a3b8;
  line-height: 1.4;
}

/* ==========================================================================
   RESPONSIVE DESIGN (MOBILE ADAPTATION)
   ========================================================================== */
@media (max-width: 480px) {
  .login-card {
    padding: 28px 20px;
    border-radius: 16px;
  }

  .portal-title {
    font-size: 18px;
  }

  .form-input {
    font-size: 14px;
    padding: 11px 12px 11px 38px;
  }

  .input-lead-icon {
    left: 12px;
  }
}
</style>
