<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAksesPublikStore } from '../../stores/aksesPublik'
import { 
  AlertTriangle, 
  Search, 
  X, 
  ArrowRight, 
  ShieldCheck, 
  Lock, 
  FileCheck,
  HelpCircle,
  MessageCircle
} from '@lucide/vue'

const router = useRouter()
const aksesPublikStore = useAksesPublikStore()

const nomorPengujian = ref('')
const persetujuanPdp = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const showHelp = ref(false)

const handleSearch = async () => {
  if (!persetujuanPdp.value) {
    errorMessage.value = 'Anda harus mencentang persetujuan pemrosesan data pribadi.'
    return
  }

  if (!nomorPengujian.value.trim()) {
    errorMessage.value = 'Silakan masukkan nomor pengujian Anda.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.post('/api/public/pengujian/cari', {
      nomor_pengujian: nomorPengujian.value.trim()
    })

    aksesPublikStore.pengujianId = response.data.id
    aksesPublikStore.nomorPengujian = response.data.nomor_pengujian
    
    router.push({
      name: 'VerifikasiOtp',
      query: { email: response.data.email_tersamar }
    })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Nomor pengujian tidak ditemukan atau berkas belum siap diunduh.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="portal-page">
    <div class="portal-card-wrapper">
      
      <!-- SISI KIRI: BRANDING MINIMALIS & IDENTITAS BRMP BIOGEN -->
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
            <h1 class="portal-heading">Sistem Hasil Pengujian Laboratorium</h1>
            <p class="portal-subheading">
              Layanan untuk mengunduh laporan hasil pengujian laboratorium BRMP Biogen.
            </p>
          </div>

          <!-- 3 Poin Keunggulan Ringkas & Lega -->
          <div class="feature-pills">
            <div class="feature-pill">
              <ShieldCheck :size="18" class="pill-icon" />
              <span>Standar Mutu ISO/IEC 17025</span>
            </div>
            <div class="feature-pill">
              <Lock :size="18" class="pill-icon" />
              <span>Proteksi Kode Keamanan OTP</span>
            </div>
            <div class="feature-pill">
              <FileCheck :size="18" class="pill-icon" />
              <span>Dokumen Sah Bertanda Tangan Digital</span>
            </div>
          </div>
        </div>

        <!-- Footer Bantuan Singkat -->
        <div class="pane-footer">
          <span class="pane-footer-text">Anda Mengalami Kendala?</span>
          <a 
            href="https://wa.me/628111756776?text=Halo%20Admin%20Layanan%20Pengujian%20BRMP%20Biogen,%20saya%20butuh%20bantuan%20terkait%20pengujian%20sampel." 
            target="_blank" 
            rel="noopener noreferrer" 
            class="wa-help-link"
            title="Hubungi WhatsApp Call Center BRMP Biogen"
          >
            <MessageCircle :size="13" class="wa-ico" />
            <span>Hubungi WhatsApp Call Center</span>
          </a>
        </div>
      </div>

      <!-- SISI KANAN: FORM PENCARIAN BERSIH & FOKUS -->
      <div class="search-pane">
        <div class="search-box-content">
          
          <!-- Step Indicator Ringkas -->
          <div class="step-indicator-bar">
            <span class="step-badge">Langkah 1 dari 3</span>
            <span class="step-label">Pencarian Nomor Pengujian</span>
          </div>

          <!-- Judul Form -->
          <div class="form-title-group">
            <h2 class="form-title">Cari Nomor Pengujian</h2>
            <p class="form-desc">Masukkan nomor pengujian yang tertera pada formulir Permohonan Pengujian dan Kaji Ulang Permintaan.</p>
          </div>

          <!-- Error Banner -->
          <div v-if="errorMessage" class="alert-error">
            <AlertTriangle :size="18" class="alert-icon" />
            <span>{{ errorMessage }}</span>
          </div>

          <!-- Form Input -->
          <form @submit.prevent="handleSearch" class="main-form">
            <div class="form-field">
              <div class="field-top">
                <label for="nomor-input">Nomor Formulir Pengujian</label>
                <button 
                  type="button" 
                  class="help-link-btn" 
                  @click="showHelp = !showHelp"
                >
                  <HelpCircle :size="13" />
                  <span>Contoh format</span>
                </button>
              </div>

              <!-- Quick Helper Note -->
              <transition name="dropdown-anim">
                <div v-if="showHelp" class="help-popover">
                  Nomor pengujian tercetak di tengah atas lembar formulir permohonan. Format umum: <code>012/LAB-BIO/2026</code> atau <code>UJI-2026-0001</code>.
                </div>
              </transition>

              <!-- Search Input Field -->
              <div class="input-container">
                <Search :size="19" class="input-lead-icon" />
                <input 
                  type="text" 
                  id="nomor-input" 
                  v-model="nomorPengujian" 
                  placeholder="Contoh: 012/LAB-BIO/2026" 
                  autocomplete="off"
                  :disabled="isLoading"
                  required
                />
                <button 
                  v-if="nomorPengujian" 
                  type="button" 
                  @click="nomorPengujian = ''" 
                  class="clear-btn" 
                  title="Kosongkan"
                >
                  <X :size="14" />
                </button>
              </div>
            </div>

            <!-- PDP Consent Checkbox -->
            <label class="pdp-agreement">
              <input type="checkbox" v-model="persetujuanPdp" :disabled="isLoading" />
              <span class="pdp-agreement-text">
                Saya menyetujui pemrosesan data pribadi oleh BRMP Biogen untuk keperluan verifikasi identitas pemohon.
              </span>
            </label>

            <!-- Submit Button (Primary Focal Point) -->
            <button type="submit" class="submit-action-btn" :disabled="isLoading">
              <span v-if="isLoading" class="loading-state-box">
                <span class="spinner-ring"></span>
                <span>Memeriksa Data...</span>
              </span>
              <span v-else class="btn-text-wrap">
                <span>Cari &amp; Lanjutkan</span>
                <ArrowRight :size="18" class="arrow-ico" />
              </span>
            </button>
          </form>

          <!-- Security Stamp -->
          <div class="card-security-note">
            <ShieldCheck :size="14" />
            <span>Koneksi terenkripsi aman SSL/TLS &bull; BRMP Biogen</span>
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

