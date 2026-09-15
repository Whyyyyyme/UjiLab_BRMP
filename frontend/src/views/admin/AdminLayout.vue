<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { 
  LayoutDashboard, 
  FlaskConical, 
  ClipboardList, 
  History, 
  Settings, 
  LogOut,
  AlertTriangle,
  Menu,
  X
} from '@lucide/vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const showLogoutConfirm = ref(false)
const isMobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}

watch(() => route.path, () => {
  isMobileMenuOpen.value = false
})

const handleLogout = () => {
  showLogoutConfirm.value = true
}

const confirmLogout = async () => {
  showLogoutConfirm.value = false
  await authStore.logout()
  router.push({ name: 'AdminLogin' })
}
</script>

<template>
  <div class="admin-container">
    <!-- Topbar Mobile Bar (Hanya tampil di layar seluler) -->
    <header class="mobile-topbar">
      <div class="mobile-brand">
        <img src="../../assets/logo-kementan.png" alt="Logo Kementan" class="mobile-logo" />
        <div class="mobile-brand-title">
          <h3>BRMP Biogen</h3>
          <span>Lab Portal</span>
        </div>
      </div>
      <button @click="toggleMobileMenu" class="btn-mobile-toggle" :aria-label="isMobileMenuOpen ? 'Tutup Menu' : 'Buka Menu'">
        <X v-if="isMobileMenuOpen" :size="22" />
        <Menu v-else :size="22" />
      </button>
    </header>

    <!-- Overlay Backdrop untuk Mobile Drawer -->
    <div 
      v-if="isMobileMenuOpen" 
      class="sidebar-overlay" 
      @click="closeMobileMenu"
    ></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" :class="{ 'mobile-open': isMobileMenuOpen }">
      <div class="sidebar-header">
        <div class="logo-icon">
          <img src="../../assets/logo-kementan.png" alt="Logo Kementan" class="sidebar-logo-img" />
        </div>
        <div class="logo-text">
          <h3>BRMP Biogen</h3>
          <span>Lab Portal</span>
        </div>
      </div>
      
      <nav class="sidebar-nav">
        <router-link :to="{ name: 'AdminDashboard' }" class="nav-item" active-class="active">
          <span class="icon"><LayoutDashboard :size="18" /></span> Dashboard
        </router-link>
        
        <router-link :to="{ name: 'DataPengujian' }" class="nav-item" active-class="active">
          <span class="icon"><FlaskConical :size="18" /></span> Data Pengujian
        </router-link>
        
        <router-link :to="{ name: 'LaporanSkm' }" class="nav-item" active-class="active">
          <span class="icon"><ClipboardList :size="18" /></span> Laporan SKM
        </router-link>
        
        <router-link 
          :to="{ name: 'LogAktivitas' }" 
          class="nav-item" 
          active-class="active"
        >
          <span class="icon"><History :size="18" /></span> Log Aktivitas
        </router-link>
        
        <router-link 
          :to="{ name: 'PengaturanSistem' }" 
          class="nav-item" 
          active-class="active"
        >
          <span class="icon"><Settings :size="18" /></span> Pengaturan
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">{{ authStore.user?.nama?.charAt(0) || 'A' }}</div>
          <div class="user-details">
            <span class="user-name">{{ authStore.user?.nama || 'Administrator' }}</span>
            <span class="user-role">Administrator</span>
          </div>
        </div>
        <button @click="handleLogout" class="btn-logout" title="Keluar">
          <LogOut :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> Logout
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
      <header class="content-header">
        <div class="header-title">
          <h2>Halaman Administrasi</h2>
          <p>Sistem Pengujian Laboratorium</p>
        </div>
        <div class="header-actions">
          <span class="date-badge">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
        </div>
      </header>
      
      <section class="content-body">
        <router-view />
      </section>
    </main>

    <!-- Custom Logout Confirmation Modal -->
    <div v-if="showLogoutConfirm" class="modal-backdrop-confirm">
      <div class="confirm-card">
        <div class="confirm-header">
          <AlertTriangle :size="24" class="text-warning" />
          <h3>Konfirmasi Keluar</h3>
        </div>
        <div class="confirm-body">
          Apakah Anda yakin ingin keluar dari portal pengujian laboratorium BRMP Biogen?
        </div>
        <div class="confirm-footer">
          <button @click="showLogoutConfirm = false" class="btn-cancel">Batal</button>
          <button @click="confirmLogout" class="btn-confirm">Keluar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-container {
  display: flex;
  min-height: 100vh;
  background-color: #f0f2f5;
  font-family: var(--font-sans);
}

