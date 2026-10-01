<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../../services/api'
import { AlertTriangle, Download, Loader2, BarChart3, Star, Users, RotateCcw, ChevronLeft, ChevronRight, FileQuestion } from '@lucide/vue'

// Data State
const stats = ref({
  total_responden: 0,
  rata_rata_unsur: {},
  rata_rata_unsur_terbobot: {},
  ikm: 0.0,
  mutu: '-',
  kinerja: '-'
})
const respondenList = ref([])
const totalResponden = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const isLoading = ref(true)
const errorMessage = ref('')

// State Filter
const filterTriwulan = ref('')
const filterMonth = ref('')
const filterYear = ref(new Date().getFullYear())

const triwulanList = [
  { value: 1, label: 'Triwulan I (Januari - Maret)' },
  { value: 2, label: 'Triwulan II (April - Juni)' },
  { value: 3, label: 'Triwulan III (Juli - September)' },
  { value: 4, label: 'Triwulan IV (Oktober - Desember)' }
]

const onTriwulanChange = () => {
  if (filterTriwulan.value) {
    filterMonth.value = ''
  }
  fetchSkmData(1)
}

const onMonthChange = () => {
  if (filterMonth.value) {
    filterTriwulan.value = ''
  }
  fetchSkmData(1)
}

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

// Dynamic year options: combines rolling window (current year - 5 to + 1) with any years present in respondenList
const years = computed(() => {
  const currentYear = new Date().getFullYear()
  const yearSet = new Set()
  
  for (let y = currentYear + 1; y >= currentYear - 5; y--) {
    yearSet.add(y)
  }
  
  if (respondenList.value && respondenList.value.length > 0) {
    respondenList.value.forEach(r => {
      if (r.created_at) {
        const year = new Date(r.created_at).getFullYear()
        if (!isNaN(year)) yearSet.add(year)
      }
    })
  }
  
  return Array.from(yearSet).sort((a, b) => b - a)
})

const questionsLabel = {
  u1: 'U1 - Informasi Pelayanan',
  u2: 'U2 - Kesesuaian Persyaratan',
  u3: 'U3 - Standar Prosedur',
  u4: 'U4 - Kemudahan Prosedur',
  u5: 'U5 - Prosedur Tanpa Kecurangan',
  u6: 'U6 - Jangka Waktu Layanan',
  u7: 'U7 - Kesesuaian Biaya',
  u8: 'U8 - Bebas Pungli',
  u9: 'U9 - Bebas Percaloan',
  u10: 'U10 - Kesesuaian Produk',
  u11: 'U11 - Kecepatan Respon',
  u12: 'U12 - Keramahan Petugas',
  u13: 'U13 - Keadilan Layanan',
  u14: 'U14 - Bebas Imbalan Ekstra',
  u15: 'U15 - Kemudahan Pengaduan',
  u16: 'U16 - Kenyamanan Sarpras'
}

