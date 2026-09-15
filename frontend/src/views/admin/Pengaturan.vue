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
  Database,
  Trash2,
  RefreshCw,
  Clock,
  Sparkles
} from '@lucide/vue'

const authStore = useAuthStore()

// State Active Tab
const activeTab = ref('profil') // 'profil', 'keamanan', 'pemeliharaan'

// Maintenance state
const maintenanceStats = ref(null)
const isLoadingMaintenance = ref(false)
const isPruning = ref(false)
const pruneDays = ref(7)

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

// Handler Maintenance
const fetchMaintenanceStats = async () => {
  isLoadingMaintenance.value = true
  try {
    const response = await api.get('/api/admin/maintenance/stats')
    maintenanceStats.value = response.data
  } catch (err) {
    console.error('Gagal memuat statistik pemeliharaan:', err)
  } finally {
    isLoadingMaintenance.value = false
  }
}

const handlePruneData = async () => {
  const daysText = pruneDays.value == 0 ? 'seluruh data yang sudah kedaluwarsa saat ini' : `data yang telah kedaluwarsa lebih dari ${pruneDays.value} hari`
  if (!confirm(`Apakah Anda yakin ingin membersihkan ${daysText}?\nTindakan ini akan menghapus rekaman OTP dan Token kadaluarsa secara permanen dari basis data.`)) {
    return
  }

  clearMessages()
  isPruning.value = true

  try {
    const response = await api.post('/api/admin/maintenance/prune', {
      days: Number(pruneDays.value)
    })
    successMessage.value = response.data.message
    await fetchMaintenanceStats()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal membersihkan data sampah.'
  } finally {
    isPruning.value = false
  }
}

onMounted(() => {
  if (authStore.user) {
    formProfil.nama = authStore.user.nama || ''
    formProfil.username = authStore.user.username || ''
    formProfil.email = authStore.user.email || ''
  }
  fetchMaintenanceStats()
})
</script>

<template>
  <div class="pengaturan-container">
    <!-- Header Page -->
    <div class="page-header">
      <div>
        <h1 class="title">Pengaturan Sistem &amp; Profil</h1>
        <p class="subtitle">Kelola profil administrator dan keamanan kata sandi akun</p>
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
        @click="activeTab = 'profil'"
      >
        <User :size="16" /> Profil Admin
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'keamanan' }"
        @click="activeTab = 'keamanan'"
      >
        <Lock :size="16" /> Keamanan &amp; Kata Sandi
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'pemeliharaan' }"
        @click="activeTab = 'pemeliharaan'; if (!maintenanceStats) fetchMaintenanceStats()"
      >
        <Database :size="16" /> Pemeliharaan Sistem
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

    <!-- TAB 3: PEMELIHARAAN SISTEM (GARBAGE COLLECTION) -->
    <div v-if="activeTab === 'pemeliharaan'" class="card-settings">
      <div class="card-title">
        <Database :size="20" class="icon-brand" />
        <div>
          <h3>Pemeliharaan Basis Data &amp; Pembersihan Sampah</h3>
          <p>Optimalkan performa aplikasi dan efisiensi ruang penyimpanan dengan membersihkan kode OTP kadaluarsa dan token akses publik lama.</p>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="maintenance-grid">
        <div class="m-card">
          <div class="m-card-header">
            <span class="m-card-label">OTP Kedaluwarsa</span>
            <KeyRound :size="18" class="text-amber" />
          </div>
          <div class="m-card-val">
            <span v-if="isLoadingMaintenance" class="skeleton-text">...</span>
            <span v-else>{{ maintenanceStats?.otp_kedaluwarsa ?? 0 }}</span>
          </div>
          <div class="m-card-sub">
            dari total {{ maintenanceStats?.total_otp ?? 0 }} riwayat kode OTP
          </div>
        </div>

        <div class="m-card">
          <div class="m-card-header">
            <span class="m-card-label">Token Akses Kedaluwarsa</span>
            <Lock :size="18" class="text-indigo" />
          </div>
          <div class="m-card-val">
            <span v-if="isLoadingMaintenance" class="skeleton-text">...</span>
            <span v-else>{{ maintenanceStats?.token_kedaluwarsa ?? 0 }}</span>
          </div>
          <div class="m-card-sub">
            dari total {{ maintenanceStats?.total_token ?? 0 }} token akses publik
          </div>
        </div>

        <div class="m-card">
          <div class="m-card-header">
            <span class="m-card-label">Pengujian di Tempat Sampah</span>
            <Trash2 :size="18" class="text-rose" />
          </div>
          <div class="m-card-val">
            <span v-if="isLoadingMaintenance" class="skeleton-text">...</span>
            <span v-else>{{ maintenanceStats?.pengujian_terhapus ?? 0 }}</span>
          </div>
          <div class="m-card-sub">
            data soft-deleted yang diarsipkan
          </div>
        </div>
      </div>

      <!-- Automation Info Banner -->
      <div class="maintenance-banner">
        <div class="banner-icon">
          <Clock :size="22" />
        </div>
        <div class="banner-content">
          <h4>Pembersihan Otomatis Terjadwal (Cron Job)</h4>
          <p>
            Sistem telah dilengkapi penjadwal otomatis di latar belakang (<strong>Laravel Scheduler</strong>) yang berjalan setiap hari pada tengah malam (pukul 00:00) untuk membuang token dan OTP yang telah kedaluwarsa lebih dari 7 hari:
          </p>
          <code class="cron-command">php artisan auth:prune-expired --days=7</code>
        </div>
      </div>

      <!-- Manual Action Box -->
      <div class="manual-clean-box">
        <div class="box-header">
          <Sparkles :size="18" class="text-emerald" />
          <h4>Pembersihan Manual Sesuai Kebutuhan (On-Demand)</h4>
        </div>
        <p class="box-desc">
          Anda dapat memicu pembersihan secara manual sewaktu-waktu untuk segera mengosongkan rekaman yang sudah tidak berlaku tanpa menunggu jadwal harian:
        </p>

        <div class="clean-controls">
          <div class="filter-group">
            <label for="prune-days">Batas Retensi Kedaluwarsa:</label>
            <select id="prune-days" v-model="pruneDays" class="select-input">
              <option :value="0">Semua yang sudah kedaluwarsa saat ini (0 hari)</option>
              <option :value="3">Kedaluwarsa lebih dari 3 hari lalu</option>
              <option :value="7">Kedaluwarsa lebih dari 7 hari lalu (Standar)</option>
              <option :value="30">Kedaluwarsa lebih dari 30 hari lalu</option>
            </select>
          </div>

          <div class="action-buttons">
            <button 
              type="button" 
              class="btn-refresh flex-icon-center" 
              @click="fetchMaintenanceStats" 
              :disabled="isLoadingMaintenance"
            >
              <RefreshCw :size="15" :class="{ 'spin-icon': isLoadingMaintenance }" /> 
              {{ isLoadingMaintenance ? 'Memuat...' : 'Segarkan Statistik' }}
            </button>
            <button 
              type="button" 
              class="btn-prune flex-icon-center" 
              @click="handlePruneData" 
              :disabled="isPruning"
            >
              <Trash2 :size="16" v-if="!isPruning" />
              <RefreshCw :size="16" class="spin-icon" v-else />
              {{ isPruning ? 'Sedang Membersihkan...' : 'Bersihkan Data Sampah Sekarang' }}
            </button>
          </div>
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
  gap: 8px;
}

