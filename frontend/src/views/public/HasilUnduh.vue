<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAksesPublikStore } from '../../stores/aksesPublik'
import { AlertTriangle, CheckCircle2, Loader2, Download, LogOut, ShieldCheck, FileText } from '@lucide/vue'

const router = useRouter()
const aksesPublikStore = useAksesPublikStore()

// State
const pengujian = ref(null)
const isLoading = ref(true)
const isDownloading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const verificationUrl = ref('')

// Fetch Status Pengujian
const fetchStatus = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get('/api/public/pengujian/status')
    pengujian.value = response.data

    // Buat tautan verifikasi dinamis berbasis domain saat ini
    const origin = window.location.origin
    verificationUrl.value = `${origin}/verifikasi/${response.data.nomor_pengujian}`

    // Proteksi: Jika SKM belum diisi, alihkan ke Form SKM (Rule 4.3)
    if (!response.data.skm_diisi) {
      router.replace({ name: 'FormSkm' })
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal memuat status pengujian.'
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

// Download File
const handleDownload = async (type) => {
  isDownloading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.get(`/api/public/pengujian/${pengujian.value.id}/download/${type}`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    
    // Tentukan nama file unduhan
    const fileName = (type === 'laporan' ? 'Laporan_' : 'Sertifikat_') + pengujian.value.nomor_pengujian + '.pdf';
    link.setAttribute('download', fileName)
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    successMessage.value = `Berkas ${type} berhasil diunduh.`
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Gagal mengunduh berkas. Sistem mendeteksi kemungkinan berkas telah rusak atau tidak valid.'
  } finally {
    isDownloading.value = false
  }
}

// Keluar Sesi / Hapus token
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
})
</script>

