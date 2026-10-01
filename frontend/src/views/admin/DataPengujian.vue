<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { AlertTriangle, CheckCircle2, Search, Plus, Upload, Edit, Trash2, FileText, Loader2, Download, RotateCcw, ChevronLeft, ChevronRight, FileQuestion, X, Eye, EyeOff, ExternalLink, Mail } from '@lucide/vue'
import JenisPengujianSelect from '../../components/admin/JenisPengujianSelect.vue'
import { KATEGORI_PENGUJIAN_LIST } from '../../constants/jenisPengujian'

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

// Form States
const form = ref({
  id: null,
  nomor_pengujian: '',
  nama_pemohon: '',
  email_pemohon: '',
  jenis_pengujian: ''
})

const errorMessage = ref('')
const successMessage = ref('')

// List Kategori Pengujian untuk Filter Toolbar
const jenisPengujianList = KATEGORI_PENGUJIAN_LIST

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
    errorMessage.value = 'Gagal memuat pratinjau berkas. Pastikan file tersedia di server.'
    setTimeout(() => { errorMessage.value = '' }, 4000)
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
    const cleanNomor = String(nomorPengujian || '').replace(/[/\\?%*:|"<>]/g, '_')
    link.setAttribute('download', `${type === 'laporan' ? 'Laporan' : 'Sertifikat'}_${cleanNomor}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download file:', error)
    errorMessage.value = 'Gagal mengunduh berkas. Pastikan file tersedia di server.'
    setTimeout(() => { errorMessage.value = '' }, 4000)
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

// Clear Search Input (UX 2 C)
const clearSearch = () => {
  searchCari.value = ''
  fetchData(1)
}

// Add Pengujian State & Logic
const fileLaporan = ref(null)
const isParsing = ref(false)
const isSaving = ref(false)
const extractionMethod = ref('')
const addPdfPreviewUrl = ref('')
const showAddPdfPreview = ref(false)
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
    // Generate URL objek lokal instan untuk pratinjau PDF langsung di browser
    if (addPdfPreviewUrl.value) {
      window.URL.revokeObjectURL(addPdfPreviewUrl.value)
    }
    addPdfPreviewUrl.value = window.URL.createObjectURL(file)
    showAddPdfPreview.value = true
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

const openAddPdfInNewTab = () => {
  if (addPdfPreviewUrl.value) {
    window.open(addPdfPreviewUrl.value, '_blank')
  }
}

const removeAddFileLaporan = () => {
  if (addPdfPreviewUrl.value) {
    window.URL.revokeObjectURL(addPdfPreviewUrl.value)
    addPdfPreviewUrl.value = ''
  }
  fileLaporan.value = null
  showAddPdfPreview.value = false
  const fileInput = document.getElementById('add-file-laporan')
  if (fileInput) fileInput.value = ''
}

const closeAddModal = () => {
  if (addPdfPreviewUrl.value) {
    window.URL.revokeObjectURL(addPdfPreviewUrl.value)
    addPdfPreviewUrl.value = ''
  }
  showAddPdfPreview.value = false
  showAddModal.value = false
}

const openAddModal = () => {
  form.value = {
    id: null,
    nomor_pengujian: '',
    nama_pemohon: '',
    email_pemohon: '',
    jenis_pengujian: ''
  }
  if (addPdfPreviewUrl.value) {
    window.URL.revokeObjectURL(addPdfPreviewUrl.value)
    addPdfPreviewUrl.value = ''
  }
  fileLaporan.value = null
  showAddPdfPreview.value = false
  isParsing.value = false
  isSaving.value = false
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
  isSaving.value = true
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
    closeAddModal()
    fetchData(1)
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menyimpan data.'
  } finally {
    isSaving.value = false
  }
}

// Edit Metadata
const openEditModal = (item) => {
  form.value = {
    id: item.id,
    nomor_pengujian: item.nomor_pengujian,
    nama_pemohon: item.nama_pemohon,
    email_pemohon: item.email_pemohon,
    jenis_pengujian: item.jenis_pengujian,
    status: item.status
  }
  errorMessage.value = ''
  showEditModal.value = true
}

const handleEdit = async () => {
  errorMessage.value = ''
  try {
    const response = await api.put(`/api/admin/pengujian/${form.value.id}`, form.value)
    successMessage.value = response.data?.message || 'Metadata berhasil diperbarui!'
    showEditModal.value = false
    fetchData(currentPage.value)
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menyimpan data.'
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

// Upload / Revisi Modal State & Logic
const showUploadModal = ref(false)
const selectedItemForUpload = ref(null)
const uploadFile = ref(null)
const uploadPdfPreviewUrl = ref('')
const showUploadPdfPreview = ref(false)
const isParsingUploadPdf = ref(false)
const isSavingUpload = ref(false)
const uploadExtractionMethod = ref('')
const uploadForm = ref({
  id: null,
  nomor_pengujian: '',
  nama_pemohon: '',
  email_pemohon: '',
  jenis_pengujian: '',
  versi: 1,
  status: 'diproses'
})
const uploadAutofilled = ref({
  nomor_pengujian: false,
  nama_pemohon: false,
  email_pemohon: false,
  jenis_pengujian: false
})
const uploadDifferences = ref({
  nomor_pengujian: false,
  nama_pemohon: false,
  email_pemohon: false,
  jenis_pengujian: false
})

const openUploadModal = (item) => {
  selectedItemForUpload.value = item
  uploadForm.value = {
    id: item.id,
    nomor_pengujian: item.nomor_pengujian || '',
    nama_pemohon: item.nama_pemohon || '',
    email_pemohon: item.email_pemohon || '',
    jenis_pengujian: item.jenis_pengujian || '',
    versi: item.versi || 1,
    status: item.status || 'diproses'
  }
  uploadAutofilled.value = {
    nomor_pengujian: false,
    nama_pemohon: false,
    email_pemohon: false,
    jenis_pengujian: false
  }
  uploadDifferences.value = {
    nomor_pengujian: false,
    nama_pemohon: false,
    email_pemohon: false,
    jenis_pengujian: false
  }
  if (uploadPdfPreviewUrl.value) {
    window.URL.revokeObjectURL(uploadPdfPreviewUrl.value)
    uploadPdfPreviewUrl.value = ''
  }
  uploadFile.value = null
  showUploadPdfPreview.value = false
  isParsingUploadPdf.value = false
  isSavingUpload.value = false
  uploadExtractionMethod.value = ''
  errorMessage.value = ''
  showUploadModal.value = true
}

const closeUploadModal = () => {
  if (uploadPdfPreviewUrl.value) {
    window.URL.revokeObjectURL(uploadPdfPreviewUrl.value)
    uploadPdfPreviewUrl.value = ''
  }
  uploadFile.value = null
  showUploadPdfPreview.value = false
  showUploadModal.value = false
  selectedItemForUpload.value = null
}

const removeUploadFile = () => {
  if (uploadPdfPreviewUrl.value) {
    window.URL.revokeObjectURL(uploadPdfPreviewUrl.value)
    uploadPdfPreviewUrl.value = ''
  }
  uploadFile.value = null
  showUploadPdfPreview.value = false
  uploadExtractionMethod.value = ''
  uploadAutofilled.value = {
    nomor_pengujian: false,
    nama_pemohon: false,
    email_pemohon: false,
    jenis_pengujian: false
  }
  uploadDifferences.value = {
    nomor_pengujian: false,
    nama_pemohon: false,
    email_pemohon: false,
    jenis_pengujian: false
  }
  if (selectedItemForUpload.value) {
    uploadForm.value.nomor_pengujian = selectedItemForUpload.value.nomor_pengujian || ''
    uploadForm.value.nama_pemohon = selectedItemForUpload.value.nama_pemohon || ''
    uploadForm.value.email_pemohon = selectedItemForUpload.value.email_pemohon || ''
    uploadForm.value.jenis_pengujian = selectedItemForUpload.value.jenis_pengujian || ''
  }
  const fileInput = document.getElementById('upload-modal-file')
  if (fileInput) fileInput.value = ''
}

const openUploadPdfInNewTab = () => {
  if (uploadPdfPreviewUrl.value) {
    window.open(uploadPdfPreviewUrl.value, '_blank')
  }
}

const handleUploadFileChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  uploadFile.value = file
  
  if (uploadPdfPreviewUrl.value) {
    window.URL.revokeObjectURL(uploadPdfPreviewUrl.value)
  }
  uploadPdfPreviewUrl.value = window.URL.createObjectURL(file)
  showUploadPdfPreview.value = true

  isParsingUploadPdf.value = true
  errorMessage.value = ''
  uploadExtractionMethod.value = ''

  const formData = new FormData()
  formData.append('file', file)

  try {
    const response = await api.post('/api/admin/pengujian/parse-pdf', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    const data = response.data
    uploadExtractionMethod.value = data.method || 'regex'

    const original = selectedItemForUpload.value || {}

    if (data.nomor_pengujian) {
      uploadForm.value.nomor_pengujian = data.nomor_pengujian
      uploadAutofilled.value.nomor_pengujian = true
      uploadDifferences.value.nomor_pengujian = (data.nomor_pengujian !== original.nomor_pengujian)
    }
    if (data.nama_pemohon) {
      uploadForm.value.nama_pemohon = data.nama_pemohon
      uploadAutofilled.value.nama_pemohon = true
      uploadDifferences.value.nama_pemohon = (data.nama_pemohon !== original.nama_pemohon)
    }
    if (data.email_pemohon) {
      uploadForm.value.email_pemohon = data.email_pemohon
      uploadAutofilled.value.email_pemohon = true
      uploadDifferences.value.email_pemohon = (data.email_pemohon !== original.email_pemohon)
    }
    if (data.jenis_pengujian) {
      uploadForm.value.jenis_pengujian = data.jenis_pengujian
      uploadAutofilled.value.jenis_pengujian = true
      uploadDifferences.value.jenis_pengujian = (data.jenis_pengujian !== original.jenis_pengujian)
    }
  } catch (error) {
    console.error(error)
    errorMessage.value = error.response?.data?.message || 'Gagal mengekstrak berkas PDF.'
  } finally {
    isParsingUploadPdf.value = false
  }
}

const handleConfirmUpload = async () => {
  if (!uploadFile.value) {
    errorMessage.value = 'Silakan pilih berkas PDF terlebih dahulu.'
    return
  }

  isSavingUpload.value = true
  errorMessage.value = ''

  const formData = new FormData()
  formData.append('file_laporan', uploadFile.value)
  formData.append('nomor_pengujian', uploadForm.value.nomor_pengujian)
  formData.append('nama_pemohon', uploadForm.value.nama_pemohon)
  formData.append('email_pemohon', uploadForm.value.email_pemohon)
  formData.append('jenis_pengujian', uploadForm.value.jenis_pengujian)

  try {
    const response = await api.post(`/api/admin/pengujian/${uploadForm.value.id}/upload`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    const newVersi = response.data?.versi || (uploadForm.value.status === 'selesai' ? (uploadForm.value.versi + 1) : 1)
    successMessage.value = `Berkas hasil pengujian berhasil disimpan & diunggah (Versi v${newVersi})!`
    closeUploadModal()
    fetchData(currentPage.value)
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengunggah berkas pengujian.'
  } finally {
    isSavingUpload.value = false
  }
}

const closeAllModals = () => {
  if (showAddModal.value) closeAddModal()
  if (showUploadModal.value) closeUploadModal()
  if (showEditModal.value) showEditModal.value = false
  if (showPreviewModal.value) closePreview()
  if (showDeleteConfirm.value) showDeleteConfirm.value = false
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    closeAllModals()
  }
}

onMounted(() => {
  fetchData(1)
  window.addEventListener('keydown', handleKeyDown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <div class="data-pengujian-view">
    <!-- Success Banner -->
    <div v-if="successMessage" class="toast-success">
      <CheckCircle2 :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ successMessage }}
    </div>

    <!-- Error Banner -->
    <div v-if="errorMessage" class="toast-error">
      <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
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
          <button 
            v-if="searchCari" 
            type="button" 
            @click="clearSearch" 
            class="btn-clear-search" 
            title="Bersihkan pencarian"
          >
            <X :size="14" />
          </button>
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
            <option value="diproses">Menunggu Unggah Berkas</option>
            <option value="selesai">Selesai &amp; Terverifikasi</option>
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
      <div v-if="isLoading" class="table-responsive">
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
            <tr v-for="n in 5" :key="n" class="skeleton-row">
              <td><div class="skeleton-bar" style="width: 75%;"></div></td>
              <td><div class="skeleton-bar" style="width: 80%;"></div></td>
              <td><div class="skeleton-bar" style="width: 85%;"></div></td>
              <td><div class="skeleton-bar" style="width: 65%;"></div></td>
              <td class="text-center"><div class="skeleton-bar" style="width: 40px;"></div></td>
              <td><div class="skeleton-bar" style="width: 70px;"></div></td>
              <td><div class="skeleton-bar" style="width: 80%;"></div></td>
              <td><div class="skeleton-bar" style="width: 90px;"></div></td>
            </tr>
          </tbody>
        </table>
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
                  {{ item.status === 'selesai' ? 'Selesai' : 'Menunggu Unggah' }}
                </span>
                <div v-if="item.status === 'selesai' && item.file_laporan" class="status-extra-group">
                  <div class="admin-file-links">
                    <a 
                      @click.prevent="openPreview(item.id, 'laporan', item.nomor_pengujian)" 
                      href="#" 
                      class="file-link" 
                      title="Pratinjau Laporan"
                    >
                      <span class="flex-icon-center" style="gap: 4px; display: inline-flex;"><FileText :size="12" /> Laporan</span>
                    </a>
                  </div>
                </div>
              </td>
              <td>{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}</td>
              <td>
                <div class="btn-actions">
                  <button @click="openUploadModal(item)" class="action-btn upload flex-icon-center" :title="item.status === 'selesai' ? 'Revisi / Unggah Ulang Berkas PDF' : 'Unggah Berkas PDF Hasil Uji'">
                    <Upload :size="12" /> {{ item.status === 'selesai' ? 'Revisi' : 'Unggah' }}
                  </button>
                  <button @click="openEditModal(item)" class="action-btn edit flex-icon-center" title="Edit Metadata">
                    <Edit :size="12" /> Edit
                  </button>
                  <button 
                    v-if="authStore.isAdmin" 
                    @click="handleDelete(item)" 
                    class="action-btn delete flex-icon-center" 
                    title="Hapus Data Pengujian"
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
            <ChevronLeft :size="16" /> Sebelumnya
          </button>
          <button 
            @click="fetchData(currentPage + 1)" 
            :disabled="currentPage === lastPage" 
            class="page-btn flex-icon-center"
          >
            Berikutnya <ChevronRight :size="16" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modals (Add / Edit / Email Correction) -->
    <!-- 1. Modal Tambah Pengujian -->
    <div v-if="showAddModal" class="modal-backdrop" @click.self="closeAddModal">
      <div class="modal-card" :class="{ 'modal-card-split': fileLaporan && showAddPdfPreview }">
        <div class="modal-header">
          <div class="modal-header-info">
            <h3>Tambah Data Pengujian Baru</h3>
            <span v-if="fileLaporan" class="file-tag-pill">
              <FileText :size="13" /> {{ fileLaporan.name }}
            </span>
          </div>
          <div class="modal-header-actions">
            <button 
              v-if="fileLaporan" 
              type="button" 
              @click="showAddPdfPreview = !showAddPdfPreview" 
              class="btn-toggle-preview"
              :title="showAddPdfPreview ? 'Sembunyikan panel pratinjau' : 'Tampilkan panel pratinjau PDF'"
            >
              <component :is="showAddPdfPreview ? EyeOff : Eye" :size="15" />
              <span>{{ showAddPdfPreview ? 'Tutup Preview' : 'Preview Berkas' }}</span>
            </button>
            <button @click="closeAddModal" class="close-btn">&times;</button>
          </div>
        </div>

        <div class="modal-split-container" :class="{ 'with-preview': fileLaporan && showAddPdfPreview }">
          
          <!-- SISI KIRI: PRATINJAU BERKAS PDF UNTUK CROSS-CHECK DATA AUTOFILL -->
          <div v-if="fileLaporan && showAddPdfPreview" class="modal-preview-pane">
            <div class="preview-pane-bar">
              <div class="preview-bar-file">
                <FileText :size="15" class="preview-bar-icon" />
                <span class="preview-bar-name" :title="fileLaporan.name">{{ fileLaporan.name }}</span>
                <span class="preview-bar-size">({{ (fileLaporan.size / 1024).toFixed(1) }} KB)</span>
              </div>
              <div class="preview-bar-tools">
                <button 
                  type="button" 
                  @click="openAddPdfInNewTab" 
                  class="btn-tool-tab"
                  title="Buka dokumen PDF di tab baru browser"
                >
                  <ExternalLink :size="13" />
                  <span>Tab Baru</span>
                </button>
              </div>
            </div>

            <div class="preview-pane-viewer">
              <div v-if="isParsing" class="preview-loading-overlay">
                <Loader2 class="animate-spin text-brand" :size="28" />
                <p>Menganalisis dokumen & mengekstrak data...</p>
              </div>
              <iframe 
                v-if="addPdfPreviewUrl" 
                :src="addPdfPreviewUrl" 
                class="add-pdf-iframe"
                title="Pratinjau Berkas PDF Tambah Pengujian"
              ></iframe>
            </div>

            <div class="preview-pane-hint">
              <span>💡 <strong>Verifikasi Dokumen:</strong> Cocokkan nilai nomor pengujian, nama pemohon, dan jenis pengujian pada PDF dengan formulir di samping.</span>
            </div>
          </div>

          <!-- SISI KANAN: FORMULIR INPUT & AUTOFILL -->
          <div class="modal-form-pane">
            <form @submit.prevent="handleAdd" class="modal-form">
              <div v-if="errorMessage" class="modal-alert alert-danger">
                <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
              </div>

              <!-- Autofill via PDF Section -->
              <div class="autofill-section">
                <div class="autofill-header-row">
                  <span class="section-title flex-icon-center" style="gap: 6px;">
                    <FileText :size="16" /> Ekstraksi Otomatis via PDF
                  </span>
                  <span v-if="extractionMethod" class="extraction-badge">
                    ⚡ Terisi Otomatis ({{ extractionMethod === 'ai' ? 'Smart AI' : 'Regex PDF' }})
                  </span>
                </div>

                <div class="file-input-group">
                  <label for="add-file-laporan">Laporan Hasil Pengujian (PDF)</label>
                  <div class="file-input-action-row">
                    <input 
                      type="file" 
                      id="add-file-laporan" 
                      accept=".pdf" 
                      @change="handlePdfFileChange($event, 'laporan')"
                      :disabled="isParsing"
                    />
                    <button 
                      v-if="fileLaporan && !showAddPdfPreview" 
                      type="button" 
                      @click="showAddPdfPreview = true" 
                      class="btn-preview-shortcut"
                    >
                      <Eye :size="13" /> Buka Preview
                    </button>
                    <button 
                      v-if="fileLaporan" 
                      type="button" 
                      @click="removeAddFileLaporan" 
                      class="btn-remove-selected-file"
                      title="Hapus / ganti berkas PDF"
                    >
                      <X :size="14" />
                    </button>
                  </div>
                  <span v-if="fileLaporan" class="selected-file flex-icon-center" style="gap: 4px; display: inline-flex;">
                    <CheckCircle2 :size="14" style="color: #10b981;" /> {{ fileLaporan.name }}
                  </span>
                </div>
                <div v-if="isParsing" class="parsing-loader">
                  <span class="spinner"><Loader2 class="animate-spin" :size="16" /></span> Sedang memproses & menganalisis berkas PDF...
                </div>
              </div>

              <div class="form-group">
                <div class="form-label-row">
                  <label>Nomor Pengujian</label>
                  <span v-if="autofilledFields.nomor_pengujian" class="badge-autofill">Ekstraksi PDF</span>
                </div>
                <input 
                  type="text" 
                  v-model="form.nomor_pengujian" 
                  placeholder="Contoh: UJI-2026-001" 
                  :class="{ 'autofilled-highlight': autofilledFields.nomor_pengujian }"
                  required 
                />
              </div>

              <div class="form-group">
                <div class="form-label-row">
                  <label>Nama Pemohon</label>
                  <span v-if="autofilledFields.nama_pemohon" class="badge-autofill">Ekstraksi PDF</span>
                </div>
                <input 
                  type="text" 
                  v-model="form.nama_pemohon" 
                  placeholder="Masukkan nama pemohon" 
                  :class="{ 'autofilled-highlight': autofilledFields.nama_pemohon }"
                  required 
                />
              </div>

              <div class="form-group">
                <div class="form-label-row">
                  <label>Email Pemohon</label>
                  <span v-if="autofilledFields.email_pemohon" class="badge-autofill">Ekstraksi PDF</span>
                </div>
                <input 
                  type="email" 
                  v-model="form.email_pemohon" 
                  placeholder="alamat@email.com" 
                  :class="{ 'autofilled-highlight': autofilledFields.email_pemohon }"
                  required 
                />
              </div>

              <div class="form-group">
                <div class="form-label-row">
                  <label>Jenis Pengujian &amp; Parameter Uji</label>
                  <span v-if="autofilledFields.jenis_pengujian" class="badge-autofill">Ekstraksi PDF</span>
                </div>
                <JenisPengujianSelect
                  v-model="form.jenis_pengujian"
                  :highlighted="autofilledFields.jenis_pengujian"
                  required
                />
              </div>

              <div class="modal-footer">
                <button type="button" @click="closeAddModal" class="btn-secondary" :disabled="isSaving">Batal</button>
                <button type="submit" class="btn-primary" :disabled="isParsing || isSaving">
                  <span v-if="isSaving" class="flex-icon-center"><Loader2 class="animate-spin" :size="16" /> Menyimpan...</span>
                  <span v-else>Simpan Data</span>
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>

    <!-- 1B. Modal Unggah / Revisi Berkas Pengujian -->
    <div v-if="showUploadModal" class="modal-backdrop" @click.self="closeUploadModal">
      <div class="modal-card" :class="{ 'modal-card-split': uploadFile && showUploadPdfPreview }">
        <div class="modal-header">
          <div class="modal-header-info">
            <h3>{{ selectedItemForUpload?.status === 'selesai' ? 'Revisi Berkas Hasil Pengujian' : 'Unggah Berkas Hasil Pengujian' }}</h3>
            <span class="badge-version" style="font-size: 11px; padding: 2px 8px;">
              {{ selectedItemForUpload?.status === 'selesai' ? `Versi Saat Ini: v${selectedItemForUpload?.versi || 1}` : 'Dokumen Baru (v1)' }}
            </span>
            <span v-if="uploadFile" class="file-tag-pill">
              <FileText :size="13" /> {{ uploadFile.name }}
            </span>
          </div>
          <div class="modal-header-actions">
            <button 
              v-if="uploadFile" 
              type="button" 
              @click="showUploadPdfPreview = !showUploadPdfPreview" 
              class="btn-toggle-preview"
              :title="showUploadPdfPreview ? 'Sembunyikan panel pratinjau' : 'Tampilkan panel pratinjau PDF'"
            >
              <component :is="showUploadPdfPreview ? EyeOff : Eye" :size="15" />
              <span>{{ showUploadPdfPreview ? 'Tutup Preview' : 'Preview Berkas' }}</span>
            </button>
            <button @click="closeUploadModal" class="close-btn">&times;</button>
          </div>
        </div>

        <div class="modal-split-container" :class="{ 'with-preview': uploadFile && showUploadPdfPreview }">
          
          <!-- SISI KIRI: PRATINJAU BERKAS PDF -->
          <div v-if="uploadFile && showUploadPdfPreview" class="modal-preview-pane">
            <div class="preview-pane-bar">
              <div class="preview-bar-file">
                <FileText :size="15" class="preview-bar-icon" />
                <span class="preview-bar-name" :title="uploadFile.name">{{ uploadFile.name }}</span>
                <span class="preview-bar-size">({{ (uploadFile.size / 1024).toFixed(1) }} KB)</span>
              </div>
              <div class="preview-bar-tools">
                <button 
                  type="button" 
                  @click="openUploadPdfInNewTab" 
                  class="btn-tool-tab"
                  title="Buka dokumen PDF di tab baru browser"
                >
                  <ExternalLink :size="13" />
                  <span>Tab Baru</span>
                </button>
              </div>
            </div>

            <div class="preview-pane-viewer">
              <div v-if="isParsingUploadPdf" class="preview-loading-overlay">
                <Loader2 class="animate-spin text-brand" :size="28" />
                <p>Menganalisis dokumen & mengekstrak data...</p>
              </div>
              <iframe 
                v-if="uploadPdfPreviewUrl" 
                :src="uploadPdfPreviewUrl" 
                class="add-pdf-iframe"
                title="Pratinjau Berkas PDF Unggah Pengujian"
              ></iframe>
            </div>

            <div class="preview-pane-hint">
              <span>💡 <strong>Verifikasi Dokumen:</strong> Berkas baru belum disimpan ke database. Anda dapat meninjau isi PDF dan memeriksa data di samping sebelum menyimpan.</span>
            </div>
          </div>

          <!-- SISI KANAN: FORMULIR RINCIAN & KONFIRMASI -->
          <div class="modal-form-pane">
            <form @submit.prevent="handleConfirmUpload" class="modal-form">
              <div v-if="errorMessage" class="modal-alert alert-danger">
                <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
              </div>

              <!-- Info Status Banner -->
              <div v-if="selectedItemForUpload?.status === 'selesai'" class="upload-revision-alert">
                <div class="revision-alert-icon">⚠️</div>
                <div class="revision-alert-text">
                  <strong>Peringatan Revisi Dokumen:</strong>
                  Data pengujian ini sudah memiliki berkas versi <strong>v{{ selectedItemForUpload?.versi || 1 }}</strong>. 
                  Menyimpan berkas baru akan menaikkan dokumen menjadi <strong>v{{ (selectedItemForUpload?.versi || 1) + 1 }}</strong> dan mengirimkan notifikasi pembaruan ke pemohon. Berkas lama <strong>tidak akan tertimpa</strong> sebelum Anda menekan tombol Simpan di bawah.
                </div>
              </div>
              <div v-else class="upload-new-alert">
                <div class="new-alert-icon">ℹ️</div>
                <div class="new-alert-text">
                  <strong>Unggah Berkas Pengujian (v1):</strong>
                  Pengujian berstatus menunggu berkas. Unggah dokumen PDF hasil uji untuk menyelesaikan pengujian dan mengaktifkan akses unduh bagi pemohon.
                </div>
              </div>

              <!-- File Selector Section -->
              <div class="autofill-section">
                <div class="autofill-header-row">
                  <span class="section-title flex-icon-center" style="gap: 6px;">
                    <Upload :size="16" /> {{ selectedItemForUpload?.status === 'selesai' ? 'Pilih Berkas Pengganti / Revisi (PDF)' : 'Pilih Berkas Laporan Hasil (PDF)' }}
                  </span>
                  <span v-if="uploadExtractionMethod" class="extraction-badge">
                    ⚡ Terisi Otomatis ({{ uploadExtractionMethod === 'ai' ? 'Smart AI' : 'Regex PDF' }})
                  </span>
                </div>

                <div class="file-input-group">
                  <label for="upload-modal-file">Berkas Dokumen PDF (Maksimal 50MB)</label>
                  <div class="file-input-action-row">
                    <input 
                      type="file" 
                      id="upload-modal-file" 
                      accept=".pdf" 
                      @change="handleUploadFileChange"
                      :disabled="isParsingUploadPdf || isSavingUpload"
                    />
                    <button 
                      v-if="uploadFile && !showUploadPdfPreview" 
                      type="button" 
                      @click="showUploadPdfPreview = true" 
                      class="btn-preview-shortcut"
                    >
                      <Eye :size="13" /> Buka Preview
                    </button>
                    <button 
                      v-if="uploadFile" 
                      type="button" 
                      @click="removeUploadFile" 
                      class="btn-remove-selected-file"
                      title="Ganti berkas PDF"
                    >
                      <X :size="14" />
                    </button>
                  </div>
                  <span v-if="uploadFile" class="selected-file flex-icon-center" style="gap: 4px; display: inline-flex;">
                    <CheckCircle2 :size="14" style="color: #10b981;" /> {{ uploadFile.name }} ({{ (uploadFile.size / 1024).toFixed(1) }} KB)
                  </span>
                </div>
                <div v-if="isParsingUploadPdf" class="parsing-loader">
                  <span class="spinner"><Loader2 class="animate-spin" :size="16" /></span> Mengekstrak data dari dokumen PDF...
                </div>
              </div>

              <!-- Metadata fields with diff/autofill badges -->
              <div class="form-group">
                <div class="form-label-row">
                  <label>Nomor Pengujian</label>
                  <span v-if="uploadAutofilled.nomor_pengujian" class="badge-autofill">Ekstraksi PDF</span>
                  <span v-if="uploadDifferences.nomor_pengujian" class="badge-diff">Nilai Berubah</span>
                </div>
                <input 
                  type="text" 
                  v-model="uploadForm.nomor_pengujian" 
                  placeholder="Contoh: UJI-2026-001" 
                  :class="{ 'autofilled-highlight': uploadAutofilled.nomor_pengujian }"
                  required 
                />
              </div>

              <div class="form-group">
                <div class="form-label-row">
                  <label>Nama Pemohon</label>
                  <span v-if="uploadAutofilled.nama_pemohon" class="badge-autofill">Ekstraksi PDF</span>
                  <span v-if="uploadDifferences.nama_pemohon" class="badge-diff">Nilai Berubah</span>
                </div>
                <input 
                  type="text" 
                  v-model="uploadForm.nama_pemohon" 
                  placeholder="Masukkan nama pemohon" 
                  :class="{ 'autofilled-highlight': uploadAutofilled.nama_pemohon }"
                  required 
                />
              </div>

              <div class="form-group">
                <div class="form-label-row">
                  <label>Email Pemohon</label>
                  <span v-if="uploadAutofilled.email_pemohon" class="badge-autofill">Ekstraksi PDF</span>
                  <span v-if="uploadDifferences.email_pemohon" class="badge-diff">Nilai Berubah</span>
                </div>
                <input 
                  type="email" 
                  v-model="uploadForm.email_pemohon" 
                  placeholder="alamat@email.com" 
                  :class="{ 'autofilled-highlight': uploadAutofilled.email_pemohon }"
                  required 
                />
              </div>

              <div class="form-group">
                <div class="form-label-row">
                  <label>Jenis Pengujian &amp; Parameter Uji</label>
                  <span v-if="uploadAutofilled.jenis_pengujian" class="badge-autofill">Ekstraksi PDF</span>
                  <span v-if="uploadDifferences.jenis_pengujian" class="badge-diff">Nilai Berubah</span>
                </div>
                <JenisPengujianSelect
                  v-model="uploadForm.jenis_pengujian"
                  :highlighted="uploadAutofilled.jenis_pengujian"
                  required
                />
              </div>

              <div class="modal-footer">
                <button type="button" @click="closeUploadModal" class="btn-secondary" :disabled="isSavingUpload">Batal</button>
                <button type="submit" class="btn-primary" :disabled="!uploadFile || isParsingUploadPdf || isSavingUpload">
                  <span v-if="isSavingUpload" class="flex-icon-center"><Loader2 class="animate-spin" :size="16" /> Menyimpan & Mengunggah...</span>
                  <span v-else-if="selectedItemForUpload?.status === 'selesai'">Konfirmasi & Simpan Revisi (v{{ (selectedItemForUpload?.versi || 1) + 1 }})</span>
                  <span v-else>Konfirmasi & Simpan Berkas (v1)</span>
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>

    <!-- 2. Modal Edit Metadata -->
    <div v-if="showEditModal" class="modal-backdrop" @click.self="showEditModal = false">
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
            <small v-if="form.status === 'selesai'" class="email-edit-hint">
              💡 Pengujian ini telah selesai. Jika Anda memperbarui alamat email, sistem akan otomatis mengirimkan berkas pengujian ke alamat email baru ini.
            </small>
          </div>

          <div class="form-group">
            <label>Jenis Pengujian &amp; Parameter Uji</label>
            <JenisPengujianSelect
              v-model="form.jenis_pengujian"
              required
            />
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

    <!-- 4. Modal Pratinjau PDF -->
    <div v-if="showPreviewModal" class="modal-backdrop" @click.self="closePreview">
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
    <div v-if="showDeleteConfirm" class="modal-backdrop-confirm" @click.self="showDeleteConfirm = false">
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

.toast-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
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
  padding: 12px 40px 12px 46px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 14px;
  color: #1e293b;
  transition: all 0.2s ease;
  background: #f8fafc;
}

.btn-clear-search {
  position: absolute;
  right: 14px;
  background: #e2e8f0;
  border: none;
  color: #64748b;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 0;
  transition: all 0.2s ease;
}

.btn-clear-search:hover {
  background: #cbd5e1;
  color: #0f172a;
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
  font-size: 13.5px;
  font-weight: 600;
  color: #64748b;
}

.filter-group select, .filter-group input {
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
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
  font-size: 13px;
  color: #94a3b8;
}

.btn-primary {
  background: #1B4D3E;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 14.5px;
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
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-reset {
  height: 40px;
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
  font-size: 14.5px;
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 14.5px;
}

.data-table th {
  background: #f8fafc;
  padding: 16px;
  font-size: 14px;
  font-weight: 700;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 16px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  font-size: 14.5px;
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
  padding: 3px 9px;
  border-radius: 4px;
  font-size: 12.5px;
  font-weight: 600;
}

.badge-status {
  display: inline-block;
  padding: 5px 12px;
  border-radius: 30px;
  font-size: 13px;
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
  font-size: 13.5px;
  font-weight: 600;
  padding: 7px 14px;
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

.email-edit-hint {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #065f46;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  padding: 6px 10px;
  border-radius: 6px;
  line-height: 1.45;
}

.status-extra-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-top: 6px;
}


.spin-icon {
  animation: spin 1s linear infinite;
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
  font-size: 14px;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  gap: 8px;
}

.page-btn {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 7px 16px;
  border-radius: 6px;
  font-size: 14px;
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
  padding: 20px 16px;
  overflow-y: auto;
}

.modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 620px;
  max-height: calc(100vh - 40px);
  display: flex;
  flex-direction: column;
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
  flex-shrink: 0;
  padding: 18px 24px;
  background: rgba(27, 77, 62, 0.02); /* Subtle green tint */
  border-bottom: 1px solid #DCDACD;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 19px;
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
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.modal-desc {
  font-size: 14px;
  color: #5B6055;
  line-height: 1.5;
  margin: 0 0 8px 0;
}

.modal-alert {
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
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
  font-size: 14px;
  font-weight: 600;
  color: #22261F; /* High contrast ink text */
}

.form-group input, .form-group select {
  padding: 12px 15px;
  border: 1.5px solid #DCDACD; /* Higher contrast border */
  border-radius: 8px;
  font-size: 14.5px;
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
  align-items: center;
  gap: 12px;
  margin-top: 12px;
  padding-top: 16px;
  border-top: 1px solid #DCDACD;
  position: sticky;
  bottom: 0;
  background: #ffffff;
  z-index: 10;
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
  font-size: 13px;
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

/* Split Modal for Tambah Pengujian with PDF Preview */
.modal-card.modal-card-split {
  max-width: 1160px;
  width: 95vw;
  height: 88vh;
  max-height: 880px;
  display: flex;
  flex-direction: column;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.file-tag-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  background: rgba(27, 77, 62, 0.08);
  border: 1px solid rgba(27, 77, 62, 0.2);
  border-radius: 9999px;
  font-size: 11.5px;
  font-weight: 600;
  color: #1B4D3E;
  max-width: 240px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.modal-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-toggle-preview {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-toggle-preview:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.modal-split-container {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

.modal-split-container.with-preview {
  flex-direction: row;
}

/* SISI KIRI: PREVIEW PANE */
.modal-preview-pane {
  flex: 1.15;
  min-width: 0;
  background: #f8fafc;
  border-right: 1.5px solid #DCDACD;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.preview-pane-bar {
  padding: 10px 16px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.preview-bar-file {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  font-weight: 600;
  color: #1B4D3E;
  min-width: 0;
}

.preview-bar-icon {
  flex-shrink: 0;
}

.preview-bar-name {
  max-width: 240px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.preview-bar-size {
  font-size: 11px;
  color: #64748b;
  font-weight: normal;
  flex-shrink: 0;
}

.preview-bar-tools {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.btn-tool-tab {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-tool-tab:hover {
  background: #1B4D3E;
  color: #ffffff;
  border-color: #1B4D3E;
}

.preview-pane-viewer {
  flex: 1;
  min-height: 0;
  position: relative;
  background: #334155;
}

.preview-loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.92);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-size: 13px;
  color: #1B4D3E;
  font-weight: 600;
  z-index: 5;
}

.add-pdf-iframe {
  width: 100%;
  height: 100%;
  border: none;
  display: block;
}

.preview-pane-hint {
  padding: 8px 14px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  font-size: 11.5px;
  color: #475569;
  line-height: 1.4;
}

/* SISI KANAN: FORM PANE */
.modal-form-pane {
  flex: 0.95;
  min-width: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.modal-card-split .modal-form {
  padding: 20px 24px;
  gap: 14px;
}

/* Action Rows & Badges */
.file-input-action-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.file-input-action-row input[type="file"] {
  flex: 1;
}

.btn-preview-shortcut {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 8px 12px;
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.2s ease;
}

.btn-preview-shortcut:hover {
  background: #123328;
}

.btn-remove-selected-file {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #fca5a5;
  background: #fef2f2;
  color: #dc2626;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}

.btn-remove-selected-file:hover {
  background: #fee2e2;
}

.form-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.badge-autofill {
  font-size: 10px;
  font-weight: 700;
  color: #065f46;
  background: #d1fae5;
  border: 1px solid #a7f3d0;
  padding: 1px 7px;
  border-radius: 9999px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.autofill-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}

.extraction-badge {
  font-size: 11px;
  font-weight: 600;
  color: #047857;
  background: #ecfdf5;
  padding: 2px 8px;
  border-radius: 6px;
  border: 1px solid #a7f3d0;
}

@media (max-width: 900px) {
  .modal-card.modal-card-split {
    max-width: 96vw;
    height: 94vh;
    max-height: none;
  }

  .modal-split-container.with-preview {
    flex-direction: column;
    overflow-y: auto;
  }

  .modal-preview-pane {
    flex: none;
    height: 340px;
    border-right: none;
    border-bottom: 1.5px solid #DCDACD;
  }

  .modal-form-pane {
    flex: none;
  }
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

.upload-revision-alert {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 12px 14px;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-left: 4px solid #d97706;
  border-radius: 8px;
  font-size: 13px;
  color: #92400e;
  line-height: 1.45;
}

.upload-revision-alert strong {
  color: #78350f;
}

.upload-new-alert {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 12px 14px;
  background: #f0fdf4;
  border: 1px solid #dcfce7;
  border-left: 4px solid #16a34a;
  border-radius: 8px;
  font-size: 13px;
  color: #166534;
  line-height: 1.45;
}

.upload-new-alert strong {
  color: #14532d;
}

.badge-diff {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
  font-size: 10.5px;
  padding: 2px 7px;
  border-radius: 4px;
  font-weight: 600;
  margin-left: 6px;
  letter-spacing: 0.2px;
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










