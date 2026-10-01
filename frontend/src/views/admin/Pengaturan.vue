<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'
import { 
  User, 
  Lock, 
  ShieldCheck, 
  CheckCircle2, 
  AlertTriangle, 
  Save, 
  KeyRound,
  Archive,
  Database,
  CalendarClock,
  HardDrive,
  RefreshCw,
  Clock
} from '@lucide/vue'

const authStore = useAuthStore()

// State Active Tab ('profil' | 'keamanan' | 'arsip')
const activeTab = ref('profil')

// State Arsip & Backup
const archiveStats = ref(null)
const isLoadingArchiveStats = ref(false)
const isActionRunning = ref(false)

// Form State Profil
const formProfil = reactive({
  nama: authStore.user?.nama || '',
  username: authStore.user?.username || '',
  email: authStore.user?.email || ''
})

// Form State Password
const formPassword = reactive({
  password_lama: '',
  password_baru: '',
  konfirmasi_password: ''
})

// Loading & Message state
const isLoadingProfil = ref(false)
const isLoadingPassword = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const clearMessages = () => {
  successMessage.value = ''
  errorMessage.value = ''
}

// Handler Update Profil
const handleUpdateProfil = async () => {
  clearMessages()
  isLoadingProfil.value = true

  try {
    const result = await authStore.updateProfile({
      nama: formProfil.nama,
      username: formProfil.username,
      email: formProfil.email
    })

    if (result.success) {
      successMessage.value = result.message
    } else {
      errorMessage.value = result.message
    }
  } catch (err) {
    errorMessage.value = 'Terjadi kesalahan saat memperbarui profil.'
  } finally {
    isLoadingProfil.value = false
  }
}

// Handler Update Password
const handleUpdatePassword = async () => {
  clearMessages()

  if (formPassword.password_baru !== formPassword.konfirmasi_password) {
    errorMessage.value = 'Konfirmasi password baru tidak cocok.'
    return
  }

  if (formPassword.password_baru.length < 8) {
    errorMessage.value = 'Password baru minimal berjumlah 8 karakter.'
    return
  }

  isLoadingPassword.value = true

  try {
    const response = await api.post('/api/admin/ganti-password', {
      password_lama: formPassword.password_lama,
      password_baru: formPassword.password_baru,
      password_baru_confirmation: formPassword.konfirmasi_password
    })

    successMessage.value = response.data.message
    formPassword.password_lama = ''
    formPassword.password_baru = ''
    formPassword.konfirmasi_password = ''
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal mengubah password.'
  } finally {
    isLoadingPassword.value = false
  }
}

// Handlers Arsip & Backup Otomatis
const fetchArchiveStats = async () => {
  isLoadingArchiveStats.value = true
  try {
    const res = await api.get('/api/admin/maintenance/archive-stats?years=3')
    archiveStats.value = res.data
  } catch (err) {
    console.error('Gagal memuat statistik arsip', err)
  } finally {
    isLoadingArchiveStats.value = false
  }
}

// State Modal Konfirmasi Khusus (Pengganti confirm bawaan browser)
const confirmModal = reactive({
  show: false,
  title: '',
  message: '',
  confirmText: '',
  confirmType: 'primary',
  action: null
})

const openConfirm = ({ title, message, confirmText, confirmType = 'primary', onConfirm }) => {
  confirmModal.title = title
  confirmModal.message = message
  confirmModal.confirmText = confirmText
  confirmModal.confirmType = confirmType
  confirmModal.action = onConfirm
  confirmModal.show = true
}

const closeConfirm = () => {
  confirmModal.show = false
  confirmModal.action = null
}

const executeConfirm = async () => {
  const actionToRun = confirmModal.action
  closeConfirm()
  if (actionToRun) {
    await actionToRun()
  }
}

const handleManualBackup = () => {
  openConfirm({
    title: 'Konfirmasi Pencadangan Data',
    message: 'Pencadangan sebenarnya sudah berjalan otomatis tiap bulan. Apakah Anda yakin ingin menjalankan pencadangan basis data & berkas LHU sekarang secara manual?',
    confirmText: 'Ya, Cadangkan Sekarang',
    confirmType: 'primary',
    onConfirm: async () => {
      clearMessages()
      isActionRunning.value = true
      try {
        const res = await api.post('/api/admin/maintenance/backup-now')
        successMessage.value = res.data.message
        await fetchArchiveStats()
      } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Gagal menjalankan pencadangan.'
      } finally {
        isActionRunning.value = false
      }
    }
  })
}