<template>
  <div class="public-container">
    <div v-if="toastMessage" class="toast-success">
      <CheckCircle2 :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ toastMessage }}
    </div>

    <div v-if="isLoading" class="landing-card loading-state">
      <Loader2 class="spinner" :size="32" />
      <p>Memuat informasi berkas hasil pengujian...</p>
    </div>

    <div v-else class="hasil-layout">
      <!-- Left side: Sample Summary & Download List -->
      <div class="landing-card info-card">
        <div class="brand">
          <img src="../../assets/logo-kementan.png" alt="Logo Kementan" class="brand-logo" />
          <h2>BRMP BIOGEN</h2>
          <p class="brand-sub">Unduh Berkas Hasil Uji</p>
        </div>

        <div v-if="errorMessage" class="alert alert-danger">
          <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
        </div>

        <div class="pengujian-info">
          <div class="info-row">
            <span class="info-label">Nomor Pengujian</span>
            <span class="info-value text-primary font-bold">{{ pengujian?.nomor_pengujian }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Jenis Layanan</span>
            <span class="info-value">{{ pengujian?.jenis_pengujian }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Nama Pemohon</span>
            <span class="info-value">{{ pengujian?.nama_pemohon }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Status Sampel</span>
            <span class="info-value">
              <span :class="['badge-status', pengujian?.status]">
                {{ pengujian?.status === 'selesai' ? 'Selesai' : 'Dalam Proses' }}
              </span>
            </span>
          </div>
        </div>

        <!-- Download Buttons -->
        <div class="download-section">
          <!-- 1. Laporan Hasil Uji -->
          <div class="download-item">
            <div class="file-info">
              <span class="file-icon flex-icon-center" style="color: #1B4D3E;"><FileText :size="28" /></span>
              <div class="file-details">
                <span class="file-title">Laporan Hasil Pengujian</span>
                <span class="file-desc text-muted">Format PDF Resmi ter-TTE</span>
              </div>
            </div>
            <button 
              v-if="pengujian?.file_laporan_ready"
              @click="handleDownload('laporan')" 
              class="btn-primary btn-dl"
              :disabled="isDownloading"
            >
              <span v-if="isDownloading" class="flex-icon-center"><Loader2 class="animate-spin" :size="16" /> Mengunduh...</span>
              <span v-else class="flex-icon-center"><Download :size="16" /> Unduh PDF</span>
            </button>
            <span v-else class="badge-waiting">Menunggu Upload</span>
          </div>
        </div>

        <button @click="handleExit" class="btn-back flex-icon-center" :disabled="isDownloading">
          <LogOut :size="16" /> Keluar dari Sesi
        </button>
      </div>

      <!-- Right side: QR Code Verification Info -->
      <div class="landing-card verification-card">
        <div class="verif-header">
          <span class="verif-badge flex-icon-center" style="gap: 4px; display: inline-flex;"><ShieldCheck :size="14" /> Dokumen Terverifikasi</span>
          <h3>Keaslian Dokumen &amp; QR Code</h3>
          <p class="text-muted">Gunakan QR Code di bawah untuk memverifikasi keaslian dokumen fisik hasil pengujian ini secara langsung melalui sistem publik BRMP Biogen.</p>
        </div>

        <div class="qr-box">
          <img 
            :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(verificationUrl)}`" 
            alt="QR Code Verifikasi Dokumen" 
            class="qr-image"
          />
        </div>

        <div class="verif-link-box">
          <span class="verif-label">Tautan Verifikasi Manual</span>
          <a :href="verificationUrl" target="_blank" class="verif-link">
            {{ verificationUrl }}
          </a>
        </div>
      </div>
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

.toast-success {
  position: fixed;
  top: 20px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 100;
}

.hasil-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 28px;
  width: 100%;
  max-width: 900px;
  align-items: start;
}

@media (max-width: 850px) {
  .hasil-layout {
    grid-template-columns: 1fr;
    max-width: 480px;
    gap: 24px;
  }
  .landing-card {
    height: auto;
    padding: 24px 18px;
    border-radius: 20px;
  }
  .download-item {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  .btn-dl {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .public-container {
    padding: 16px 12px;
  }
  .brand {
    margin-bottom: 16px;
  }
  .brand-logo {
    height: 52px;
  }
  .info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  .info-value {
    max-width: 100%;
    text-align: left;
  }
  .btn-back {
    margin-top: 20px;
    margin-bottom: 8px;
  }
}

.landing-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 40px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(226, 232, 240, 0.8);
  animation: cardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  height: 100%;
  display: flex;
  flex-direction: column;
}

@keyframes cardEnter {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.loading-state {
  max-width: 480px;
  align-items: center;
  justify-content: center;
  color: #64748b;
  gap: 12px;
}

.spinner {
  font-size: 32px;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
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

.pengujian-info {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 18px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 24px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.info-label {
  color: #64748b;
  font-weight: 500;
}

.info-value {
  color: #1e293b;
  font-weight: 600;
  text-align: right;
  max-width: 200px;
  word-wrap: break-word;
}

.info-value.text-primary {
  color: #1B4D3E;
}

.font-bold {
  font-weight: 700;
}

.badge-status {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 30px;
  font-size: 11px;
  font-weight: 700;
}

.badge-status.diproses {
  background: #fefce8;
  color: #854d0e;
}

.badge-status.selesai {
  background: #f0fdf4;
  color: #166534;
}

.download-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
  flex: 1;
}

.download-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 16px;
  border-radius: 12px;
  gap: 12px;
  transition: all 0.2s ease;
}

.download-item:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
}

.file-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.file-icon {
  font-size: 28px;
}

.file-details {
  display: flex;
  flex-direction: column;
}

.file-title {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
}

.file-desc {
  font-size: 11px;
  color: #94a3b8;
}

.btn-dl {
  padding: 8px 14px;
  font-size: 12px;
  border-radius: 8px;
  box-shadow: 0 3px 6px rgba(59,130,246,0.15);
  margin-top: 0;
  white-space: nowrap;
}

.badge-waiting {
  background: #f1f5f9;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.btn-back {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-size: 13px;
  font-weight: 700;
  margin-top: 24px;
  text-align: center;
  transition: opacity 0.2s ease;
}

.btn-back:hover {
  opacity: 0.8;
}

/* Verification Card Specific styles */
.verif-header {
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 16px;
  margin-bottom: 24px;
}

.verif-badge {
  background: rgba(27, 77, 62, 0.08);
  color: #1B4D3E;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 30px;
  border: 1px solid rgba(27, 77, 62, 0.2);
  display: inline-block;
  margin-bottom: 8px;
}

.verification-card h3 {
  margin: 0 0 6px 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.qr-box {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 24px;
  border-radius: 16px;
  margin: 0 auto 24px auto;
  width: fit-content;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
}

.qr-image {
  display: block;
  width: 160px;
  height: 160px;
}

.verif-link-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 14px;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.verif-label {
  font-size: 10px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.verif-link {
  font-size: 12px;
  font-weight: 600;
  color: #1B4D3E;
  word-break: break-all;
  text-decoration: none;
}

.verif-link:hover {
  text-decoration: underline;
}

.btn-primary {
  background: #1B4D3E;
  color: white;
  border: none;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s ease;
}

.flex-icon-center {
  display: inline-flex;
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
</style>