/* Maintenance Tab Styling */
.maintenance-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

@media (max-width: 860px) {
  .maintenance-grid {
    grid-template-columns: 1fr;
  }
}

.m-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  transition: all 0.2s ease;

  &:hover {
    border-color: #cbd5e1;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
  }
}

.m-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.m-card-label {
  font-size: 13px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.m-card-val {
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

.m-card-sub {
  font-size: 13px;
  color: #94a3b8;
}

.text-amber {
  color: #d97706;
}

.text-indigo {
  color: #4f46e5;
}

.text-rose {
  color: #e11d48;
}

.text-emerald {
  color: #059669;
}

.maintenance-banner {
  display: flex;
  gap: 16px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-left: 4px solid #3b82f6;
  padding: 18px 20px;
  border-radius: 12px;
}

.banner-icon {
  color: #2563eb;
  padding-top: 2px;
}

.banner-content {
  flex: 1;

  h4 {
    margin: 0 0 6px 0;
    font-size: 15px;
    font-weight: 700;
    color: #1e3a8a;
  }

  p {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #334155;
    line-height: 1.5;
  }
}

.cron-command {
  display: inline-block;
  background: #1e293b;
  color: #38bdf8;
  padding: 6px 12px;
  border-radius: 6px;
  font-family: var(--font-mono);
  font-size: 13px;
}

.manual-clean-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
}

.box-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;

  h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
  }
}

.box-desc {
  margin: 0 0 20px 0;
  font-size: 14px;
  color: #64748b;
  line-height: 1.5;
}

.clean-controls {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 16px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;

  label {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
  }
}

.select-input {
  padding: 10px 14px;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  color: #1e293b;
  background: #f8fafc;
  outline: none;
  min-width: 280px;
  transition: all 0.2s ease;

  &:focus {
    background: #ffffff;
    border-color: #1B4D3E;
    box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
  }
}

.action-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-refresh {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover:not(:disabled) {
    background: #e2e8f0;
    color: #1e293b;
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.btn-prune {
  background: #dc2626;
  color: #ffffff;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.25);
  transition: all 0.2s ease;

  &:hover:not(:disabled) {
    background: #b91c1c;
    transform: translateY(-1px);
  }

  &:disabled {
    background: #f87171;
    cursor: not-allowed;
  }
}

.spin-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
