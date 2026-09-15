<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAksesPublikStore } from '../../stores/aksesPublik'
import { 
  AlertTriangle, 
  CheckCircle2, 
  Loader2, 
  Download, 
  LogOut, 
  ShieldCheck, 
  FileText, 
  ExternalLink, 
  Copy, 
  Check, 
  Eye
} from '@lucide/vue'

const router = useRouter()
const aksesPublikStore = useAksesPublikStore()

// State
const pengujian = ref(null)
const isLoading = ref(true)
const isDownloading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const verificationUrl = ref('')
const isCopied = ref(false)
const isNoPengujianCopied = ref(false)

// State Pratinjau PDF Modal
const showPreviewModal = ref(false)
const previewUrl = ref('')
const previewLoading = ref(false)

// Format Tanggal
const formatDate = (dateString) => {
  if (!dateString) return '-'
  try {
    const d = new Date(dateString)
    return d.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    })
  } catch {
    return dateString
  }
}

// Fetch Status Pengujian
const fetchStatus = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get('/api/public/pengujian/status')
    pengujian.value = response.data

    const origin = window.location.origin
    verificationUrl.value = `${origin}/verifikasi/${response.data.nomor_pengujian}`

    if (!response.data.skm_diisi) {
      router.replace({ name: 'FormSkm' })
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal memuat dokumen hasil pengujian.'
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

// Download Berkas LHU
const handleDownload = async () => {
  isDownloading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.get(`/api/public/pengujian/${pengujian.value.id}/download/laporan`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Laporan_Hasil_Pengujian_${pengujian.value.nomor_pengujian}.pdf`)
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    successMessage.value = 'Dokumen pengujian berhasil diunduh.'
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (error) {
    console.error(error)
    errorMessage.value = error.response?.data?.message || 'Gagal mengunduh berkas. Silakan coba kembali.'
  } finally {
    isDownloading.value = false
  }
}

// Copy Tautan Verifikasi
const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(verificationUrl.value)
    isCopied.value = true
    setTimeout(() => { isCopied.value = false }, 2000)
  } catch (err) {
    console.error(err)
  }
}

// Copy Nomor Pengujian
const copyNomorPengujian = async () => {
  if (!pengujian.value?.nomor_pengujian) return
  try {
    await navigator.clipboard.writeText(pengujian.value.nomor_pengujian)
    isNoPengujianCopied.value = true
    setTimeout(() => { isNoPengujianCopied.value = false }, 2000)
  } catch (err) {
    console.error(err)
  }
}

// Modal Pratinjau PDF
const openPreviewModal = async () => {
  if (!pengujian.value) return
  previewLoading.value = true
  showPreviewModal.value = true
  
  try {
    const response = await api.get(`/api/public/pengujian/${pengujian.value.id}/download/laporan`, {
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    if (previewUrl.value) {
      window.URL.revokeObjectURL(previewUrl.value)
    }
    previewUrl.value = window.URL.createObjectURL(blob)
  } catch (error) {
    console.error('Gagal memuat pratinjau PDF:', error)
    errorMessage.value = 'Pratinjau tidak dapat dimuat. Silakan klik tombol Unduh langsung.'
    showPreviewModal.value = false
  } finally {
    previewLoading.value = false
  }
}

const closePreviewModal = () => {
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = ''
  }
  showPreviewModal.value = false
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && showPreviewModal.value) {
    closePreviewModal()
  }
}

// Keluar Sesi
const handleExit = () => {
  aksesPublikStore.clearAkses()
  router.push({ name: 'CariPengujian' })
}

onMounted(() => {
  if (!aksesPublikStore.hasAkses) {
    router.replace({ name: 'CariPengujian' })
    return
  }
  fetchStatus()
  window.addEventListener('keydown', handleKeyDown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeyDown)
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
  }
})
</script>

<template>
  <div class="hasil-page">
    <!-- Top Accent Bar -->
    <div class="top-accent-bar"></div>

    <div class="page-container">
      
      <!-- Toast Notifikasi Ringkas -->
      <transition name="toast-fade">
        <div v-if="successMessage" class="toast-floating">
          <CheckCircle2 :size="16" class="text-emerald" />
          <span>{{ successMessage }}</span>
        </div>
      </transition>

      <!-- Loading State Ringan -->
      <div v-if="isLoading" class="loading-box">
        <Loader2 class="spinner" :size="36" />
        <p>Menyiapkan dokumen hasil pengujian...</p>
      </div>

      <!-- Main Clean Card -->
      <main v-else class="main-card">
        
        <!-- Header Identitas Balai -->
        <header class="card-header">
          <div class="brand-row">
            <img src="../../assets/logo-kementan.png" alt="Logo Kementan" class="brand-logo" />
            <div class="brand-meta">
              <span class="brand-kementan">KEMENTERIAN PERTANIAN REPUBLIK INDONESIA</span>
              <h1 class="brand-balai">BRMP BIOGEN</h1>
            </div>
          </div>
          <div class="status-pill">
            <span class="status-dot"></span>
            <span>Dokumen Siap</span>
          </div>
        </header>

        <!-- Alert Error -->
        <div v-if="errorMessage" class="error-alert">
          <AlertTriangle :size="16" class="shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Section 1: Dokumen LHU (Fokus Utama) -->
        <section class="doc-hero">
          <div class="doc-icon-wrap">
            <FileText :size="32" class="doc-icon" />
          </div>
          <div class="doc-info">
            <h2 class="doc-title">Laporan Hasil Pengujian</h2>
            <p class="doc-sub">Dokumen resmi berformat PDF &bull; Disahkan dengan Tanda Tangan Elektronik (TTE)</p>
          </div>
        </section>

        <!-- Section 2: Ringkasan Pengujian (Tipografi Bersih, Tanpa Kotak Bertumpuk) -->
        <section class="info-list">
          <div class="info-row">
            <span class="info-label">Nomor Pengujian</span>
            <div class="info-val-with-action">
              <strong class="font-mono text-dark">{{ pengujian?.nomor_pengujian }}</strong>
              <button 
                type="button" 
                class="btn-copy-mini" 
                @click="copyNomorPengujian"
                :title="isNoPengujianCopied ? 'Tersalin' : 'Salin Nomor'"
              >
                <Check v-if="isNoPengujianCopied" :size="13" class="text-emerald" />
                <Copy v-else :size="13" />
                <span>{{ isNoPengujianCopied ? 'Tersalin' : 'Salin' }}</span>
              </button>
            </div>
          </div>

          <div class="info-row">
            <span class="info-label">Nama Pemohon</span>
            <span class="info-val text-dark">{{ pengujian?.nama_pemohon || '-' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Jenis Pengujian</span>
            <span class="info-val text-dark">{{ pengujian?.jenis_pengujian || '-' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Tanggal Terbit</span>
            <span class="info-val">{{ formatDate(pengujian?.tanggal_selesai) }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Validasi Legalitas</span>
            <span class="info-val">
              <span class="badge-valid">
                <ShieldCheck :size="13" /> Sah (ISO 17025 &amp; TTE BSrE)
              </span>
            </span>
          </div>
        </section>

        <!-- Section 3: Tombol Aksi Utama (Download & Preview) -->
        <section class="actions-area">
          <div v-if="pengujian?.file_laporan_ready" class="buttons-row">
            <button 
              type="button" 
              class="btn-download" 
              @click="handleDownload"
              :disabled="isDownloading"
            >
              <Loader2 v-if="isDownloading" class="spinner-sm" :size="18" />
              <Download v-else :size="18" />
              <span>{{ isDownloading ? 'Mengunduh...' : 'Unduh (PDF)' }}</span>
            </button>

            <button 
              type="button" 
              class="btn-preview" 
              @click="openPreviewModal"
              :disabled="isDownloading || previewLoading"
            >
              <Eye :size="17" />
              <span>Pratinjau</span>
            </button>
          </div>

          <div v-else class="pending-notice">
            <Loader2 class="spinner-sm text-amber" :size="16" />
            <span>Dokumen dalam proses finalisasi tanda tangan. Silakan muat ulang beberapa saat lagi.</span>
          </div>
        </section>

        <hr class="divider" />

        <!-- Section 4: Verifikasi Publik Ringkas (QR & Link) -->
        <section class="verification-compact">
          <div class="qr-compact-box">
            <img 
              :src="`https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=${encodeURIComponent(verificationUrl)}`" 
              alt="QR Verifikasi Dokumen" 
              class="qr-img-compact"
            />
          </div>

          <div class="verif-compact-details">
            <h3 class="verif-compact-title">Verifikasi Keaslian Dokumen</h3>
            <p class="verif-compact-desc">
              Pindai QR atau klik tautan berikut untuk memverifikasi keabsahan laporan hasil uji secara langsung:
            </p>
            <div class="link-bar">
              <input type="text" readonly :value="verificationUrl" class="input-link-clean" />
              <button 
                type="button" 
                class="btn-copy-link" 
                @click="copyLink"
                :class="{ 'copied': isCopied }"
              >
                <Check v-if="isCopied" :size="14" />
                <Copy v-else :size="14" />
                <span>{{ isCopied ? 'Tersalin' : 'Salin' }}</span>
              </button>
              <a 
                :href="verificationUrl" 
                target="_blank" 
                class="btn-open-link" 
                title="Buka Halaman Verifikasi"
              >
                <ExternalLink :size="15" />
              </a>
            </div>
          </div>
        </section>

        <!-- Footer: Keluar Sesi -->
        <footer class="card-footer">
          <button type="button" @click="handleExit" class="btn-exit" :disabled="isDownloading">
            <LogOut :size="14" />
            <span>Selesai &amp; Keluar Sesi</span>
          </button>
        </footer>

      </main>

      <!-- Micro Brand Trust Line -->
      <div class="trust-foot">
        <span>Laboratorium Penguji Terakreditasi KAN ISO/IEC 17025 &bull; BRMP Biogen</span>
      </div>
    </div>

    <!-- Modal Pratinjau PDF -->
    <div v-if="showPreviewModal" class="modal-backdrop" @click.self="closePreviewModal">
      <div class="modal-box">
        <div class="modal-bar">
          <div class="modal-bar-title">
            <FileText :size="18" class="text-emerald" />
            <strong>Laporan Hasil Pengujian &bull; {{ pengujian?.nomor_pengujian }}</strong>
          </div>
          <div class="modal-bar-actions">
            <button type="button" @click="handleDownload" class="btn-modal-dl" :disabled="isDownloading">
              <Download :size="14" />
              <span>Unduh</span>
            </button>
            <button type="button" @click="closePreviewModal" class="btn-modal-close" title="Tutup (Esc)">&times;</button>
          </div>
        </div>

        <div class="modal-content-pdf">
          <div v-if="previewLoading" class="preview-load-state">
            <Loader2 class="spinner text-white" :size="36" />
            <p>Memuat lembar PDF...</p>
          </div>
          <object v-else-if="previewUrl" :data="previewUrl" type="application/pdf" class="pdf-viewer">
            <div class="pdf-fallback-box">
              <p>Peramban Anda tidak mendukung pratinjau PDF langsung.</p>
              <button type="button" @click="handleDownload" class="btn-download">
                <Download :size="16" /> Unduh Dokumen Sekarang
              </button>
            </div>
          </object>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* Reset & Base */
.hasil-page {
  min-height: 100vh;
  background: #f8fafc;
  font-family: var(--font-sans);
  color: #0f172a;
  display: flex;
  flex-direction: column;
}

/* Top Accent Line (Kementan Green & Gold) */
.top-accent-bar {
  height: 4px;
  width: 100%;
  background: linear-gradient(90deg, #1B4D3E 0%, #246B56 70%, #EAB308 100%);
  position: fixed;
  top: 0;
  left: 0;
  z-index: 100;
}

.page-container {
  width: 100%;
  max-width: 640px;
  margin: 0 auto;
  padding: 48px 16px 40px 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Floating Toast */
.toast-floating {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: #0f172a;
  color: #ffffff;
  padding: 10px 20px;
  border-radius: 9999px;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
  z-index: 200;
}

.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: all 0.25s ease;
}

.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translate(-50%, -10px);
}

/* Loading State */
.loading-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 56px 20px;
  text-align: center;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  font-size: 14px;
}

.spinner {
  color: #1B4D3E;
  animation: spin 1s linear infinite;
}

.spinner-sm {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Main Single Unified Card (Clean & Spacious) */
.main-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 32px 36px;
  box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Header */
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
}

.brand-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-logo {
  height: 44px;
  width: auto;
  object-fit: contain;
}

.brand-meta {
  display: flex;
  flex-direction: column;
}

.brand-kementan {
  font-size: 10px;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.5px;
}

.brand-balai {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #1B4D3E;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 11.5px;
  font-weight: 700;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
}

/* Alert */
.error-alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Document Hero (Simple & Direct) */
.doc-hero {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border: 1px solid #edf2f7;
  border-radius: 14px;
}

.doc-icon-wrap {
  width: 50px;
  height: 50px;
  background: rgba(27, 77, 62, 0.08);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.doc-icon {
  color: #1B4D3E;
}

.doc-info {
  flex: 1;
}

.doc-title {
  margin: 0 0 2px 0;
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
}

.doc-sub {
  margin: 0;
  font-size: 12.5px;
  color: #64748b;
}

/* Clean Info List (Refactoring UI: Pure Typography & Whitespace, No Heavy Boxes) */
.info-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 4px 0;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13.5px;
  gap: 16px;
}

.info-label {
  color: #64748b;
  font-size: 13px;
  flex-shrink: 0;
}

.info-val {
  text-align: right;
  font-weight: 600;
  color: #475569;
}

.info-val-with-action {
  display: flex;
  align-items: center;
  gap: 8px;
}

.text-dark {
  color: #0f172a;
}

.font-mono {
  font-family: var(--font-mono);
}

.btn-copy-mini {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #475569;
  border-radius: 6px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: all 0.15s ease;
}

.btn-copy-mini:hover {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #0f172a;
}

.badge-valid {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #15803d;
  font-size: 12px;
  font-weight: 700;
}

/* Action Area (Focal Download Button) */
.actions-area {
  padding-top: 4px;
}

.buttons-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-download {
  flex: 1;
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  padding: 13px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(27, 77, 62, 0.2);
  transition: all 0.15s ease;
}

.btn-download:hover:not(:disabled) {
  background: #13392E;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(27, 77, 62, 0.3);
}

.btn-preview {
  background: #ffffff;
  color: #1B4D3E;
  border: 1.5px solid rgba(27, 77, 62, 0.3);
  padding: 12px 18px;
  border-radius: 12px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.15s ease;
}

.btn-preview:hover:not(:disabled) {
  background: rgba(27, 77, 62, 0.05);
  border-color: #1B4D3E;
}

.btn-download:disabled,
.btn-preview:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.pending-notice {
  background: #fffbeb;
  border: 1px solid #fef3c7;
  color: #b45309;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.divider {
  border: none;
  height: 1px;
  background: #f1f5f9;
  margin: 0;
}

/* Compact Verification Section */
.verification-compact {
  display: flex;
  align-items: center;
  gap: 18px;
}

.qr-compact-box {
  flex-shrink: 0;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 6px;
  display: flex;
}

.qr-img-compact {
  width: 76px;
  height: 76px;
  display: block;
}

.verif-compact-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.verif-compact-title {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.verif-compact-desc {
  margin: 0 0 4px 0;
  font-size: 12px;
  color: #64748b;
  line-height: 1.45;
}

.link-bar {
  display: flex;
  align-items: center;
  gap: 6px;
}

.input-link-clean {
  flex: 1;
  min-width: 0;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 12px;
  color: #475569;
  font-family: var(--font-mono);
  outline: none;
}

.btn-copy-link,
.btn-open-link {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 7px 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: all 0.15s ease;
  text-decoration: none;
}

.btn-copy-link:hover,
.btn-open-link:hover {
  border-color: #1B4D3E;
  color: #1B4D3E;
  background: #f0fdf4;
}

.btn-copy-link.copied {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

/* Footer Exit */
.card-footer {
  display: flex;
  justify-content: center;
  padding-top: 4px;
}

.btn-exit {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: color 0.15s ease;
}

.btn-exit:hover {
  color: #dc2626;
}

/* Micro Trust Line */
.trust-foot {
  text-align: center;
  font-size: 11.5px;
  color: #94a3b8;
}

/* Modal Pratinjau PDF */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(6px);
  z-index: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-box {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  height: 86vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
}

.modal-bar {
  padding: 12px 18px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-bar-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #0f172a;
}

.modal-bar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-modal-dl {
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-modal-close {
  background: none;
  border: none;
  font-size: 24px;
  line-height: 1;
  color: #94a3b8;
  cursor: pointer;
  padding: 0 4px;
}

.btn-modal-close:hover {
  color: #0f172a;
}

.modal-content-pdf {
  flex: 1;
  background: #334155;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pdf-viewer {
  width: 100%;
  height: 100%;
  border: none;
}

.preview-load-state {
  color: #ffffff;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  font-size: 13.5px;
}

.pdf-fallback-box {
  padding: 24px;
  text-align: center;
  color: #ffffff;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.text-emerald {
  color: #10b981;
}

.text-white {
  color: #ffffff;
}

.text-amber {
  color: #d97706;
}

.shrink-0 {
  flex-shrink: 0;
}

/* Responsif Mobile */
@media (max-width: 640px) {
  .content-container {
    padding: 24px 14px 36px 14px;
  }
  .main-card {
    padding: 22px 16px;
    border-radius: 16px;
  }
  .info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  .info-val {
    text-align: left;
  }
  .buttons-row {
    flex-direction: column;
  }
  .btn-download, .btn-preview {
    width: 100%;
  }
  .verification-compact {
    flex-direction: column;
    align-items: stretch;
    gap: 14px;
  }
  .verif-compact-details {
    width: 100%;
  }
  .link-bar {
    width: 100%;
  }

  /* PDF Preview Modal Optimization for Mobile Viewports */
  .modal-overlay {
    padding: 6px;
  }
  .modal-box {
    height: 96vh;
    border-radius: 12px;
  }
  .modal-bar {
    padding: 8px 12px;
  }
  .modal-bar-title {
    font-size: 13px;
  }
  .btn-modal-dl {
    padding: 6px 10px;
    font-size: 12px;
  }
  .btn-modal-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 24px;
  }
}

@media (max-width: 440px) {
  .link-bar {
    flex-wrap: wrap;
  }
  .input-link-clean {
    width: 100%;
    flex: none;
  }
  .btn-copy-link,
  .btn-open-link {
    flex: 1;
    justify-content: center;
  }
}
</style>
