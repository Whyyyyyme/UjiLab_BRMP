<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { AlertTriangle, CheckCircle2, Search, Plus, Upload, Edit, Mail, Trash2, FileText, Loader2, Download, RotateCcw, ChevronLeft, ChevronRight, FileQuestion } from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()

// State Data
const items = ref([])
const totalItems = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const isLoading = ref(false)
const showDeleteConfirm = ref(false)
const selectedItemToDelete = ref(null)

// State Filter & Search
const searchCari = ref('')
const filterJenis = ref('')
const filterStatus = ref('')
const filterBulan = ref('')
const filterTahun = ref('')

const months = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' }
]

// Dynamic year options: combines rolling window (current year - 5 to + 1) with any years present in dataset
const years = computed(() => {
  const currentYear = new Date().getFullYear()
  const yearSet = new Set()
  
  for (let y = currentYear + 1; y >= currentYear - 5; y--) {
    yearSet.add(y)
  }
  
  if (items.value && items.value.length > 0) {
    items.value.forEach(item => {
      if (item.tanggal_masuk) {
        const year = new Date(item.tanggal_masuk).getFullYear()
        if (!isNaN(year)) yearSet.add(year)
      } else if (item.created_at) {
        const year = new Date(item.created_at).getFullYear()
        if (!isNaN(year)) yearSet.add(year)
      }
    })
  }
  
  return Array.from(yearSet).sort((a, b) => b - a)
})

// State Modals
const showAddModal = ref(false)
const showEditModal = ref(false)
const showEmailModal = ref(false)

// Form States
const form = ref({
  id: null,
  nomor_pengujian: '',
  nama_pemohon: '',
  email_pemohon: '',
  jenis_pengujian: ''
})
const emailForm = ref({
  id: null,
  nomor_pengujian: '',
  email_pemohon: ''
})

const errorMessage = ref('')
const successMessage = ref('')

// List 9 Jenis Pengujian
const jenisPengujianList = [
  'Analisis SSR/RAPD',
  'Deteksi GMO',
  'Deteksi Virus secara Molekuler',
  'Analisis Ploidi Level',
  'Uji Mutu Benih (ISTA)',
  'Liofilisasi',
  'Enumerasi Total Mikroba Bakteri/Cendawan',
  'Deteksi Mikroba secara Molekuler (Bakteri/Cendawan)',
  'Uji Sensitivitas Bakteri'
]

// Fetch Data
const fetchData = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = {
      page,
      cari: searchCari.value,
      jenis_pengujian: filterJenis.value,
      status: filterStatus.value,
      bulan: filterBulan.value || undefined,
      tahun: filterTahun.value || undefined
    }
    const response = await api.get('/api/admin/pengujian', { params })
    items.value = response.data.data
    totalItems.value = response.data.total
    currentPage.value = response.data.current_page
    lastPage.value = response.data.last_page
  } catch (error) {
    errorMessage.value = 'Gagal memuat data pengujian.'
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

// State Pratinjau PDF
const showPreviewModal = ref(false)
const previewUrl = ref('')
const previewTitle = ref('')
const previewLoading = ref(false)
const activeDownloadInfo = ref({ id: null, type: '', nomorPengujian: '' })

// Buka modal pratinjau PDF
const openPreview = async (id, type, nomorPengujian) => {
  previewLoading.value = true
  showPreviewModal.value = true
  previewTitle.value = `Pratinjau ${type === 'laporan' ? 'Laporan' : 'Sertifikat'} - ${nomorPengujian}`
  activeDownloadInfo.value = { id, type, nomorPengujian }
  try {
    const response = await api.get(`/api/admin/pengujian/${id}/download/${type}`, {
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    if (previewUrl.value) {
      window.URL.revokeObjectURL(previewUrl.value)
    }
    previewUrl.value = window.URL.createObjectURL(blob)
  } catch (error) {
    console.error('Failed to load preview:', error)
    alert('Gagal memuat pratinjau berkas. Pastikan file tersedia di server.')
    showPreviewModal.value = false
  } finally {
    previewLoading.value = false
  }
}

// Tutup modal pratinjau PDF
const closePreview = () => {
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
  }
  previewUrl.value = ''
  showPreviewModal.value = false
}

// Picu download dari modal pratinjau
const triggerDownload = () => {
  if (!activeDownloadInfo.value.id) return
  const { id, type, nomorPengujian } = activeDownloadInfo.value
  downloadFile(id, type, nomorPengujian)
}

// Download berkas PDF (F-22)
const downloadFile = async (id, type, nomorPengujian) => {
  try {
    const response = await api.get(`/api/admin/pengujian/${id}/download/${type}`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${type === 'laporan' ? 'Laporan' : 'Sertifikat'}_${nomorPengujian}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Failed to download file:', error)
    alert('Gagal mengunduh berkas. Pastikan file tersedia di server.')
  }
}

// Watch filters
watch([filterJenis, filterStatus, filterBulan, filterTahun], () => {
  fetchData(1)
})

// Trigger search with delay (debounce)
let searchTimeout = null
const triggerSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchData(1)
  }, 500)
}