/* Subtle background illumination */
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

.wa-help-link {
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

.wa-help-link:hover {
  background: rgba(250, 204, 21, 0.2);
  color: #fef08a;
  border-color: rgba(250, 204, 21, 0.35);
}

.wa-ico {
  display: block;
  flex-shrink: 0;
}

/* =========================================================
   SISI KANAN: FORM PENCARIAN BERSIH & LEGA
   ========================================================= */
.search-pane {
  padding: 48px 44px;
  display: flex;
  align-items: center;
  background: #ffffff;
}

.search-box-content {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 24px;
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

/* Error Banner */
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

.alert-icon {
  color: #dc2626;
  flex-shrink: 0;
}

/* Main Form */
.main-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.field-top label {
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
}

.help-link-btn {
  background: none;
  border: none;
  color: #1B4D3E;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 0;
}

.help-link-btn:hover {
  text-decoration: underline;
}

/* Helper Popover */
.help-popover {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-left: 3px solid #1B4D3E;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 12px;
  color: #475569;
  line-height: 1.45;
}

.help-popover code {
  background: #e2e8f0;
  color: #0f172a;
  padding: 1px 4px;
  border-radius: 4px;
  font-family: var(--font-mono);
}

/* Input Container */
.input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.input-lead-icon {
  position: absolute;
  left: 14px;
  color: #64748b;
  pointer-events: none;
}

.input-container input {
  width: 100%;
  padding: 14px 40px 14px 44px;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: 15px;
  color: #0f172a;
  background: #f8fafc;
  outline: none;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.input-container input:focus {
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.12);
}

.input-container input::placeholder {
  color: #94a3b8;
  font-size: 14px;
}

.clear-btn {
  position: absolute;
  right: 6px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: transparent;
  color: #64748b;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 0;
  transition: all 0.15s ease;
  z-index: 2;
}

.clear-btn::before {
  content: '';
  position: absolute;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #e2e8f0;
  z-index: -1;
  transition: background 0.15s ease;
}

.clear-btn:hover::before {
  background: #cbd5e1;
}

.clear-btn:hover {
  color: #0f172a;
}

/* PDP Agreement */
.pdp-agreement {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  user-select: none;
}

.pdp-agreement input {
  margin-top: 3px;
  width: 16px;
  height: 16px;
  accent-color: #1B4D3E;
  cursor: pointer;
  flex-shrink: 0;
}

.pdp-agreement-text {
  font-size: 12.5px;
  color: #64748b;
  line-height: 1.45;
}

/* Submit Action Button */
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

.arrow-ico {
  transition: transform 0.2s ease;
}

.submit-action-btn:hover:not(:disabled) .arrow-ico {
  transform: translateX(3px);
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

/* Transition Dropdown */
.dropdown-anim-enter-active,
.dropdown-anim-leave-active {
  transition: all 0.2s ease;
}

.dropdown-anim-enter-from,
.dropdown-anim-leave-to {
  opacity: 0;
  transform: translateY(-4px);
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
    display: none; /* Sembunyikan pilar di mobile agar tidak panjang bertele-tele */
  }

  .search-pane {
    padding: 36px 28px;
  }

  .form-title {
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

  .search-pane {
    padding: 28px 20px;
  }

  .agency-title {
    font-size: 17px;
  }

  .submit-action-btn {
    padding: 13px;
    font-size: 14px;
  }
}
</style>
