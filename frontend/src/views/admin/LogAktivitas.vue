<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../../services/api'
import { AlertTriangle, Download, Loader2, User, FileDown, RefreshCw, Search, ChevronLeft, ChevronRight, FileText } from '@lucide/vue'

// Tab state: 'aktivitas' atau 'unduhan'
const activeTab = ref('aktivitas')

// Data State
const logsAktivitas = ref([])
const logsUnduhan = ref([])
const petugasList = ref([])

// Loading / Error
const isLoading = ref(false)
const errorMessage = ref('')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)

// Filters
const filterAktivitas = ref({
  petugas_id: '',
  aksi: '',
  tanggal_mulai: '',
  tanggal_akhir: ''
})

const filterUnduhan = ref({
  tipe_file: '',
  ip_address: '',
  tanggal_mulai: '',
  tanggal_akhir: ''
})

// Load Petugas list for dropdown filter
const fetchPetugasList = async () => {
  try {
    const response = await api.get('/api/admin/petugas')
    petugasList.value = response.data
  } catch (error) {
    console.error('Failed to load petugas list:', error)
  }
}

// Fetch Logs
const fetchLogs = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    if (activeTab.value === 'aktivitas') {
      const response = await api.get('/api/admin/logs/aktivitas', {
        params: {
          page,
          petugas_id: filterAktivitas.value.petugas_id || undefined,
          aksi: filterAktivitas.value.aksi || undefined,
          tanggal_mulai: filterAktivitas.value.tanggal_mulai || undefined,
          tanggal_akhir: filterAktivitas.value.tanggal_akhir || undefined,
        }
      })
      logsAktivitas.value = response.data.data
      currentPage.value = response.data.current_page
      lastPage.value = response.data.last_page
    } else {
      const response = await api.get('/api/admin/logs/unduhan', {
        params: {
          page,
          tipe_file: filterUnduhan.value.tipe_file || undefined,
          ip_address: filterUnduhan.value.ip_address || undefined,
          tanggal_mulai: filterUnduhan.value.tanggal_mulai || undefined,
          tanggal_akhir: filterUnduhan.value.tanggal_akhir || undefined,
        }
      })
      logsUnduhan.value = response.data.data
      currentPage.value = response.data.current_page
      lastPage.value = response.data.last_page
    }
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Gagal memuat data log audit.'
  } finally {
    isLoading.value = false
  }
}

// Reset filters
const resetFilters = () => {
  if (activeTab.value === 'aktivitas') {
    filterAktivitas.value = { petugas_id: '', aksi: '', tanggal_mulai: '', tanggal_akhir: '' }
  } else {
    filterUnduhan.value = { tipe_file: '', ip_address: '', tanggal_mulai: '', tanggal_akhir: '' }
  }
  fetchLogs(1)
}

// Export Excel handler
const handleExport = async () => {
  try {
    const endpoint = activeTab.value === 'aktivitas' 
      ? '/api/admin/logs/aktivitas/ekspor' 
      : '/api/admin/logs/unduhan/ekspor'
    
    const fileName = activeTab.value === 'aktivitas' 
      ? 'audit_log_aktivitas.xlsx' 
      : 'audit_log_unduhan.xlsx'

    const response = await api.get(endpoint, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', fileName)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Failed to export log:', error)
    alert('Gagal mengekspor data log.')
  }
}

// Watch tab changes to refresh data
watch(activeTab, () => {
  fetchLogs(1)
})

onMounted(() => {
  fetchPetugasList()
  fetchLogs(1)
})
</script>