const handleManualArchive = () => {
  openConfirm({
    title: 'Konfirmasi Pengarsipan Data Lama',
    message: 'Pengarsipan berjalan otomatis tiap bulan. Apakah Anda yakin ingin mengarsipkan pengujian yang telah berusia > 3 tahun sekarang? Berkas fisik PDF aktif akan dibersihkan dari server live guna membebaskan ruang disk.',
    confirmText: 'Ya, Arsipkan Data Sekarang',
    confirmType: 'warning',
    onConfirm: async () => {
      clearMessages()
      isActionRunning.value = true
      try {
        const res = await api.post('/api/admin/maintenance/archive-now?years=3')
        successMessage.value = res.data.message
        await fetchArchiveStats()
      } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Gagal menjalankan pengarsipan.'
      } finally {
        isActionRunning.value = false
      }
    }
  })
}

const switchTab = (tab) => {
  activeTab.value = tab
  clearMessages()
  if (tab === 'arsip' && !archiveStats.value) {
    fetchArchiveStats()
  }
}

onMounted(() => {
  if (authStore.user) {
    formProfil.nama = authStore.user.nama || ''
    formProfil.username = authStore.user.username || ''
    formProfil.email = authStore.user.email || ''
  }
})
</script>

<template>
  <div class="pengaturan-container">
    <!-- Header Page -->
    <div class="page-header">
      <div>
        <h1 class="title">Pengaturan Sistem &amp; Profil</h1>
        <p class="subtitle">Kelola profil administrator, keamanan akun, dan pemeliharaan arsip otomatis</p>
      </div>
    </div>

    <!-- Toast Alert Messages -->
    <div v-if="successMessage" class="alert alert-success flex-icon-center">
      <CheckCircle2 :size="18" /> {{ successMessage }}
    </div>

    <div v-if="errorMessage" class="alert alert-danger flex-icon-center">
      <AlertTriangle :size="18" /> {{ errorMessage }}
    </div>

    <!-- Navigation Tabs -->
    <div class="settings-tabs">
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'profil' }"
        @click="switchTab('profil')"
      >
        <User :size="16" /> Profil Admin
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'keamanan' }"
        @click="switchTab('keamanan')"
      >
        <Lock :size="16" /> Keamanan &amp; Kata Sandi
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'arsip' }"
        @click="switchTab('arsip')"
      >
        <Archive :size="16" /> Retensi &amp; Arsip Otomatis
      </button>
    </div>

    <!-- TAB 1: PROFIL ADMINISTRATOR -->
    <div v-if="activeTab === 'profil'" class="card-settings">
      <div class="card-title">
        <User :size="20" class="icon-brand" />
        <div>
          <h3>Informasi Administrator</h3>
          <p>Perbarui nama, username, dan alamat email utama administrator sistem.</p>
        </div>
      </div>

      <form @submit.prevent="handleUpdateProfil" class="settings-form">
        <div class="form-group">
          <label for="nama">Nama Lengkap</label>
          <input 
            type="text" 
            id="nama" 
            v-model="formProfil.nama" 
            placeholder="Masukkan Nama Lengkap" 
            required 
          />
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="username">Username Log In</label>
            <input 
              type="text" 
              id="username" 
              v-model="formProfil.username" 
              placeholder="Username login" 
              required 
            />
          </div>

          <div class="form-group">
            <label for="email">Alamat Email Resmi</label>
            <input 
              type="email" 
              id="email" 
              v-model="formProfil.email" 
              placeholder="email@kementan.go.id" 
              required 
            />
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save flex-icon-center" :disabled="isLoadingProfil">
            <Save :size="16" /> {{ isLoadingProfil ? 'Menyimpan...' : 'Simpan Perubahan Profil' }}
          </button>
        </div>
      </form>
    </div>

    <!-- TAB 2: KEAMANAN & PASSWORD -->
    <div v-if="activeTab === 'keamanan'" class="card-settings">
      <div class="card-title">
        <KeyRound :size="20" class="icon-brand" />
        <div>
          <h3>Ganti Kata Sandi Administrator</h3>
          <p>Gunakan kata sandi yang kuat (minimal 8 karakter dengan kombinasi huruf dan angka).</p>
        </div>
      </div>

      <form @submit.prevent="handleUpdatePassword" class="settings-form">
        <div class="form-group">
          <label for="pass-lama">Kata Sandi Saat Ini</label>
          <input 
            type="password" 
            id="pass-lama" 
            v-model="formPassword.password_lama" 
            placeholder="Masukkan kata sandi saat ini" 
            required 
          />
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="pass-baru">Kata Sandi Baru</label>
            <input 
              type="password" 
              id="pass-baru" 
              v-model="formPassword.password_baru" 
              placeholder="Minimal 8 karakter" 
              required 
            />
          </div>

          <div class="form-group">
            <label for="pass-konfirm">Konfirmasi Kata Sandi Baru</label>
            <input 
              type="password" 
              id="pass-konfirm" 
              v-model="formPassword.konfirmasi_password" 
              placeholder="Ulangi kata sandi baru" 
              required 
            />
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save flex-icon-center" :disabled="isLoadingPassword">
            <ShieldCheck :size="16" /> {{ isLoadingPassword ? 'Memperbarui...' : 'Perbarui Kata Sandi' }}
          </button>
        </div>
      </form>
    </div>

    <!-- TAB 3: RETENSI & ARSIP DATA OTOMATIS -->
    <div v-if="activeTab === 'arsip'" class="card-settings">
      <div class="card-title">
        <Archive :size="20" class="icon-brand" />
        <div>
          <h3>Retensi &amp; Pengarsipan Data Otomatis</h3>
          <p>Otomasi pencadangan data bulanan dan pengelolaan arsip berkas hasil uji lama (&gt; 3 tahun).</p>
        </div>
      </div>

      <!-- Banner Status Otomasi -->
      <div class="automation-banner">
        <CalendarClock :size="24" class="banner-icon" />
        <div class="banner-text">
          <strong>Sistem Berjalan 100% Otomatis (Hands-Free):</strong>
          Setiap tanggal 1 awal bulan pukul 01:00, sistem secara otomatis mencadangkan seluruh basis data dan berkas LHU ke repositori cadangan server. Selanjutnya pada pukul 02:00, sistem mengarsipkan pengujian yang telah berusia lebih dari 3 tahun guna membebaskan ruang disk server tanpa memerlukan tindakan manual dari petugas.
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoadingArchiveStats" class="loading-panel text-center">
        <RefreshCw :size="24" class="animate-spin text-green" />
        <p class="text-muted text-sm mt-2">Memuat statistik retensi arsip...</p>
      </div>

      <!-- Metrik Arsip Grid -->
      <div v-else class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon-wrap bg-green-subtle text-green"><HardDrive :size="20" /></div>
          <div class="stat-info">
            <span class="stat-label">Pengujian Aktif</span>
            <span class="stat-value text-green">{{ archiveStats?.pengujian_aktif ?? 0 }}</span>
            <span class="stat-desc">Berusia &lt; 3 tahun (portal live)</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon-wrap bg-blue-subtle text-blue"><Archive :size="20" /></div>
          <div class="stat-info">
            <span class="stat-label">Telah Diarsipkan</span>
            <span class="stat-value text-blue">{{ archiveStats?.pengujian_diarsipkan ?? 0 }}</span>
            <span class="stat-desc">Data aman di repositori arsip</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon-wrap bg-amber-subtle text-amber"><Clock :size="20" /></div>
          <div class="stat-info">
            <span class="stat-label">Siap Diarsipkan</span>
            <span class="stat-value text-amber">{{ archiveStats?.siap_diarsipkan ?? 0 }}</span>
            <span class="stat-desc">Akan diarsipkan awal bulan</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon-wrap bg-purple-subtle text-purple"><Database :size="20" /></div>
          <div class="stat-info">
            <span class="stat-label">Cadangan Bulanan</span>
            <span class="stat-value">{{ archiveStats?.total_backups ?? 0 }} Berkas</span>
            <span class="stat-desc" v-if="archiveStats?.latest_backup">Terbaru: {{ archiveStats.latest_backup.size }}</span>
            <span class="stat-desc" v-else>Terjadwal awal bulan</span>
          </div>
        </div>
      </div>

      <!-- Riwayat Cadangan Terakhir -->
      <div v-if="archiveStats?.backup_history?.length" class="backup-history-card">
        <h4>Riwayat Berkas Cadangan Tersimpan</h4>
        <div class="history-list">
          <div v-for="b in archiveStats.backup_history" :key="b.name" class="history-item">
            <div class="history-item-name flex-icon-center">
              <Database :size="16" class="text-green" />
              <span>{{ b.name }}</span>
            </div>
            <span class="badge-size">{{ b.size }}</span>
          </div>
        </div>
      </div>

      <!-- Aksi On-Demand (Opsional) -->
      <div class="manual-actions-card">
        <h4>Tindakan Manual (Opsional / Kebutuhan Khusus)</h4>
        <p class="text-muted text-sm">
          Semua proses pencadangan dan pengarsipan berjalan terjadwal secara otomatis di latar belakang. Anda hanya perlu menggunakan tombol ini jika sewaktu-waktu membutuhkan pencadangan seketika sebelum pemeliharaan server.
        </p>
        <div class="actions-buttons-row">
          <button 
            type="button" 
            class="btn-action flex-icon-center" 
            @click="handleManualBackup" 
            :disabled="isActionRunning"
          >
            <Database :size="16" /> {{ isActionRunning ? 'Memproses...' : 'Cadangkan Data Sekarang' }}
          </button>
          <button 
            type="button" 
            class="btn-action flex-icon-center" 
            @click="handleManualArchive" 
            :disabled="isActionRunning"
          >
            <Archive :size="16" /> {{ isActionRunning ? 'Memproses...' : 'Arsipkan Data Lama Sekarang' }}
          </button>
          <button 
            type="button" 
            class="btn-reload flex-icon-center" 
            @click="fetchArchiveStats" 
            title="Segarkan data statistik"
            :disabled="isLoadingArchiveStats"
          >
            <RefreshCw :size="16" :class="{ 'animate-spin': isLoadingArchiveStats }" />
          </button>
        </div>
      </div>
    </div>

    <!-- Custom Action Confirmation Modal (Pengganti confirm browser) -->
    <div v-if="confirmModal.show" class="modal-backdrop-confirm" @click.self="closeConfirm">
      <div class="confirm-card">
        <div class="confirm-header">
          <div class="confirm-icon-wrap" :class="confirmModal.confirmType === 'warning' ? 'bg-amber-light text-warning' : 'bg-green-light text-green'">
            <Archive v-if="confirmModal.confirmType === 'warning'" :size="24" />
            <Database v-else :size="24" />
          </div>
          <div class="confirm-title-wrap">
            <h3>{{ confirmModal.title }}</h3>
            <span class="confirm-sub">Tindakan Administrator</span>
          </div>
        </div>
        <div class="confirm-body">
          {{ confirmModal.message }}
        </div>
        <div class="confirm-footer">
          <button type="button" @click="closeConfirm" class="btn-cancel">Batal</button>
          <button 
            type="button" 
            @click="executeConfirm" 
            :class="confirmModal.confirmType === 'warning' ? 'btn-confirm-warning' : 'btn-confirm-primary'"
          >
            {{ confirmModal.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pengaturan-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.title {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.alert {
  padding: 14px 18px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
}

.alert-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
}

.alert-danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.settings-tabs {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 2px;
}

.tab-btn {
  background: none;
  border: none;
  padding: 12px 18px;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 3px solid transparent;
  transition: all 0.2s ease;

  &:hover {
    color: #1B4D3E;
  }

  &.active {
    color: #1B4D3E;
    border-bottom-color: #1B4D3E;
  }
}

.card-settings {
  background: #ffffff;
  border-radius: 16px;
  padding: 32px;
  border: 1px solid #e2e8f0;
  border-top: 4px solid #1B4D3E;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.card-title {
  display: flex;
  align-items: flex-start;
  gap: 14px;

  h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
  }

  p {
    margin: 4px 0 0 0;
    font-size: 14px;
    color: #64748b;
  }
}

.icon-brand {
  color: #1B4D3E;
  margin-top: 2px;
}

.settings-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;

  label {
    font-size: 14px;
    font-weight: 700;
    color: #475569;
  }

  input {
    padding: 12px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 15px;
    color: #1e293b;
    outline: none;
    background: #f8fafc;
    transition: all 0.2s ease;

    &:focus {
      background: #ffffff;
      border-color: #1B4D3E;
      box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
    }
  }
}

