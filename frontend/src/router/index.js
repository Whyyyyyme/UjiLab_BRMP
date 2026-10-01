import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAksesPublikStore } from '../stores/aksesPublik'

// Route definitions
const routes = [
  // === MODUL PUBLIK (PENGGUNA JASA) ===
  {
    path: '/',
    name: 'CariPengujian',
    component: () => import('../views/public/CariPengujian.vue'),
    meta: { public: true }
  },
  {
    path: '/otp',
    name: 'VerifikasiOtp',
    component: () => import('../views/public/VerifikasiOtp.vue'),
    meta: { public: true }
  },
  {
    path: '/skm',
    name: 'FormSkm',
    component: () => import('../views/public/FormSkm.vue'),
    meta: { public: true, needsTokenAkses: true }
  },
  {
    path: '/hasil',
    name: 'HasilUnduh',
    component: () => import('../views/public/HasilUnduh.vue'),
    meta: { public: true, needsTokenAkses: true }
  },
  {
    path: '/verifikasi/:nomor_pengujian(.*)',
    name: 'VerifikasiDokumen',
    component: () => import('../views/public/VerifikasiDokumen.vue'),
    meta: { public: true }
  },

  // === MODUL PETUGAS / ADMIN ===
  {
    path: '/portal-brmp/login',
    name: 'AdminLogin',
    component: () => import('../views/admin/Login.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/portal-brmp',
    component: () => import('../views/admin/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: { name: 'AdminDashboard' }
      },
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('../views/admin/Dashboard.vue')
      },
      {
        path: 'pengujian',
        name: 'DataPengujian',
        component: () => import('../views/admin/DataPengujian.vue')
      },
      {
        path: 'pengujian/:id/upload',
        name: 'UploadHasil',
        component: () => import('../views/admin/UploadHasil.vue')
      },
      {
        path: 'laporan-skm',
        name: 'LaporanSkm',
        component: () => import('../views/admin/LaporanSkm.vue')
      },
      {
        path: 'log-aktivitas',
        name: 'LogAktivitas',
        component: () => import('../views/admin/LogAktivitas.vue')
      },
      {
        path: 'pengaturan',
        name: 'PengaturanSistem',
        component: () => import('../views/admin/Pengaturan.vue')
      },
      {
        path: 'ganti-password',
        name: 'AdminGantiPassword',
        component: () => import('../views/admin/GantiPassword.vue')
      }
    ]
  },
  // Fallback 404 route
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../views/public/NotFound.vue'),
    meta: { public: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Route navigation guard
router.beforeEach((to) => {
  const authStore = useAuthStore()
  const aksesPublikStore = useAksesPublikStore()

  // 1. Cek Proteksi Halaman Admin
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!authStore.isAuthenticated) {
      // Jika belum login, lempar ke login admin
      return { name: 'AdminLogin', query: { redirect: to.fullPath } }
    }

    // PENGAMAN: Jika wajib ganti password dan tidak sedang mengakses halaman ganti password
    if (authStore.user?.wajib_ganti_password && to.name !== 'AdminGantiPassword') {
      return { name: 'AdminGantiPassword' }
    }
  }

  // 2. Cegah akses halaman Login jika sudah login
  if (to.matched.some(record => record.meta.guestOnly)) {
    if (authStore.isAuthenticated) {
      return { name: 'AdminDashboard' }
    }
  }

  // 3. Cek Proteksi Halaman Publik yang butuh token OTP
  if (to.matched.some(record => record.meta.needsTokenAkses)) {
    if (!aksesPublikStore.hasAkses) {
      // Jika tidak punya token akses OTP, lempar kembali ke halaman pencarian nomor pengujian
      return { name: 'CariPengujian' }
    }
  }
})

export default router
