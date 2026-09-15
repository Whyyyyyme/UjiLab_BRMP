<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { Loader2, AlertTriangle, Search, XCircle, RefreshCw, CheckCircle2, ArrowLeft } from '@lucide/vue'

const route = useRoute()
const router = useRouter()

const nomorPengujianParam = route.params.nomor_pengujian

// State
const pengujian = ref(null)
const isLoading = ref(true)
const isNotFound = ref(false)
const errorMessage = ref('')

// Fetch Verification Status
const checkVerification = async () => {
  isLoading.value = true
  isNotFound.value = false
  errorMessage.value = ''
  try {
    const response = await api.get(`/api/public/verifikasi/${nomorPengujianParam}`)
    pengujian.value = response.data
  } catch (error) {
    console.error(error)
    if (error.response?.status === 404) {
      isNotFound.value = true
    } else {
      errorMessage.value = 'Terjadi kesalahan sistem saat memproses verifikasi.'
    }
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  if (!nomorPengujianParam) {
    router.replace({ name: 'CariPengujian' })
    return
  }
  checkVerification()
})
</script>

<template>
  <div class="public-container">
    <!-- Loading state -->
    <div v-if="isLoading" class="landing-card loading-state">
      <div class="spinner"><Loader2 class="animate-spin" :size="32" /></div>
      <p>Memindai keaslian dokumen di basis data...</p>
    </div>

    <!-- 1. Skenario NOT FOUND / Palsu -->
    <div v-else-if="isNotFound" class="landing-card warning-card">
      <div class="status-badge error">
        <span class="status-icon"><AlertTriangle :size="32" /></span>
        <h3>Dokumen Tidak Terdaftar</h3>
      </div>

      <div class="card-intro text-center">
        Sistem BRMP Biogen tidak menemukan dokumen dengan nomor pengujian:
        <div class="highlight-number text-red">{{ nomorPengujianParam }}</div>
      </div>

      <div class="alert alert-danger text-left">
        <strong>Peringatan Keamanan:</strong> Berkas fisik atau digital yang Anda miliki kemungkinan besar adalah <strong>palsu</strong>, telah kedaluwarsa, atau sudah dihapus oleh instansi berwenang. Jangan memproses dokumen ini lebih lanjut untuk keperluan hukum/administrasi apa pun.
      </div>

      <button @click="router.push({ name: 'CariPengujian' })" class="btn-primary btn-full flex-icon-center">
        <Search :size="16" /> Cari Nomor Lain
      </button>
    </div>

    <!-- 2. Skenario ERROR sistem -->
    <div v-else-if="errorMessage" class="landing-card warning-card">
      <div class="status-badge error">
        <span class="status-icon"><XCircle :size="32" /></span>
        <h3>Kesalahan Sistem</h3>
      </div>
      <p class="text-center text-muted">{{ errorMessage }}</p>
      <button @click="checkVerification" class="btn-secondary btn-full flex-icon-center">
        <RefreshCw :size="16" /> Coba Lagi
      </button>
    </div>

    <!-- 3. Skenario DOKUMEN ASLI & VALID -->
    <div v-else class="landing-card verif-success-card">
      <!-- Badge Keaslian -->
      <div class="status-badge success" :class="{ 'warning-hash': !pengujian?.status_keaslian }">
        <span class="status-icon">
          <CheckCircle2 v-if="pengujian?.status_keaslian" :size="32" />
          <AlertTriangle v-else :size="32" />
        </span>
        <h3>{{ pengujian?.status_keaslian ? 'Dokumen Terverifikasi Asli' : 'Dokumen Rusak / Dimodifikasi' }}</h3>
      </div>

      <div class="card-intro text-center">
        Detail dokumen resmi untuk nomor pengujian:
        <div class="highlight-number text-blue">{{ pengujian?.nomor_pengujian }}</div>
      </div>

      <div class="details-section">
        <div class="detail-row">
          <span class="detail-label">Jenis Layanan</span>
          <span class="detail-val">{{ pengujian?.jenis_pengujian }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Nama Pemohon (PDP masked)</span>
          <span class="detail-val font-semibold">{{ pengujian?.nama_pemohon }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Tanggal Selesai Uji</span>
          <span class="detail-val">{{ pengujian?.tanggal_selesai ? new Date(pengujian.tanggal_selesai).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-' }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Status Administrasi</span>
          <span class="detail-val">
            <span :class="['badge-status', pengujian?.status]">
              {{ pengujian?.status === 'selesai' ? 'Selesai' : 'Dalam Proses' }}
            </span>
          </span>
        </div>
      </div>

      <!-- Detail Integritas PDF -->
      <div class="integrity-section">
        <h4 class="integrity-title">Pemeriksaan Integritas Berkas (SHA-256):</h4>
        
        <div class="integrity-item">
          <span class="integrity-label">Laporan Hasil Uji</span>
          <span :class="['integrity-val', pengujian?.laporan_valid ? 'valid' : 'invalid']">
            {{ pengujian?.laporan_valid ? '🟢 VALID (Sesuai Aslinya)' : '🔴 TIDAK VALID / MODIFIKASI' }}
          </span>
        </div>
      </div>

      <div v-if="pengujian?.status_keaslian" class="alert alert-success text-left">
        <strong>Konfirmasi Resmi:</strong> Sistem memverifikasi bahwa metadata dan konten file PDF hasil uji di atas <strong>cocok 100%</strong> dengan pangkalan data BRMP Biogen Kementerian Pertanian Republik Indonesia.
      </div>
      <div v-else class="alert alert-danger text-left">
        <strong>Peringatan Integritas:</strong> Sistem mendeteksi tanda tangan digital atau konten berkas PDF fisik telah <strong>dimodifikasi</strong> di luar sistem resmi. Keaslian dokumen diragukan.
      </div>

      <button @click="router.push({ name: 'CariPengujian' })" class="btn-primary btn-full flex-icon-center">
        <ArrowLeft :size="16" /> Kembali Ke Pencarian
      </button>
    </div>
  </div>
</template>

<style scoped>
.public-container {
  font-family: var(--font-sans);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at 50% 0%, #f0f7f4 0%, #f8fafc 60%, #e2e8f0 100%);
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.public-container::before {
  content: '';
  position: absolute;
  top: -120px;
  right: -120px;
  width: 360px;
  height: 360px;
  background: radial-gradient(circle, rgba(234, 179, 8, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
  border-radius: 50%;
  pointer-events: none;
}

.public-container::after {
  content: '';
  position: absolute;
  bottom: -120px;
  left: -120px;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(27, 77, 62, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
  border-radius: 50%;
  pointer-events: none;
}

.landing-card {
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  width: 100%;
  max-width: 500px;
  padding: 40px;
  box-shadow: 0 20px 40px -15px rgba(27, 77, 62, 0.1), inset 0 0 0 1px rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-top: 5px solid #1B4D3E;
  position: relative;
  z-index: 1;
  animation: cardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.warning-card {
  border-top: 5px solid #dc2626;
}

@keyframes cardEnter {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.loading-state {
  align-items: center;
  justify-content: center;
  color: #64748b;
  gap: 12px;
}

.spinner {
  font-size: 32px;
  color: #1B4D3E;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.text-center {
  text-align: center;
}

.text-left {
  text-align: left;
}

.text-muted {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

/* Status Badges */
.status-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  border-radius: 16px;
  text-align: center;
}

.status-badge.success {
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.status-badge.warning-hash {
  background: #fffbeb;
  color: #92400e;
  border: 1px solid #fde68a;
}

.status-badge.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.status-icon {
  font-size: 36px;
}

.status-badge h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
}

.card-intro {
  font-size: 13px;
  color: #64748b;
}

.highlight-number {
  font-size: 20px;
  font-weight: 800;
  margin-top: 4px;
}

.highlight-number.text-blue {
  color: #1B4D3E;
}

.highlight-number.text-red {
  color: #dc2626;
}

.alert {
  padding: 14px;
  border-radius: 12px;
  font-size: 12px;
  line-height: 1.6;
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

.details-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 8px;
}

.detail-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.detail-label {
  color: #64748b;
  font-weight: 500;
}

.detail-val {
  color: #1e293b;
  font-weight: 700;
  text-align: right;
  max-width: 240px;
  word-wrap: break-word;
}

.font-semibold {
  font-weight: 700;
}

.badge-status {
  display: inline-block;
  padding: 2px 8px;
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

.integrity-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 4px;
}

.integrity-title {
  margin: 0 0 4px 0;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.integrity-item {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  align-items: center;
}

.integrity-label {
  color: #64748b;
  font-weight: 500;
}

.integrity-val {
  font-weight: 700;
}

.integrity-val.valid {
  color: #166534;
}

.integrity-val.invalid {
  color: #b91c1c;
}

.btn-primary {
  background: #1B4D3E;
  color: white;
  border: none;
  padding: 14px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(27, 77, 62, 0.25);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-primary:hover {
  background: #13382D;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(27, 77, 62, 0.35);
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border: 1.5px solid #cbd5e1;
  padding: 14px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-secondary:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-full {
  width: 100%;
}

.flex-icon-center {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.animate-spin {
  animation: spin 1s linear infinite;
  display: inline-block;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .public-container {
    padding: 16px 12px;
  }
  .landing-card {
    padding: 28px 20px;
    border-radius: 20px;
  }
  .brand {
    margin-bottom: 16px;
  }
  .brand-logo {
    height: 52px;
  }
  .card-header h1 {
    font-size: 20px;
  }
  .meta-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .action-footer {
    flex-direction: column;
    gap: 10px;
  }
  .btn-primary, .btn-secondary {
    width: 100%;
  }
}
</style>