.form-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

@media (max-width: 640px) {
  .form-grid-2 {
    grid-template-columns: 1fr;
  }
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 8px;
}

.btn-save {
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(27, 77, 62, 0.2);
  transition: all 0.2s ease;

  &:hover:not(:disabled) {
    background: #13382D;
    transform: translateY(-1px);
  }

  &:disabled {
    background: #94a3b8;
    cursor: not-allowed;
  }
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

@media (max-width: 640px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
}

.info-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 16px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 13px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-val {
  font-size: 15px;
  font-weight: 700;
  color: #1e293b;
}

.pdp-notice-box {
  background: #f0f7f4;
  border: 1px solid rgba(27, 77, 62, 0.2);
  border-left: 4px solid #1B4D3E;
  padding: 20px;
  border-radius: 12px;

  .pdp-header {
    font-size: 15px;
    font-weight: 700;
    color: #1B4D3E;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
  }

  .pdp-body {
    margin: 0;
    font-size: 14px;
    color: #334155;
    line-height: 1.6;
  }
}

.flex-icon-center {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Tab 3: Retensi & Arsip Otomatis Styling */
.automation-banner {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-left: 4px solid #166534;
  padding: 16px 20px;
  border-radius: 12px;
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.banner-icon {
  color: #166534;
  flex-shrink: 0;
  margin-top: 2px;
}

.banner-text {
  font-size: 14px;
  line-height: 1.6;
  color: #166534;

  strong {
    display: block;
    margin-bottom: 2px;
  }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

.stat-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 20px;
  border-radius: 14px;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  transition: all 0.2s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }
}

.stat-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-green-subtle { background: #dcfce7; }
.bg-blue-subtle { background: #dbeafe; }
.bg-amber-subtle { background: #fef3c7; }
.bg-purple-subtle { background: #f3e8ff; }

.text-green { color: #166534; }
.text-blue { color: #1d4ed8; }
.text-amber { color: #b45309; }
.text-purple { color: #7e22ce; }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
}

.stat-value {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin: 2px 0;
}

.stat-desc {
  font-size: 12px;
  color: #94a3b8;
}

.backup-history-card, .manual-actions-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 22px;
  border-radius: 14px;

  h4 {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px 0;
  }
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 14px;
}

.history-item {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 12px 16px;
  border-radius: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.history-item-name {
  gap: 10px;
  font-size: 14px;
  font-weight: 600;
  font-family: var(--font-mono);
  color: #334155;
}

.badge-size {
  font-size: 12px;
  font-weight: 700;
  padding: 4px 10px;
  background: #f1f5f9;
  color: #475569;
  border-radius: 6px;
}

.actions-buttons-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
  flex-wrap: wrap;
}

.btn-action {
  background: #ffffff;
  color: #1B4D3E;
  border: 1.5px solid #1B4D3E;
  padding: 10px 18px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  gap: 8px;
  transition: all 0.2s ease;

  &:hover:not(:disabled) {
    background: #1B4D3E;
    color: #ffffff;
  }

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
}

.btn-reload {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #64748b;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;

  &:hover {
    color: #0f172a;
    border-color: #94a3b8;
  }
}

/* Custom Action Confirmation Modal */
.modal-backdrop-confirm {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 16px;
  animation: fadeIn 0.2s ease-out;
}

.confirm-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 440px;
  padding: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.12), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid #e2e8f0;
  animation: scaleIn 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.confirm-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 14px;
}

.confirm-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-amber-light {
  background-color: #fef3c7;
}

.bg-green-light {
  background-color: rgba(27, 77, 62, 0.1);
}

.confirm-title-wrap h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
}

.confirm-sub {
  font-size: 12px;
  color: #64748b;
}

.confirm-body {
  font-size: 14px;
  color: #475569;
  line-height: 1.6;
  margin-bottom: 24px;
}

.confirm-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.confirm-footer button {
  padding: 10px 18px;
  border-radius: 9px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s ease;
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

.btn-confirm-primary {
  background: #1B4D3E;
  color: #ffffff;
  border: none;
}

.btn-confirm-primary:hover {
  background: #14382d;
  box-shadow: 0 4px 12px rgba(27, 77, 62, 0.25);
}

.btn-confirm-warning {
  background: #d97706;
  color: #ffffff;
  border: none;
}

.btn-confirm-warning:hover {
  background: #b45309;
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleIn {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
</style>