// Reset Filters
const resetFilters = () => {
  searchCari.value = ''
  filterJenis.value = ''
  filterStatus.value = ''
  filterBulan.value = ''
  filterTahun.value = ''
  fetchData(1)
}

// Add Pengujian State & Logic
const fileLaporan = ref(null)
const isParsing = ref(false)
const extractionMethod = ref('')
const autofilledFields = ref({
  nomor_pengujian: false,
  nama_pemohon: false,
  email_pemohon: false,
  jenis_pengujian: false
})

const handlePdfFileChange = async (event, type) => {
  const file = event.target.files[0]
  if (!file) return

  if (type === 'laporan') {
    fileLaporan.value = file
  }

  isParsing.value = true
  errorMessage.value = ''
  extractionMethod.value = ''
  
  const formData = new FormData()
  formData.append('file', file)

  try {
    const response = await api.post('/api/admin/pengujian/parse-pdf', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    const data = response.data
    extractionMethod.value = data.method || 'regex'
    
    if (data.nomor_pengujian) {
      form.value.nomor_pengujian = data.nomor_pengujian
      autofilledFields.value.nomor_pengujian = true
    }
    if (data.nama_pemohon) {
      form.value.nama_pemohon = data.nama_pemohon
      autofilledFields.value.nama_pemohon = true
    }
    if (data.email_pemohon) {
      form.value.email_pemohon = data.email_pemohon
      autofilledFields.value.email_pemohon = true
    }
    if (data.jenis_pengujian) {
      form.value.jenis_pengujian = data.jenis_pengujian
      autofilledFields.value.jenis_pengujian = true
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengekstrak berkas PDF.'
  } finally {
    isParsing.value = false
  }
}

const openAddModal = () => {
  form.value = {
    id: null,
    nomor_pengujian: '',
    nama_pemohon: '',
    email_pemohon: '',
    jenis_pengujian: ''
  }
  fileLaporan.value = null
  isParsing.value = false
  extractionMethod.value = ''
  autofilledFields.value = {
    nomor_pengujian: false,
    nama_pemohon: false,
    email_pemohon: false,
    jenis_pengujian: false
  }
  errorMessage.value = ''
  showAddModal.value = true
}

const handleAdd = async () => {
  errorMessage.value = ''
  try {
    const formData = new FormData()
    formData.append('nomor_pengujian', form.value.nomor_pengujian)
    formData.append('nama_pemohon', form.value.nama_pemohon)
    formData.append('email_pemohon', form.value.email_pemohon)
    formData.append('jenis_pengujian', form.value.jenis_pengujian)

    if (fileLaporan.value) {
      formData.append('file_laporan', fileLaporan.value)
    }

    await api.post('/api/admin/pengujian', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    successMessage.value = 'Data pengujian berhasil ditambahkan!'
    showAddModal.value = false
    fetchData(1)
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menyimpan data.'
  }
}

// Edit Metadata
const openEditModal = (item) => {
  form.value = {
    id: item.id,
    nomor_pengujian: item.nomor_pengujian,
    nama_pemohon: item.nama_pemohon,
    email_pemohon: item.email_pemohon,
    jenis_pengujian: item.jenis_pengujian
  }
  errorMessage.value = ''
  showEditModal.value = true
}

const handleEdit = async () => {
  errorMessage.value = ''
  try {
    await api.put(`/api/admin/pengujian/${form.value.id}`, form.value)
    successMessage.value = 'Metadata berhasil diperbarui!'
    showEditModal.value = false
    fetchData(currentPage.value)
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menyimpan data.'
  }
}

// Edit Email Pemohon
const openEmailModal = (item) => {
  emailForm.value = {
    id: item.id,
    nomor_pengujian: item.nomor_pengujian,
    email_pemohon: item.email_pemohon
  }
  errorMessage.value = ''
  showEmailModal.value = true
}

const handleEmailEdit = async () => {
  errorMessage.value = ''
  try {
    await api.patch(`/api/admin/pengujian/${emailForm.value.id}/email`, {
      email_pemohon: emailForm.value.email_pemohon
    })
    successMessage.value = 'Email pemohon berhasil dikoreksi!'
    showEmailModal.value = false
    fetchData(currentPage.value)
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menyimpan email.'
  }
}

// Soft Delete (Admin Only)
const handleDelete = (item) => {
  selectedItemToDelete.value = item
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  if (!selectedItemToDelete.value) return
  const item = selectedItemToDelete.value
  showDeleteConfirm.value = false
  try {
    await api.delete(`/api/admin/pengujian/${item.id}`)
    successMessage.value = 'Data pengujian berhasil dihapus!'
    fetchData(currentPage.value)
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menghapus data.'
    setTimeout(() => { errorMessage.value = '' }, 4000)
  } finally {
    selectedItemToDelete.value = null
  }
}

// Navigate to Upload page
const goToUpload = (item) => {
  router.push({ name: 'UploadHasil', params: { id: item.id } })
}

onMounted(() => {
  fetchData(1)
})
</script>

<template>
  <div class="data-pengujian-view">
    <!-- Success Banner -->
    <div v-if="successMessage" class="toast-success">
      <CheckCircle2 :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ successMessage }}
    </div>

    <!-- Toolbar: Search, Filters, Add Button -->
    <div class="card toolbar-card">
      <div class="toolbar-top">
        <div class="search-box">
          <span class="search-icon"><Search :size="18" /></span>
          <input 
            type="text" 
            v-model="searchCari" 
            @input="triggerSearch" 
            placeholder="Cari nomor pengujian atau nama pemohon..."
          />
        </div>
        <button @click="openAddModal" class="btn-primary flex-icon-center">
          <Plus :size="16" /> Tambah Pengujian
        </button>
      </div>

      <div class="toolbar-filters">
        <div class="filter-group">
          <label>Jenis Layanan</label>
          <select v-model="filterJenis">
            <option value="">Semua Jenis</option>
            <option v-for="jenis in jenisPengujianList" :key="jenis" :value="jenis">
              {{ jenis }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label>Status</label>
          <select v-model="filterStatus">
            <option value="">Semua Status</option>
            <option value="diproses">Dalam Proses</option>
            <option value="selesai">Selesai</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Bulan Masuk</label>
          <select v-model="filterBulan">
            <option value="">Semua Bulan</option>
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Tahun Masuk</label>
          <select v-model="filterTahun">
            <option value="">Semua Tahun</option>
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>

        <button @click="resetFilters" class="btn-secondary btn-reset">
          <RotateCcw :size="14" style="margin-right: 4px; display: inline-block; vertical-align: middle;" /> Reset
        </button>
      </div>
    </div>

    <!-- Data Table Card -->
    <div class="card table-card">
      <div v-if="isLoading" class="loading-overlay">
        <div class="spinner"><Loader2 class="animate-spin" :size="32" /></div>
        <p>Memuat data...</p>
      </div>

      <div v-else-if="items.length === 0" class="empty-state">
        <div class="empty-icon"><FileQuestion :size="36" /></div>
        <h3>Data Tidak Ditemukan</h3>
        <p>Tidak ada data pengujian yang sesuai dengan kriteria filter saat ini.</p>
      </div>

      <div v-else class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nomor Pengujian</th>
              <th>Nama Pemohon</th>
              <th>Email Pemohon</th>
              <th>Jenis Pengujian</th>
              <th>Versi</th>
              <th>Status</th>
              <th>Tanggal Masuk</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" class="table-row">
              <td class="text-bold">{{ item.nomor_pengujian }}</td>
              <td>{{ item.nama_pemohon }}</td>
              <td class="text-muted">{{ item.email_pemohon }}</td>
              <td>{{ item.jenis_pengujian }}</td>
              <td class="text-center"><span class="badge-version">v{{ item.versi }}</span></td>
              <td>
                <span :class="['badge-status', item.status]">
                  {{ item.status === 'selesai' ? 'Selesai' : 'Diproses' }}
                </span>
                <div v-if="item.status === 'selesai'" class="admin-file-links">
                  <a 
                    v-if="item.file_laporan" 
                    @click.prevent="openPreview(item.id, 'laporan', item.nomor_pengujian)" 
                    href="#" 
                    class="file-link" 
                    title="Pratinjau Laporan"
                  >
                    <span class="flex-icon-center" style="gap: 4px; display: inline-flex;"><FileText :size="12" /> Laporan</span>
                  </a>
                </div>
              </td>
              <td>{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}</td>
              <td>
                <div class="btn-actions">
                  <button @click="goToUpload(item)" class="action-btn upload flex-icon-center" title="Upload PDF Hasil Uji">
                    <Upload :size="12" /> Upload
                  </button>
                  <button @click="openEditModal(item)" class="action-btn edit flex-icon-center" title="Edit Metadata">
                    <Edit :size="12" /> Edit
                  </button>
                  <button @click="openEmailModal(item)" class="action-btn email flex-icon-center" title="Koreksi Email">
                    <Mail :size="12" /> Email
                  </button>
                  <button 
                    v-if="authStore.isAdmin" 
                    @click="handleDelete(item)" 
                    class="action-btn delete flex-icon-center" 
                    title="Hapus Pengujian (Soft Delete)"
                  >
                    <Trash2 :size="12" /> Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="lastPage > 1" class="pagination-footer">
        <span class="pagination-info">Menampilkan halaman {{ currentPage }} dari {{ lastPage }} ({{ totalItems }} data)</span>
        <div class="pagination-buttons">
          <button 
            @click="fetchData(currentPage - 1)" 
            :disabled="currentPage === 1" 
            class="page-btn flex-icon-center"
          >
            <ChevronLeft :size="16" /> Prev
          </button>
          <button 
            @click="fetchData(currentPage + 1)" 
            :disabled="currentPage === lastPage" 
            class="page-btn flex-icon-center"
          >
            Next <ChevronRight :size="16" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modals (Add / Edit / Email Correction) -->
    <!-- 1. Modal Tambah Pengujian -->
    <div v-if="showAddModal" class="modal-backdrop">
      <div class="modal-card">
        <div class="modal-header">
          <h3>Tambah Data Pengujian Baru</h3>
          <button @click="showAddModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="handleAdd" class="modal-form">
          <div v-if="errorMessage" class="modal-alert alert-danger">
            <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
          </div>

          <!-- Autofill via PDF Section -->
          <div class="autofill-section">
            <span class="section-title flex-icon-center" style="gap: 6px;"><FileText :size="16" /> Autofill via PDF</span>
            <div class="file-input-group">
              <label for="add-file-laporan">Laporan Hasil Pengujian</label>
              <input 
                type="file" 
                id="add-file-laporan" 
                accept=".pdf" 
                @change="handlePdfFileChange($event, 'laporan')"
                :disabled="isParsing"
              />
              <span v-if="fileLaporan" class="selected-file flex-icon-center" style="gap: 4px; display: inline-flex;"><FileText :size="14" /> {{ fileLaporan.name }}</span>
            </div>
            <div v-if="isParsing" class="parsing-loader">
              <span class="spinner"><Loader2 class="animate-spin" :size="16" /></span> Sedang memproses & menganalisis berkas PDF...
            </div>
          </div>

          <div class="form-group">
            <label>Nomor Pengujian</label>
            <input 
              type="text" 
              v-model="form.nomor_pengujian" 
              placeholder="Contoh: UJI-2026-001" 
              :class="{ 'autofilled-highlight': autofilledFields.nomor_pengujian }"
              required 
            />
          </div>

          <div class="form-group">
            <label>Nama Pemohon</label>
            <input 
              type="text" 
              v-model="form.nama_pemohon" 
              placeholder="Masukkan nama pemohon" 
              :class="{ 'autofilled-highlight': autofilledFields.nama_pemohon }"
              required 
            />
          </div>

          <div class="form-group">
            <label>Email Pemohon</label>
            <input 
              type="email" 
              v-model="form.email_pemohon" 
              placeholder="alamat@email.com" 
              :class="{ 'autofilled-highlight': autofilledFields.email_pemohon }"
              required 
            />
          </div>

          <div class="form-group">
            <label>Jenis Pengujian</label>
            <select 
              v-model="form.jenis_pengujian" 
              :class="{ 'autofilled-highlight': autofilledFields.jenis_pengujian }"
              required
            >
              <option value="" disabled>Pilih Jenis Pengujian</option>
              <option v-for="jenis in jenisPengujianList" :key="jenis" :value="jenis">
                {{ jenis }}
              </option>
            </select>
          </div>

          <div class="modal-footer">
            <button type="button" @click="showAddModal = false" class="btn-secondary" :disabled="isSaving">Batal</button>
            <button type="submit" class="btn-primary" :disabled="isParsing || isSaving">
              <span v-if="isSaving" class="flex-icon-center"><Loader2 class="animate-spin" :size="16" /> Menyimpan...</span>
              <span v-else>Simpan Data</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- 2. Modal Edit Metadata -->
    <div v-if="showEditModal" class="modal-backdrop">
      <div class="modal-card">
        <div class="modal-header">
          <h3>Edit Metadata Pengujian</h3>
          <button @click="showEditModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="handleEdit" class="modal-form">
          <div v-if="errorMessage" class="modal-alert alert-danger">
            <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
          </div>

          <div class="form-group">
            <label>Nomor Pengujian</label>
            <input type="text" v-model="form.nomor_pengujian" required />
          </div>

          <div class="form-group">
            <label>Nama Pemohon</label>
            <input type="text" v-model="form.nama_pemohon" required />
          </div>

          <div class="form-group">
            <label>Email Pemohon</label>
            <input type="email" v-model="form.email_pemohon" required />
          </div>

          <div class="form-group">
            <label>Jenis Pengujian</label>
            <select v-model="form.jenis_pengujian" required>
              <option v-for="jenis in jenisPengujianList" :key="jenis" :value="jenis">
                {{ jenis }}
              </option>
            </select>
          </div>

          <div class="modal-footer">
            <button type="button" @click="showEditModal = false" class="btn-secondary" :disabled="isSaving">Batal</button>
            <button type="submit" class="btn-primary" :disabled="isSaving">
              <span v-if="isSaving" class="flex-icon-center"><Loader2 class="animate-spin" :size="16" /> Menyimpan...</span>
              <span v-else>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- 3. Modal Koreksi Email -->
    <div v-if="showEmailModal" class="modal-backdrop">
      <div class="modal-card">
        <div class="modal-header">
          <h3>Koreksi Email Pemohon</h3>
          <button @click="showEmailModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="handleEmailEdit" class="modal-form">
          <div v-if="errorMessage" class="modal-alert alert-danger">
            <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
          </div>
          
          <p class="modal-desc">Koreksi ini khusus untuk menangani kesalahan ketik email agar notifikasi berhasil terkirim. Tindakan ini dicatat pada Log Aktivitas.</p>

          <div class="form-group">
            <label>Nomor Pengujian</label>
            <input type="text" :value="emailForm.nomor_pengujian" disabled />
          </div>

          <div class="form-group">
            <label>Email Pemohon Baru</label>
            <input type="email" v-model="emailForm.email_pemohon" required />
          </div>

          <div class="modal-footer">
            <button type="button" @click="showEmailModal = false" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">Update Email</button>
          </div>
        </form>
      </div>
    </div>

    <!-- 4. Modal Pratinjau PDF -->
    <div v-if="showPreviewModal" class="modal-backdrop">
      <div class="modal-card preview-modal-card">
        <div class="modal-header">
          <h3>{{ previewTitle }}</h3>
          <div class="header-actions">
            <button 
              v-if="previewUrl && !previewLoading" 
              @click="triggerDownload" 
              class="btn-primary btn-download-header flex-icon-center"
            >
              <Download :size="16" /> Unduh Berkas
            </button>
            <button @click="closePreview" class="close-btn">&times;</button>
          </div>
        </div>
        <div class="modal-body-preview">
          <div v-if="previewLoading" class="preview-loading">
            <div class="spinner"><Loader2 class="animate-spin" :size="32" /></div>
            <p>Memuat berkas PDF...</p>
          </div>
          <iframe 
            v-else-if="previewUrl" 
            :src="previewUrl" 
            class="pdf-iframe"
          ></iframe>
        </div>
      </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div v-if="showDeleteConfirm" class="modal-backdrop-confirm">
      <div class="confirm-card">
        <div class="confirm-header">
          <AlertTriangle :size="24" class="text-danger" />
          <h3>Hapus Data Pengujian</h3>
        </div>
        <div class="confirm-body">
          Apakah Anda yakin ingin menghapus data pengujian <strong>{{ selectedItemToDelete?.nomor_pengujian }}</strong>? Tindakan ini bersifat soft-delete dan dapat dikembalikan oleh administrator jika diperlukan.
        </div>
        <div class="confirm-footer">
          <button @click="showDeleteConfirm = false" class="btn-cancel">Batal</button>
          <button @click="confirmDelete" class="btn-confirm-delete">Hapus Data</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.data-pengujian-view {
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

.card {
  background: #ffffff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01), 0 10px 15px -3px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9;
}

.toolbar-card {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.toolbar-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.search-box {
  flex: 1;
  max-width: 500px;
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 16px;
  color: #94a3b8;
  font-size: 16px;
}

.search-box input {
  width: 100%;
  padding: 12px 16px 12px 46px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 14px;
  color: #1e293b;
  transition: all 0.2s ease;
  background: #f8fafc;
}

.search-box input:focus {
  outline: none;
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
}

.toolbar-filters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 100px;
  gap: 16px;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-group label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}

.filter-group select, .filter-group input {
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  background: #ffffff;
  color: #1e293b;
  outline: none;
}

.date-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
}

.date-inputs input {
  width: 100%;
}

.date-inputs span {
  font-size: 12px;
  color: #94a3b8;
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
}

.btn-primary:hover {
  background: #13382D;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-reset {
  height: 38px;
}

.table-card {
  position: relative;
  min-height: 200px;
}

.loading-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
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

.empty-state {
  text-align: center;
  padding: 48px;
  color: #64748b;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: #334155;
  font-size: 18px;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 14px;
}

.data-table th {
  background: #f8fafc;
  padding: 16px;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 16px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.table-row:hover {
  background: #f8fafc;
}

.text-bold {
  font-weight: 600;
  color: #0f172a;
}

.text-muted {
  color: #64748b;
}

.text-center {
  text-align: center;
}

.badge-version {
  background: #f1f5f9;
  color: #475569;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.badge-status {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 30px;
  font-size: 12px;
  font-weight: 600;
}

.badge-status.diproses {
  background: #fefce8;
  color: #854d0e;
}

.badge-status.selesai {
  background: #f0fdf4;
  color: #166534;
}

.btn-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.action-btn.upload {
  background: rgba(27, 77, 62, 0.08);
  color: #1B4D3E;
}

.action-btn.upload:hover {
  background: rgba(27, 77, 62, 0.15);
}

.action-btn.edit {
  background: #f8fafc;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.action-btn.edit:hover {
  background: #f1f5f9;
}

.action-btn.email {
  background: #fffbeb;
  color: #b45309;
}

.action-btn.email:hover {
  background: #fef3c7;
}

.action-btn.delete {
  background: #fef2f2;
  color: #b91c1c;
}

.action-btn.delete:hover {
  background: #fee2e2;
}

.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

.pagination-info {
  font-size: 13px;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  gap: 8px;
}

.page-btn {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.page-btn:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal Styling */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(27, 43, 37, 0.65); /* Forest green tint backdrop */
  backdrop-filter: blur(8px); /* Richer glassmorphism blur */
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 16px;
}

.modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 25px 50px -12px rgba(27, 43, 37, 0.25); /* Warmer shadow */
  border: 1px solid #DCDACD; /* Clean, warm border to prevent blending in */
  overflow: hidden;
  animation: modalEnter 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalEnter {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.modal-header {
  padding: 20px 24px;
  background: rgba(27, 77, 62, 0.02); /* Subtle green tint */
  border-bottom: 1px solid #DCDACD;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1B4D3E; /* Brand primary green */
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #5B6055;
  transition: color 0.2s ease;
  line-height: 1;
}

.close-btn:hover {
  color: #A8382C; /* Brick red */
}

.modal-form {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-desc {
  font-size: 13px;
  color: #5B6055;
  line-height: 1.5;
  margin: 0 0 8px 0;
}

.modal-alert {
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
}

.modal-alert.alert-danger {
  background: rgba(168, 56, 44, 0.08);
  border: 1px solid rgba(168, 56, 44, 0.2);
  color: #A8382C;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #22261F; /* High contrast ink text */
}

.form-group input, .form-group select {
  padding: 11px 14px;
  border: 1.5px solid #DCDACD; /* Higher contrast border */
  border-radius: 8px;
  font-size: 14px;
  color: #22261F;
  background: #ffffff;
  outline: none;
  transition: all 0.2s ease;
}

.form-group input:focus, .form-group select:focus {
  border-color: #1B4D3E; /* Primary green focus */
  box-shadow: 0 0 0 3px rgba(184, 144, 31, 0.25); /* Gold focus ring */
}

.form-group input:disabled {
  background: #F6F5F0; /* Warm gray-cream background */
  color: #5B6055;
  border-color: #DCDACD;
  cursor: not-allowed;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 12px;
  padding-top: 16px;
  border-top: 1px solid #DCDACD;
}

/* Modal Inner Button overrides */
.modal-card .btn-primary {
  background: #1B4D3E;
  box-shadow: 0 4px 12px rgba(27, 77, 62, 0.2);
}

.modal-card .btn-primary:hover {
  background: #123328;
}

.modal-card .btn-secondary {
  background: transparent;
  color: #1B4D3E;
  border: 1.5px solid #1B4D3E;
}

.modal-card .btn-secondary:hover {
  background: rgba(27, 77, 62, 0.05);
  color: #123328;
}

.admin-file-links {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 6px;
}

.file-link {
  font-size: 11px;
  color: #1B4D3E;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: color 0.2s ease;
}

.file-link:hover {
  color: #13382D;
  text-decoration: underline;
}

.preview-modal-card {
  max-width: 900px;
  width: 90%;
  display: flex;
  flex-direction: column;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-download-header {
  padding: 8px 16px;
  font-size: 13px;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.15);
}

.modal-body-preview {
  padding: 16px;
  background: #f8fafc;
  border-radius: 0 0 16px 16px;
}

.preview-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px;
  color: #64748b;
  gap: 12px;
}

.pdf-iframe {
  width: 100%;
  height: 70vh;
  border: none;
  border-radius: 8px;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}
/* Autofill Section Styles */
.autofill-section {
  background: rgba(27, 77, 62, 0.04); /* Light green tint background */
  border: 1.5px dashed rgba(27, 77, 62, 0.25); /* Primary green dashed border */
  padding: 16px;
  border-radius: 12px;
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.autofill-section .section-title {
  font-size: 13px;
  font-weight: 600;
  color: #1B4D3E; /* Primary green title */
}

.file-input-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.file-input-group label {
  font-size: 12px;
  font-weight: 500;
  color: #5B6055;
}

.file-input-group input[type="file"] {
  background: #ffffff;
  border: 1.5px solid #DCDACD;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  cursor: pointer;
  color: #22261F;
  transition: border-color 0.2s;
}

.file-input-group input[type="file"]:hover {
  border-color: #94a3b8;
}

.selected-file {
  font-size: 11px;
  color: #10b981;
  font-weight: 500;
  word-break: break-all;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.remove-file-btn {
  background: transparent;
  border: none;
  color: #ef4444;
  font-size: 14px;
  font-weight: bold;
  cursor: pointer;
  padding: 0 4px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  transition: background-color 0.2s, color 0.2s;
}

.remove-file-btn:hover {
  background-color: rgba(239, 68, 68, 0.1);
  color: #dc2626;
}

.parsing-loader {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #1B4D3E;
  font-weight: 500;
}

.parsing-loader .spinner {
  animation: pulse-spinner 1.5s infinite;
  display: inline-block;
}

@keyframes pulse-spinner {
  0% { transform: scale(1); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

.autofilled-highlight {
  border-color: #10b981 !important;
  background-color: rgba(16, 185, 129, 0.05) !important;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
  transition: all 0.3s ease;
}
.extraction-status {
  margin-top: 8px;
  display: flex;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  font-size: 11px;
  font-weight: 600;
  border-radius: 6px;
}

.status-ai {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-regex {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.2);
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

/* Custom Confirmation Modal Styles */
.modal-backdrop-confirm {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.2s ease-out;
}

.confirm-card {
  background: #ffffff;
  border-radius: 16px;
  width: 90%;
  max-width: 400px;
  padding: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid #e2e8f0;
  animation: scaleIn 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.confirm-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.confirm-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
}

.text-danger {
  color: #ef4444;
}

.confirm-body {
  font-size: 14px;
  color: #64748b;
  line-height: 1.5;
  margin-bottom: 24px;
}

.confirm-body strong {
  color: #0f172a;
}

.confirm-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.confirm-footer button {
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.btn-cancel:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.btn-confirm-delete {
  background: #ef4444;
  color: #ffffff;
  border: none;
}

.btn-confirm-delete:hover {
  background: #dc2626;
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleIn {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

/* Mobile Responsiveness Rules */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .header-title h2 {
    font-size: 20px;
  }

  .header-actions {
    width: 100%;
  }

  .btn-primary {
    width: 100%;
    justify-content: center;
  }

  .toolbar-card {
    padding: 16px;
  }

  .toolbar-filters {
    grid-template-columns: 1fr;
  }

  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0 -16px;
    padding: 0 16px;
  }

  .modal-card, .preview-modal-card {
    width: 95%;
    padding: 16px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-form {
    grid-template-columns: 1fr;
  }
}
</style>