// Fetch Stats & Responden Data
const fetchSkmData = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get('/api/admin/skm/ikm', {
      params: { 
        page,
        triwulan: filterTriwulan.value || undefined,
        bulan: filterMonth.value || undefined,
        tahun: filterYear.value || undefined
      }
    })
    stats.value = response.data.stats
    respondenList.value = response.data.responden.data
    totalResponden.value = response.data.responden.total
    currentPage.value = response.data.responden.current_page
    lastPage.value = response.data.responden.last_page
  } catch (error) {
    errorMessage.value = 'Gagal memuat data laporan SKM.'
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

// Reset filters
const resetFilters = () => {
  filterTriwulan.value = ''
  filterMonth.value = ''
  filterYear.value = new Date().getFullYear()
  fetchSkmData(1)
}

// Export to Excel
const handleExport = async () => {
  try {
    const response = await api.get('/api/admin/skm/ekspor', { 
      params: {
        triwulan: filterTriwulan.value || undefined,
        bulan: filterMonth.value || undefined,
        tahun: filterYear.value || undefined
      },
      responseType: 'blob' 
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    let labelPeriode = 'semua'
    if (filterTriwulan.value) {
      labelPeriode = `triwulan_${filterTriwulan.value}`
    } else if (filterMonth.value) {
      labelPeriode = `bulan_${filterMonth.value}`
    }
    link.setAttribute('download', `rekapitulasi_skm_${labelPeriode}_${filterYear.value || 'semua'}.xlsx`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export:', error)
    errorMessage.value = 'Gagal mengekspor laporan. Silakan coba beberapa saat lagi.'
    setTimeout(() => { errorMessage.value = '' }, 4000)
  }
}

onMounted(() => {
  fetchSkmData(1)
})
</script>

<template>
  <div class="laporan-skm-view">
    <div class="header-section">
      <div class="header-info">
        <h2>Laporan &amp; Indeks Kepuasan Masyarakat</h2>
        <p class="text-muted">Ringkasan nilai IKM dan rekap data survei kepuasan masyarakat.</p>
      </div>
      <button @click="handleExport" class="btn-primary btn-export" :disabled="isLoading || totalResponden === 0">
        <Download :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> Ekspor Rekap (Excel)
      </button>
    </div>

    <!-- Filter Section (F-23) -->
    <div class="card filter-card">
      <div class="filter-row">
        <div class="filter-group">
          <label>Filter Triwulan</label>
          <select v-model="filterTriwulan" @change="onTriwulanChange" :disabled="isLoading">
            <option value="">Semua Triwulan</option>
            <option v-for="tw in triwulanList" :key="tw.value" :value="tw.value">{{ tw.label }}</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Filter Bulan</label>
          <select v-model="filterMonth" @change="onMonthChange" :disabled="isLoading">
            <option value="">Semua Bulan</option>
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Filter Tahun</label>
          <select v-model="filterYear" @change="fetchSkmData(1)" :disabled="isLoading">
            <option value="">Semua Tahun</option>
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>

        <div class="filter-actions-inline">
          <button @click="resetFilters" class="btn-secondary" :disabled="isLoading">
            <RotateCcw :size="14" style="margin-right: 4px; display: inline-block; vertical-align: middle;" /> Reset Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-if="errorMessage" class="alert alert-danger">
      <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
    </div>

    <!-- Skeleton Loading State -->
    <div v-if="isLoading" class="laporan-grid">
      <div class="stats-overview">
        <div v-for="n in 3" :key="n" class="card stat-card" style="padding: 20px;">
          <div class="skeleton-bar" style="width: 50%; height: 14px; margin-bottom: 16px;"></div>
          <div class="skeleton-bar" style="width: 70%; height: 28px; margin-bottom: 12px;"></div>
          <div class="skeleton-bar" style="width: 80%; height: 12px;"></div>
        </div>
      </div>
      <div class="card" style="padding: 24px;">
        <div class="skeleton-bar" style="width: 30%; height: 20px; margin-bottom: 20px;"></div>
        <div v-for="n in 5" :key="n" class="skeleton-bar" style="width: 100%; height: 18px; margin-bottom: 12px;"></div>
      </div>
    </div>


    <div v-else class="laporan-grid">
      <!-- Top Stats Section -->
      <div class="stats-overview">
        <!-- Card 1: Nilai IKM -->
        <div class="card stat-card ikm-card">
          <div class="stat-header">
            <span class="stat-title">Nilai IKM Terkonversi</span>
            <span class="stat-icon"><BarChart3 :size="18" /></span>
          </div>
          <div class="stat-value">{{ stats.ikm }}</div>
          <div class="stat-meta">
            Mutu Pelayanan: <strong :class="'mutu-' + stats.mutu">Mutu {{ stats.mutu }}</strong>
          </div>
        </div>

        <!-- Card 2: Kinerja Unit -->
        <div class="card stat-card kinerja-card">
          <div class="stat-header">
            <span class="stat-title">Kinerja Pelayanan</span>
            <span class="stat-icon"><Star :size="18" /></span>
          </div>
          <div class="stat-value text-large">{{ stats.kinerja }}</div>
          <div class="stat-meta">
            Berdasarkan skor rata-rata tertimbang
          </div>
        </div>

        <!-- Card 3: Total Responden -->
        <div class="card stat-card responden-card">
          <div class="stat-header">
            <span class="stat-title">Total Responden</span>
            <span class="stat-icon"><Users :size="18" /></span>
          </div>
          <div class="stat-value">{{ stats.total_responden }}</div>
          <div class="stat-meta">
            Survei masuk yang valid
          </div>
        </div>
      </div>

      <!-- Main Section: Chart per Unsur (Left) & Responden List (Right) -->
      <div class="main-sections">
        <!-- 1. Chart per Unsur Card -->
        <div class="card chart-card">
          <h3>Rata-rata Nilai per Unsur</h3>
          <p class="section-desc">Nilai rata-rata unsur menggunakan skala 1.00 s/d 4.00</p>
          
          <div class="chart-container">
            <div v-for="(label, key) in questionsLabel" :key="key" class="chart-row">
              <div class="chart-label-group">
                <span class="chart-label-text">{{ label }}</span>
                <span class="chart-label-score">{{ stats.rata_rata_unsur[key] || '0.00' }}</span>
              </div>
              <div class="bar-wrapper">
                <!-- Hitung persentase lebar dari nilai maks 4 -->
                <div 
                  class="bar-fill" 
                  :style="{ width: ((stats.rata_rata_unsur[key] || 0) / 4 * 100) + '%' }"
                  :class="'bar-color-' + Math.round(stats.rata_rata_unsur[key] || 0)"
                ></div>
              </div>
              <div class="chart-terbobot-text">
                Nilai Terbobot: {{ stats.rata_rata_unsur_terbobot[key] || '0.00' }}
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Responden Card -->
        <div class="card list-card">
          <h3>Daftar Penilaian Responden</h3>
          <p class="section-desc">Riwayat hasil survei kepuasan pelanggan.</p>

          <div v-if="respondenList.length === 0" class="empty-state">
            <div class="empty-icon"><FileQuestion :size="36" /></div>
            <p>Belum ada data responden.</p>
          </div>

          <div v-else class="responden-table-wrapper">
            <table class="responden-table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Nomor Uji</th>
                  <th>Nilai U1-U16</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="resp in respondenList" :key="resp.id" class="table-row">
                  <td class="text-nowrap">
                    {{ new Date(resp.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) }}
                  </td>
                  <td class="text-bold">{{ resp.nomor_pengujian }}</td>
                  <td>
                    <!-- Tampilkan skor terkompresi -->
                    <span class="score-summary" title="Skor Unsur U1-U16">
                      {{ [resp.skor_1, resp.skor_2, resp.skor_3, resp.skor_4, resp.skor_5, resp.skor_6, resp.skor_7, resp.skor_8, resp.skor_9, resp.skor_10, resp.skor_11, resp.skor_12, resp.skor_13, resp.skor_14, resp.skor_15, resp.skor_16].join(',') }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination Footer -->
            <div v-if="lastPage > 1" class="pagination-footer">
              <span class="pagination-info">Halaman {{ currentPage }} dari {{ lastPage }}</span>
              <div class="pagination-buttons">
                <button 
                  @click="fetchSkmData(currentPage - 1)" 
                  :disabled="currentPage === 1" 
                  class="page-btn flex-icon-center"
                >
                  <ChevronLeft :size="16" /> Prev
                </button>
                <button 
                  @click="fetchSkmData(currentPage + 1)" 
                  :disabled="currentPage === lastPage" 
                  class="page-btn flex-icon-center"
                >
                  Next <ChevronRight :size="16" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.laporan-skm-view {
  display: flex;
  flex-direction: column;
  gap: 24px;
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
  background: #cbd5e1;
  color: #94a3b8;
  box-shadow: none;
  cursor: not-allowed;
}

.btn-export {
  height: 44px;
  white-space: nowrap;
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

.laporan-grid {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.card {
  background: #ffffff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01), 0 10px 15px -3px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9;
}

.stat-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-title {
  font-size: 13px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-icon {
  font-size: 20px;
}

.stat-value {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
}

.stat-value.text-large {
  font-size: 20px;
  padding: 8px 0;
  color: #1B4D3E;
}

.stat-meta {
  font-size: 13px;
  color: #64748b;
}

.mutu-A { color: #10b981; font-weight: 700; }
.mutu-B { color: #1B4D3E; font-weight: 700; }
.mutu-C { color: #f59e0b; font-weight: 700; }
.mutu-D { color: #ef4444; font-weight: 700; }

/* Main layout sections */
.main-sections {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 24px;
  align-items: start;
}

@media (max-width: 950px) {
  .main-sections {
    grid-template-columns: 1fr;
  }
}

.section-desc {
  margin: -8px 0 20px 0;
  font-size: 14px;
  color: #64748b;
}

.chart-card h3, .list-card h3 {
  margin: 0 0 8px 0;
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
}

.chart-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.chart-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.chart-label-group {
  display: flex;
  justify-content: space-between;
  font-size: 13.5px;
  font-weight: 600;
  color: #475569;
}

.chart-label-score {
  font-weight: 700;
  color: #0f172a;
}

.bar-wrapper {
  background: #f1f5f9;
  height: 10px;
  border-radius: 20px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  border-radius: 20px;
  transition: width 0.8s ease-out;
}

/* Color bar dynamically based on rating score */
.bar-color-4 { background: #10b981; } /* Sangat Baik */
.bar-color-3 { background: #1B4D3E; } /* Baik */
.bar-color-2 { background: #f59e0b; } /* Kurang Baik */
.bar-color-1 { background: #ef4444; } /* Buruk */
.bar-color-0 { background: #e2e8f0; } /* Kosong */

.chart-terbobot-text {
  font-size: 11px;
  color: #94a3b8;
  text-align: right;
  margin-top: 1px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #94a3b8;
}

.empty-icon {
  font-size: 32px;
  margin-bottom: 8px;
}

.responden-table-wrapper {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.responden-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 14.5px;
}

.responden-table th {
  background: #f8fafc;
  padding: 14px 16px;
  font-size: 14px;
  font-weight: 700;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.responden-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  font-size: 14.5px;
  vertical-align: top;
}

.table-row:hover {
  background: #f8fafc;
}

.text-bold {
  font-weight: 600;
  color: #0f172a;
}

.text-nowrap {
  white-space: nowrap;
}

.score-summary {
  background: #f1f5f9;
  color: #475569;
  font-size: 12.5px;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 4px;
  letter-spacing: 0.5px;
}

.saran-cell {
  max-width: 250px;
  word-wrap: break-word;
}

.saran-text {
  font-size: 13.5px;
  color: #334155;
  line-height: 1.4;
}

.saran-text.no-saran {
  color: #94a3b8;
  font-style: italic;
}

.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
}

.pagination-info {
  font-size: 14px;
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

/* Custom styles for month/year filter (F-23) */
.filter-card {
  padding: 16px 24px;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01), 0 10px 15px -3px rgba(0, 0, 0, 0.02);
}

.filter-row {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 150px;
}

.filter-group label {
  font-size: 13px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-group select {
  padding: 10px 14px;
  border-radius: 10px;
  border: 1.5px solid #cbd5e1;
  background: #f8fafc;
  outline: none;
  font-size: 14px;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-group select:focus {
  border-color: #1B4D3E;
  background: #ffffff;
}

.filter-actions-inline {
  display: flex;
  align-items: center;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 11px 18px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.animate-spin {
  animation: spin 1s linear infinite;
  display: inline-block;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  .btn-primary {
    width: 100%;
    justify-content: center;
  }
  .main-sections {
    grid-template-columns: 1fr;
  }
  .summary-grid {
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
}
</style>