.mobile-topbar {
  display: none;
}

.sidebar {
  width: 280px;
  background: linear-gradient(180deg, #1B4D3E 0%, #0F3328 100%);
  color: #f8fafc;
  display: flex;
  flex-direction: column;
  box-shadow: 4px 0 10px rgba(0, 0, 0, 0.15);
  z-index: 10;
}

.sidebar-header {
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.logo-icon {
  background: rgba(255, 255, 255, 0.12);
  padding: 6px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidebar-logo-img {
  width: 44px;
  height: 44px;
  object-fit: contain;
  display: block;
}

.logo-text h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #ffffff !important;
  letter-spacing: 0.5px;
}

.logo-text span {
  font-size: 12.5px;
  color: #94a3b8;
}

.sidebar-nav {
  flex: 1;
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: #94a3b8;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 500;
  font-size: 15px;
  transition: all 0.2s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #f8fafc;
}

.nav-item.active {
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
  border-left: 4px solid #EAB308;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.nav-item .icon {
  font-size: 16px;
}

.sidebar-footer {
  padding: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  overflow: hidden;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #EAB308;
  color: #1B4D3E;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 16px;
  flex-shrink: 0;
}

.user-details {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: #f8fafc;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}

.user-role {
  font-size: 12px;
  color: #94a3b8;
}

.btn-logout {
  background: transparent;
  border: none;
  color: #ef4444;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  transition: all 0.2s ease;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-logout:hover {
  background: rgba(239, 68, 68, 0.1);
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow-y: auto;
}

.content-header {
  background: #ffffff;
  padding: 20px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
}

.header-title h2 {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  color: #1e293b;
}

.header-title p {
  margin: 4px 0 0 0;
  font-size: 14px;
  color: #64748b;
}

.date-badge {
  background: #f1f5f9;
  padding: 8px 16px;
  border-radius: 30px;
  font-size: 13.5px;
  color: #475569;
  font-weight: 500;
}

.content-body {
  padding: 32px;
  flex: 1;
}

@media (max-width: 768px) {
  .admin-container {
    flex-direction: column;
  }
  .mobile-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #1B4D3E;
    color: #ffffff;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }
  .mobile-brand {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .mobile-logo {
    width: 32px;
    height: 32px;
    object-fit: contain;
  }
  .mobile-brand-title h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #ffffff;
  }
  .mobile-brand-title span {
    font-size: 10px;
    color: #cbd5e1;
  }
  .btn-mobile-toggle {
    background: rgba(255, 255, 255, 0.12);
    border: none;
    color: #ffffff;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
  }
  .btn-mobile-toggle:hover {
    background: rgba(255, 255, 255, 0.2);
  }
  .sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 149;
  }
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 280px;
    z-index: 150;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.3);
  }
  .sidebar.mobile-open {
    transform: translateX(0);
  }
  .main-content {
    height: auto;
  }
  .content-header {
    padding: 16px;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  .content-body {
    padding: 16px;
  }
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

.text-warning {
  color: #f59e0b;
}

.confirm-body {
  font-size: 14px;
  color: #64748b;
  line-height: 1.5;
  margin-bottom: 24px;
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

.btn-confirm {
  background: #ef4444;
  color: #ffffff;
  border: none;
}

.btn-confirm:hover {
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
</style>