<template>
  <div class="audit-log-view">
    <!-- Header Section -->
    <div class="header-section">
      <div class="header-info">
        <h2>Pelacakan Audit Log &amp; Riwayat</h2>
        <p class="text-muted">Pantau rekap aktivitas sensitif petugas laboratorium serta akses unduhan laporan oleh publik.</p>
      </div>
      <button @click="handleExport" class="btn-export" :disabled="isLoading">
        <Download :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> Ekspor Log (Excel)
      </button>
    </div>

    <!-- Error Alert -->
    <div v-if="errorMessage" class="alert alert-danger">
      <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
    </div>

    <!-- Navigation Tabs -->
    <div class="tabs-nav">
      <button 
        @click="activeTab = 'aktivitas'" 
        class="tab-btn flex-icon-center" 
        :class="{ active: activeTab === 'aktivitas' }"
      >
        <User :size="16" /> Aktivitas Petugas
      </button>
      <button 
        @click="activeTab = 'unduhan'" 
        class="tab-btn flex-icon-center" 
        :class="{ active: activeTab === 'unduhan' }"
      >
        <FileDown :size="16" /> Unduhan Publik
      </button>
    </div>

    <!-- Main Card Content -->
    <div class="card log-card">
      <!-- 1. Filters block -->
      <div class="filter-wrapper">
        <!-- Filter untuk Log Aktivitas -->
        <div v-if="activeTab === 'aktivitas'" class="filters-grid">
          <div class="filter-group">
            <label>Petugas</label>
            <select v-model="filterAktivitas.petugas_id">
              <option value="">-- Semua Petugas --</option>
              <option v-for="p in petugasList" :key="p.id" :value="p.id">
                {{ p.nama }} ({{ p.username }})
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label>Jenis Tindakan</label>
            <select v-model="filterAktivitas.aksi">
              <option value="">-- Semua Tindakan --</option>
              <option value="Tambah Petugas">Tambah Petugas</option>
              <option value="Update Petugas">Update Petugas</option>
              <option value="Hapus Petugas">Hapus Petugas</option>
              <option value="Reset Password Petugas">Reset Password Petugas</option>
              <option value="Tambah Pengujian">Tambah Pengujian</option>
              <option value="Update Pengujian">Update Pengujian</option>
              <option value="Upload Hasil Uji">Upload Hasil Uji</option>
              <option value="Kirim Email Hasil Uji">Kirim Email Hasil Uji</option>
              <option value="Hapus Pengujian">Hapus Pengujian</option>
              <option value="Login">Login</option>
              <option value="Ganti Password">Ganti Password</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Mulai Tanggal</label>
            <input 
              type="date" 
              v-model="filterAktivitas.tanggal_mulai" 
              @click="$event.target.showPicker?.()" 
              class="input-date-picker"
            />
          </div>

          <div class="filter-group">
            <label>Hingga Tanggal</label>
            <input 
              type="date" 
              v-model="filterAktivitas.tanggal_akhir" 
              @click="$event.target.showPicker?.()" 
              class="input-date-picker"
            />
          </div>
        </div>

        <!-- Filter untuk Log Unduhan -->
        <div v-else class="filters-grid">
          <div class="filter-group">
            <label>Tipe Berkas</label>
            <select v-model="filterUnduhan.tipe_file">
              <option value="">-- Semua Tipe Berkas --</option>
              <option value="laporan">Laporan Hasil Uji</option>
            </select>
          </div>

          <div class="filter-group">
            <label>IP Address</label>
            <input type="text" placeholder="Cari IP..." v-model="filterUnduhan.ip_address" />
          </div>

          <div class="filter-group">
            <label>Mulai Tanggal</label>
            <input 
              type="date" 
              v-model="filterUnduhan.tanggal_mulai" 
              @click="$event.target.showPicker?.()" 
              class="input-date-picker"
            />
          </div>

          <div class="filter-group">
            <label>Hingga Tanggal</label>
            <input 
              type="date" 
              v-model="filterUnduhan.tanggal_akhir" 
              @click="$event.target.showPicker?.()" 
              class="input-date-picker"
            />
          </div>
        </div>

        <!-- Filter Action Buttons -->
        <div class="filter-actions">
          <button @click="fetchLogs(1)" class="btn-filter" :disabled="isLoading">
            <Search :size="14" style="margin-right: 6px; display: inline-block; vertical-align: middle;" /> Terapkan Filter
          </button>
          <button @click="resetFilters" class="btn-reset" :disabled="isLoading">
            <RefreshCw :size="14" style="margin-right: 6px; display: inline-block; vertical-align: middle;" /> Reset
          </button>
        </div>
      </div>

      <!-- Spinner Loader -->
      <div v-if="isLoading" class="loading-state">
        <div class="spinner"><Loader2 :size="32" /></div>
        <p>Memuat audit log...</p>
      </div>

      <!-- Tab Content Area -->
      <div v-else>
        <!-- A. Tabel Log Aktivitas -->
        <div v-if="activeTab === 'aktivitas'" class="table-container">
          <table class="log-table">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Petugas</th>
                <th>Tindakan</th>
                <th>Detail Kegiatan</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="logsAktivitas.length === 0">
                <td colspan="5" class="empty-row">Tidak ada log aktivitas petugas.</td>
              </tr>
              <tr v-for="log in logsAktivitas" :key="log.id">
                <td class="text-nowrap">
                  {{ new Date(log.created_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                </td>
                <td class="text-bold">{{ log.petugas?.nama || 'Sistem' }}</td>
                <td>
                  <span class="badge-action" :class="log.aksi.toLowerCase().replace(/\s+/g, '-')">
                    {{ log.aksi }}
                  </span>
                </td>
                <td class="detail-cell">{{ log.detail }}</td>
                <td class="text-nowrap font-mono text-muted">{{ log.ip_address || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- B. Tabel Log Unduhan -->
        <div v-else class="table-container">
          <table class="log-table">
            <thead>
              <tr>
                <th>Waktu Unduh</th>
                <th>Nomor Pengujian</th>
                <th>Jenis Berkas</th>
                <th>Akses Oleh</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="logsUnduhan.length === 0">
                <td colspan="5" class="empty-row">Belum ada riwayat unduhan berkas.</td>
              </tr>
              <tr v-for="log in logsUnduhan" :key="log.id">
                <td class="text-nowrap">
                  {{ new Date(log.created_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                </td>
                <td class="text-bold">{{ log.pengujian?.nomor_pengujian }}</td>
                <td>
                  <span class="badge-file laporan flex-icon-center" style="gap: 4px; display: inline-flex;">
                    <FileText :size="12" /> Laporan Hasil Uji
                  </span>
                </td>
                <td>
                  <span class="badge-akses" :class="log.akses_oleh">
                    {{ log.akses_oleh }}
                  </span>
                </td>
                <td class="font-mono text-muted">{{ log.ip_address }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="pagination-footer">
          <span class="pagination-info">Halaman {{ currentPage }} dari {{ lastPage }}</span>
          <div class="pagination-buttons">
            <button 
              @click="fetchLogs(currentPage - 1)" 
              :disabled="currentPage === 1 || isLoading" 
              class="page-btn"
            >
              <ChevronLeft :size="16" /> Prev
            </button>
            <button 
              @click="fetchLogs(currentPage + 1)" 
              :disabled="currentPage === lastPage || isLoading" 
              class="page-btn"
            >
              Next <ChevronRight :size="16" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.audit-log-view {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.header-info h2 {
  margin: 0 0 6px 0;
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
}

.text-muted {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.btn-export {
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
  white-space: nowrap;
}

.btn-export:hover:not(:disabled) {
  background: #13382D;
  transform: translateY(-1px);
}

.btn-export:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
  box-shadow: none;
}

.alert {
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 14px;
}

.alert-danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

/* Tabs styles */
.tabs-nav {
  display: flex;
  gap: 12px;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 2px;
}

.tab-btn {
  background: none;
  border: none;
  padding: 10px 16px;
  font-size: 14px;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  color: #0f172a;
}

.tab-btn.active {
  color: #1B4D3E;
  border-bottom-color: #1B4D3E;
}

/* Card layout */
.card {
  background: #ffffff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01), 0 10px 15px -3px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9;
}

/* Filters */
.filter-wrapper {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group label {
  font-size: 11px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-group input, .filter-group select {
  padding: 8px 12px;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  background: #ffffff;
}

.filter-group input:focus, .filter-group select:focus {
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
}

.input-date-picker {
  cursor: pointer;
}

.input-date-picker::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0.85;
  transition: opacity 0.2s ease;
}

.input-date-picker::-webkit-calendar-picker-indicator:hover {
  opacity: 1;
}

.filter-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.btn-filter {
  background: #1B4D3E;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn-filter:hover {
  background: #13382D;
}

.btn-reset {
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
  color: #475569;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-reset:hover {
  background: #e2e8f0;
}

/* Tables */
.table-container {
  overflow-x: auto;
}

.log-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.log-table th {
  background: #f8fafc;
  padding: 12px 16px;
  font-weight: 600;
  color: #475569;
  border-bottom: 1.5px solid #e2e8f0;
}

.log-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.log-table tbody tr:hover {
  background: #f8fafc;
}

.empty-row {
  text-align: center;
  color: #94a3b8;
  padding: 40px !important;
  font-style: italic;
}

.text-nowrap {
  white-space: nowrap;
}

.text-bold {
  font-weight: 600;
  color: #0f172a;
}

.font-mono {
  font-family: monospace;
}

.detail-cell {
  max-width: 320px;
  word-wrap: break-word;
  color: #475569;
}

/* Badges */
.badge-action {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  display: inline-block;
}

/* Warna tag aksi log */
.badge-action.tambah-petugas { background: #ecfdf5; color: #065f46; }
.badge-action.update-petugas { background: rgba(27, 77, 62, 0.08); color: #1B4D3E; }
.badge-action.hapus-petugas { background: #fef2f2; color: #991b1b; }
.badge-action.reset-password-petugas { background: #fffbeb; color: #92400e; }
.badge-action.tambah-pengujian { background: #fdf2f8; color: #9d174d; }
.badge-action.upload-hasil-uji { background: #f5f3ff; color: #5b21b6; }
.badge-action.login { background: #f1f5f9; color: #334155; }
.badge-action.ganti-password { background: #e0f2fe; color: #0369a1; }

.badge-file {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
}

.badge-file.laporan { background: rgba(27, 77, 62, 0.08); color: #1B4D3E; }
.badge-file.sertifikat { background: #f5f3ff; color: #5b21b6; }

.badge-akses {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
}
.badge-akses.publik { background: #ecfdf5; color: #065f46; }
.badge-akses.petugas { background: #e0f2fe; color: #0369a1; }

/* Pagination */
.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

.pagination-info {
  font-size: 12px;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  gap: 6px;
}

.page-btn {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
}

.page-btn:hover:not(:disabled) {
  background: rgba(27, 77, 62, 0.08);
  border-color: #1B4D3E;
  color: #1B4D3E;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: #f8fafc;
  color: #94a3b8;
  border-color: #e2e8f0;
}

/* Spinner Loader */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  color: #64748b;
  gap: 12px;
}

.spinner {
  font-size: 28px;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.flex-icon-center {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  .btn-export {
    width: 100%;
    justify-content: center;
  }
  .tabs-nav {
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
  }
  .filters-grid {
    grid-template-columns: 1fr;
  }
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
}
</style>
