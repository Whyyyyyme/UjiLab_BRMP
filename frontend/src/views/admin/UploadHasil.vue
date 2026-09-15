<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { AlertTriangle, CheckCircle2, Loader2, Save, ArrowLeft } from '@lucide/vue'

const route = useRoute()
const router = useRouter()

const pengujianId = route.params.id

// Data State
const pengujian = ref(null)
const isLoading = ref(true)
const isUploading = ref(false)
const uploadProgress = ref(0)

// File Input Refs
const fileLaporan = ref(null)

// Form confirmation (autofill preview)
const showAutofillPreview = ref(false)
const autofillSource = ref(null) // Extracted values directly from PDF
const form = ref({
  nomor_pengujian: '',
  nama_pemohon: '',
  jenis_pengujian: '',
  email_pemohon: ''
})

// Highlight Flags
const highlightedFields = ref({
  nomor_pengujian: false,
  nama_pemohon: false,
  jenis_pengujian: false
})

const errorMessage = ref('')
const successMessage = ref('')

const jenisPengujianList = [
  'Analisis SSR/RAPD',
  'Deteksi GMO',
  'Deteksi Virus secara Molekuler',
  'Analisis Ploidi Level',
  'Uji Mutu Benih (ISTA)',
  'Liofilisasi',
  'Enumerasi Total Mikroba Bakteri/Cendawan',
  'Deteksi Mikroba secara Molekuler (Bakteri/Cendawan)',
  'Uji Sensitivitas Bakteri',
  'Pengujian Lainnya'
]

// Fetch Pengujian Details
const fetchPengujian = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get(`/api/admin/pengujian/${pengujianId}`)
    pengujian.value = response.data
    form.value = {
      nomor_pengujian: response.data.nomor_pengujian,
      nama_pemohon: response.data.nama_pemohon,
      jenis_pengujian: response.data.jenis_pengujian,
      email_pemohon: response.data.email_pemohon
    }
  } catch (error) {
    errorMessage.value = 'Gagal memuat data pengujian.'
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

// Handle File Change
const onFileLaporanChange = (e) => {
  fileLaporan.value = e.target.files[0]
}

// Upload Files
const handleUpload = async () => {
  if (!fileLaporan.value) {
    errorMessage.value = 'Silakan pilih berkas Laporan untuk diunggah.'
    return
  }

  isUploading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  uploadProgress.value = 0

  const formData = new FormData()
  if (fileLaporan.value) formData.append('file_laporan', fileLaporan.value)

  try {
    const response = await api.post(`/api/admin/pengujian/${pengujianId}/upload`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    })

    successMessage.value = 'Berkas hasil pengujian berhasil diunggah!'
    pengujian.value = response.data.data

    // Handle Autofill Suggestions
    if (response.data.autofill) {
      const parsed = response.data.autofill
      autofillSource.value = { ...parsed }
      
      // Update form with suggestions, highlight fields if values are found
      if (parsed.nomor_pengujian) {
        form.value.nomor_pengujian = parsed.nomor_pengujian
        highlightedFields.value.nomor_pengujian = true
      }
      if (parsed.nama_pemohon) {
        form.value.nama_pemohon = parsed.nama_pemohon
        highlightedFields.value.nama_pemohon = true
      }
      if (parsed.jenis_pengujian) {
        form.value.jenis_pengujian = parsed.jenis_pengujian
        highlightedFields.value.jenis_pengujian = true
      }

      showAutofillPreview.value = true
    } else {
      // If no autofill suggestions, redirect to list after short delay
      setTimeout(() => {
        router.push({ name: 'DataPengujian' })
      }, 2000)
    }

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengunggah berkas. Pastikan format berkas adalah PDF asli.'
  } finally {
    isUploading.value = false
  }
}

// Confirm Autofill and save metadata updates
const handleConfirmAutofill = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.put(`/api/admin/pengujian/${pengujianId}`, form.value)
    successMessage.value = 'Konfirmasi data berhasil disimpan!'
    
    // Clear highlights
    highlightedFields.value = { nomor_pengujian: false, nama_pemohon: false, jenis_pengujian: false }
    showAutofillPreview.value = false
    
    setTimeout(() => {
      router.push({ name: 'DataPengujian' })
    }, 1500)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menyimpan perubahan data.'
  }
}

