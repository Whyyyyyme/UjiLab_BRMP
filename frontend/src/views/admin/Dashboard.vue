<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { 
  AlertTriangle, 
  FlaskConical, 
  Clock, 
  CheckCircle2, 
  Mail, 
  Plus, 
  ClipboardList, 
  Settings,
  Sparkles
} from '@lucide/vue'

const authStore = useAuthStore()

const stats = ref({
  total_pengujian: 0,
  diproses: 0,
  selesai: 0,
  notifikasi_gagal: 0
})
const recentLogs = ref([])
const isLoading = ref(true)

const fetchDashboardData = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/api/admin/dashboard')
    stats.value = response.data.stats
    recentLogs.value = response.data.recent_logs
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="dashboard-view">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div class="banner-text">
        <h1>Selamat Datang Kembali, {{ authStore.user?.nama || 'Administrator' }}! <Sparkles :size="24" style="color: #EAB308; display: inline-block; vertical-align: middle; margin-left: 6px;" /></h1>
        <p>Kelola dan pantau proses pengujian laboratorium BRMP Biogen hari ini.</p>
      </div>
      <div class="banner-bg">
        <img src="../../assets/logo-kementan.png" alt="" aria-hidden="true" class="banner-logo-img" />
      </div>
    </div>

    <!-- Notification Alert for Failed Emails -->
    <div v-if="stats.notifikasi_gagal > 0" class="alert-banner warning">
      <span class="alert-icon"><AlertTriangle :size="20" /></span>
      <div class="alert-content">
        <h4>Notifikasi Email Gagal Terkirim!</h4>
        <p>Terdapat <strong>{{ stats.notifikasi_gagal }}</strong> notifikasi email hasil uji yang belum berhasil terkirim. Mohon periksa log notifikasi email.</p>
      </div>
      <router-link :to="{ name: 'DataPengujian' }" class="btn-alert">Periksa Data Pengujian</router-link>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue"><FlaskConical :size="24" /></div>
        <div class="stat-info">
          <span class="stat-label">Total Pengujian</span>
          <h2 class="stat-value">{{ stats.total_pengujian }}</h2>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon yellow"><Clock :size="24" /></div>
        <div class="stat-info">
          <span class="stat-label">Menunggu Unggah Berkas</span>
          <h2 class="stat-value">{{ stats.diproses }}</h2>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon green"><CheckCircle2 :size="24" /></div>
        <div class="stat-info">
          <span class="stat-label">Selesai &amp; Terverifikasi</span>
          <h2 class="stat-value">{{ stats.selesai }}</h2>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red"><Mail :size="24" /></div>
        <div class="stat-info">
          <span class="stat-label">Gagal Notifikasi Email</span>
          <h2 class="stat-value">{{ stats.notifikasi_gagal }}</h2>
        </div>
      </div>
    </div>

    <div class="dashboard-content-layout">
      <!-- Shortcuts / Quick Actions -->
      <div class="content-card quick-actions">
        <h3>Aksi Cepat</h3>
        <div class="actions-grid">
          <router-link :to="{ name: 'DataPengujian' }" class="action-btn">
            <span class="action-icon brand"><Plus :size="22" /></span>
            <div class="action-info">
              <h4>Tambah Data Pengujian</h4>
              <p>Input nomor pengujian baru dan unggah dokumen hasil uji.</p>
            </div>
          </router-link>
          
          <router-link :to="{ name: 'LaporanSkm' }" class="action-btn">
            <span class="action-icon amber"><ClipboardList :size="22" /></span>
            <div class="action-info">
              <h4>Laporan SKM &amp; IKM</h4>
              <p>Lihat dan unduh rekapitulasi Survei Kepuasan Masyarakat.</p>
            </div>
          </router-link>

          <router-link :to="{ name: 'PengaturanSistem' }" class="action-btn">
            <span class="action-icon blue"><Settings :size="22" /></span>
            <div class="action-info">
              <h4>Pengaturan Sistem &amp; Profil</h4>
              <p>Kelola profil administrator, serta kata sandi akun.</p>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Recent Log Activity -->
      <div class="content-card recent-activity">
        <h3>Aktivitas Terkini</h3>
        <div v-if="isLoading" class="logs-list">
          <div v-for="n in 3" :key="n" class="log-item" style="opacity: 0.7;">
            <div class="skeleton-circle" style="width: 10px; height: 10px; margin-top: 6px;"></div>
            <div class="log-details" style="width: 100%;">
              <div class="skeleton-bar" style="width: 70%; height: 14px; margin-bottom: 6px;"></div>
              <div class="skeleton-bar" style="width: 40%; height: 12px;"></div>
            </div>
          </div>
        </div>

        <div v-else-if="recentLogs.length === 0" class="empty-state">
          <p>Belum ada aktivitas tercatat hari ini.</p>
        </div>
        <div v-else class="logs-list">
          <div v-for="log in recentLogs" :key="log.id" class="log-item">
            <div class="log-dot"></div>
            <div class="log-details">
              <p class="log-message"><strong>{{ log.petugas_nama }}</strong> {{ log.aksi }}</p>
              <span class="log-time">{{ new Date(log.created_at).toLocaleTimeString('id-ID') }} - IP {{ log.ip_address }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-view {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.welcome-banner {
  background: linear-gradient(135deg, #1B4D3E 0%, #13382D 100%);
  border-radius: 16px;
  padding: 32px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 25px rgba(27, 77, 62, 0.2);
}

.banner-text {
  position: relative;
  z-index: 2;
}

.banner-text h1 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 800;
  color: #ffffff !important;
  line-height: 1.3;
}

.banner-text p {
  margin: 0;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.9) !important;
  line-height: 1.5;
}

.banner-bg {
  position: absolute;
  right: -20px;
  bottom: -30px;
  opacity: 0.18;
  pointer-events: none;
}

.banner-logo-img {
  width: 160px;
  height: 160px;
  object-fit: contain;
  display: block;
}

.alert-banner {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 24px;
  border-radius: 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.alert-icon {
  font-size: 24px;
}

.alert-content {
  flex: 1;
}

.alert-content h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.alert-content p {
  margin: 0;
  font-size: 13px;
  opacity: 0.9;
}

.btn-alert {
  background: #ef4444;
  color: white;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn-alert:hover {
  background: #dc2626;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}

.stat-card {
  background: #ffffff;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02), 0 10px 15px -3px rgba(0, 0, 0, 0.03);
  border: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 54px;
  height: 54px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.stat-card:hover .stat-icon {
  transform: scale(1.05);
}

.stat-icon.blue { 
  background: rgba(37, 99, 235, 0.1); 
  color: #2563eb;
}
.stat-icon.yellow { 
  background: rgba(217, 119, 6, 0.1); 
  color: #d97706;
}
.stat-icon.green { 
  background: rgba(27, 77, 62, 0.1); 
  color: #1B4D3E;
}
.stat-icon.red { 
  background: rgba(220, 38, 38, 0.1); 
  color: #dc2626;
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.stat-value {
  margin: 4px 0 0 0;
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
}

.dashboard-content-layout {
  display: grid;
  grid-template-columns: 3fr 2fr;
  gap: 24px;
}

@media (max-width: 1024px) {
  .dashboard-content-layout {
    grid-template-columns: 1fr;
  }
}

.content-card {
  background: #ffffff;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
}

.content-card h3 {
  margin: 0 0 20px 0;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 12px;
}

.actions-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 14px;
  background: #f8fafc;
  text-decoration: none;
  border: 1.5px solid #e2e8f0;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: #ffffff;
  border-color: #1B4D3E;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(27, 77, 62, 0.08);
}

.action-icon {
  font-size: 24px;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.action-icon.brand {
  background: rgba(27, 77, 62, 0.1);
  color: #1B4D3E;
}

.action-icon.amber {
  background: rgba(217, 119, 6, 0.1);
  color: #d97706;
}

.action-icon.blue {
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
}

.action-btn:hover .action-icon {
  transform: scale(1.08);
}

.action-info h4 {
  margin: 0 0 4px 0;
  font-size: 15.5px;
  font-weight: 700;
  color: #0f172a;
}

.action-info p {
  margin: 0;
  font-size: 13.5px;
  color: #64748b;
  line-height: 1.4;
}

.loading-state, .empty-state {
  padding: 30px;
  text-align: center;
  color: #64748b;
  font-size: 14px;
}

.logs-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.log-item {
  display: flex;
  gap: 12px;
  position: relative;
}

.log-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #1B4D3E;
  margin-top: 6px;
  flex-shrink: 0;
}

.log-details {
  display: flex;
  flex-direction: column;
}

.log-message {
  margin: 0;
  font-size: 14px;
  color: #334155;
  line-height: 1.4;
}

.log-time {
  font-size: 12.5px;
  color: #64748b;
  margin-top: 4px;
}

@media (max-width: 640px) {
  .welcome-banner {
    padding: 20px;
  }
  .welcome-text h2 {
    font-size: 20px;
  }
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .stat-card {
    padding: 16px;
  }
  .action-item {
    padding: 12px 16px;
  }
}
</style>