onMounted(() => {
  fetchPengujian()
})
</script>

<template>
  <div class="upload-hasil-view">
    <div v-if="successMessage" class="toast-success">
      <CheckCircle2 :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ successMessage }}
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="spinner"><Loader2 class="animate-spin" :size="32" /></div>
      <p>Memuat rincian data pengujian...</p>
    </div>

    <div v-else class="upload-layout">
      <!-- Left side: Upload Form Card -->
      <div class="card upload-card">
        <div class="card-header">
          <button @click="router.push({ name: 'DataPengujian' })" class="btn-back flex-icon-center">
            <ArrowLeft :size="14" /> Kembali
          </button>
          <h3>Unggah Berkas Hasil Uji</h3>
          <p class="text-muted">Nomor Uji Awal: {{ pengujian?.nomor_pengujian }}</p>
        </div>

        <div class="pengujian-summary">
          <div class="info-item">
            <span class="info-label">Pemohon</span>
            <span class="info-value">{{ pengujian?.nama_pemohon }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Jenis Layanan</span>
            <span class="info-value">{{ pengujian?.jenis_pengujian }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Versi Berkas</span>
            <span class="info-value"><span class="badge-version">v{{ pengujian?.versi }}</span></span>
          </div>
        </div>

        <form @submit.prevent="handleUpload" class="upload-form">
          <div v-if="errorMessage" class="alert alert-danger">
            <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
          </div>

          <div class="form-group">
            <label for="laporan">Laporan Hasil Pengujian (PDF asli)</label>
            <input 
              type="file" 
              id="laporan" 
              accept=".pdf" 
              @change="onFileLaporanChange" 
              :disabled="isUploading || showAutofillPreview"
            />
            <span class="file-hint">Maksimal 50MB. File lama tidak akan terhapus jika diunggah ulang (versi bertambah).</span>
          </div>

          <!-- Progress Bar -->
          <div v-if="isUploading" class="progress-container">
            <div class="progress-bar-wrapper">
              <div class="progress-bar-fill" :style="{ width: uploadProgress + '%' }"></div>
            </div>
            <span class="progress-text">Mengunggah... {{ uploadProgress }}%</span>
          </div>

          <button 
            type="submit" 
            class="btn-primary btn-upload" 
            :disabled="isUploading || showAutofillPreview"
          >
            <Upload :size="16" style="margin-right: 6px; display: inline-block; vertical-align: middle;" /> Mulai Unggah &amp; Ekstrak Data
          </button>
        </form>
      </div>

      <!-- Right side: Autofill Confirmation Card -->
      <div v-if="showAutofillPreview" class="card autofill-card">
        <div class="card-header">
          <div class="autofill-badge">🤖 Rekomendasi Ekstraksi Otomatis PDF</div>
          <h3>Konfirmasi Hasil Ekstraksi PDF</h3>
          <p class="text-muted">Sistem mendeteksi data di bawah ini dari file PDF yang baru saja diunggah. Mohon periksa kembali sebelum menyimpan.</p>
        </div>

        <form @submit.prevent="handleConfirmAutofill" class="autofill-form">
          <div class="form-group">
            <label>
              Nomor Pengujian
              <span v-if="highlightedFields.nomor_pengujian" class="highlight-badge">Terdeteksi PDF</span>
            </label>
            <input 
              type="text" 
              v-model="form.nomor_pengujian" 
              :class="{ 'highlight-field': highlightedFields.nomor_pengujian }"
              required
            />
            <span v-if="autofillSource?.nomor_pengujian" class="original-val">Nilai asli PDF: "{{ autofillSource.nomor_pengujian }}"</span>
          </div>

          <div class="form-group">
            <label>
              Nama Pemohon
              <span v-if="highlightedFields.nama_pemohon" class="highlight-badge">Terdeteksi PDF</span>
            </label>
            <input 
              type="text" 
              v-model="form.nama_pemohon" 
              :class="{ 'highlight-field': highlightedFields.nama_pemohon }"
              required
            />
            <span v-if="autofillSource?.nama_pemohon" class="original-val">Nilai asli PDF: "{{ autofillSource.nama_pemohon }}"</span>
          </div>

          <div class="form-group">
            <label>
              Jenis Pengujian
              <span v-if="highlightedFields.jenis_pengujian" class="highlight-badge">Terdeteksi PDF</span>
            </label>
            <select 
              v-model="form.jenis_pengujian" 
              :class="{ 'highlight-field': highlightedFields.jenis_pengujian }"
              required
            >
              <option v-for="jenis in jenisPengujianList" :key="jenis" :value="jenis">
                {{ jenis }}
              </option>
            </select>
            <span v-if="autofillSource?.jenis_pengujian" class="original-val">Nilai asli PDF: "{{ autofillSource.jenis_pengujian }}"</span>
          </div>

          <div class="form-group">
            <label>Email Pemohon (Tetap)</label>
            <input type="email" v-model="form.email_pemohon" required />
          </div>

          <button type="submit" class="btn-primary btn-save flex-icon-center">
            <Save :size="16" /> Konfirmasi &amp; Simpan Perubahan
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.upload-hasil-view {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.toast-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px;
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

.upload-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  align-items: start;
}

@media (max-width: 900px) {
  .upload-layout {
    grid-template-columns: 1fr;
  }
}

.card {
  background: #ffffff;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01), 0 10px 15px -3px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9;
}

.card-header {
  display: flex;
  flex-direction: column;
  gap: 8px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 16px;
  margin-bottom: 24px;
}

.btn-back {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  padding: 0;
  width: flex;
  text-align: left;
  transition: color 0.2s ease;
}

.btn-back:hover {
  color: #1B4D3E;
}

.card-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.text-muted {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.pengujian-summary {
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 10px;
  margin-bottom: 24px;
  border: 1px solid #f1f5f9;
}

.info-item {
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
}

.badge-version {
  background: #e2e8f0;
  color: #334155;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
}

.upload-form, .autofill-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.alert {
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
}

.alert-danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
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
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.form-group input[type="file"] {
  padding: 12px;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  outline: none;
  font-size: 13px;
  transition: all 0.2s ease;
}

.form-group input[type="file"]:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.file-hint {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.4;
}

.progress-container {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.progress-bar-wrapper {
  background: #e2e8f0;
  height: 8px;
  border-radius: 10px;
  overflow: hidden;
}

.progress-bar-fill {
  background: #22c55e;
  height: 100%;
  transition: width 0.2s ease;
}

.progress-text {
  font-size: 12px;
  color: #64748b;
  text-align: center;
  font-weight: 600;
}

.btn-primary {
  background: #1B4D3E;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(27, 77, 62, 0.2);
  transition: all 0.2s ease;
  text-align: center;
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

.btn-upload {
  margin-top: 8px;
}

/* Autofill Card Specific Styles */
.autofill-badge {
  background: #ecfdf5;
  color: #065f46;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 30px;
  width: fit-content;
  border: 1px solid #a7f3d0;
  margin-bottom: 4px;
}

.highlight-badge {
  background: rgba(27, 77, 62, 0.08);
  color: #1B4D3E;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 700;
  border: 1px solid rgba(27, 77, 62, 0.2);
}

.highlight-field {
  background: rgba(27, 77, 62, 0.04);
  border-color: #1B4D3E !important;
  font-weight: 600;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.1);
}

.original-val {
  font-size: 11px;
  color: #94a3b8;
  font-style: italic;
  margin-top: -2px;
}

.btn-save {
  background: #10b981;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.btn-save:hover:not(:disabled) {
  background: #059669;
}

.autofill-form input, .autofill-form select {
  padding: 11px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  color: #1e293b;
  outline: none;
  transition: all 0.2s ease;
}

.autofill-form input:focus, .autofill-form select:focus {
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
}

.animate-spin {
  animation: spin 1s linear infinite;
  display: inline-block;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 900px) {
  .upload-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  .autofill-grid {
    grid-template-columns: 1fr;
  }
}
</style>

