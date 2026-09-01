<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAksesPublikStore } from '../../stores/aksesPublik'
import { AlertTriangle, Search } from '@lucide/vue'

const router = useRouter()
const aksesPublikStore = useAksesPublikStore()

const nomorPengujian = ref('')
const persetujuanPdp = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

const handleSearch = async () => {
  if (!persetujuanPdp.value) {
    errorMessage.value = 'Anda harus menyetujui pemrosesan data pribadi untuk melanjutkan.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.post('/api/public/pengujian/cari', {
      nomor_pengujian: nomorPengujian.value.trim()
    })

    // Simpan informasi pengujian ke store sebelum berpindah ke halaman OTP
    aksesPublikStore.pengujianId = response.data.id
    aksesPublikStore.nomorPengujian = response.data.nomor_pengujian
    
    // Kirim informasi email tersamar ke router state/query
    router.push({
      name: 'VerifikasiOtp',
      query: { email: response.data.email_tersamar }
    })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mencari data.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="public-container">
    <div class="landing-card">
      <div class="brand">
        <img src="../../assets/logo-kementan.png" alt="Logo Kementan" class="brand-logo" />
        <h2>BRMP BIOGEN</h2>
        <p class="brand-sub">Sistem Distribusi Hasil Uji Laboratorium</p>
      </div>

      <h1 class="page-title">Pencarian Hasil Uji</h1>
      <p class="page-desc">Masukkan nomor pengujian sampel Anda untuk memverifikasi data dan mengunduh berkas laporan hasil uji resmi.</p>

      <form @submit.prevent="handleSearch" class="search-form">
        <div v-if="errorMessage" class="alert alert-danger">
          <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
        </div>

        <div class="form-group">
          <label for="nomor">Nomor Pengujian / Kode Sampel</label>
          <div class="input-wrapper">
            <span class="input-icon"><Search :size="18" /></span>
            <input 
              type="text" 
              id="nomor" 
              v-model="nomorPengujian" 
              placeholder="Contoh: UJI-2026-0001" 
              required 
              :disabled="isLoading"
            />
          </div>
        </div>

        <!-- PDP Consent Checkbox (F-03, UU PDP No. 27/2022) -->
        <div class="pdp-consent">
          <label class="consent-checkbox">
            <input type="checkbox" v-model="persetujuanPdp" :disabled="isLoading" />
            <span class="checkmark"></span>
            <span class="consent-text">
              Saya menyetujui pemrosesan data pribadi saya (nama, email, dan data pengujian) untuk keperluan verifikasi hasil pengujian sesuai <strong>UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi</strong>.
            </span>
          </label>
        </div>

        <button type="submit" class="btn-primary" :disabled="isLoading">
          <span v-if="isLoading">Mencari Data...</span>
          <span v-else>Cari &amp; Lanjutkan</span>
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.public-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at 10% 20%, rgba(243, 244, 246, 1) 0%, rgba(229, 231, 235, 1) 90%);
  padding: 20px;
}

.landing-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  width: 100%;
  max-width: 480px;
  padding: 40px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(226, 232, 240, 0.8);
  animation: cardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes cardEnter {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.brand {
  text-align: center;
  margin-bottom: 32px;
}

.brand-logo {
  height: 64px;
  object-fit: contain;
  display: inline-block;
  margin-bottom: 8px;
}

.brand h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: 1px;
}

.brand-sub {
  margin: 4px 0 0 0;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px 0;
  text-align: center;
}

.page-desc {
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
  text-align: center;
  margin: 0 0 28px 0;
}

.search-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  line-height: 1.5;
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
  font-size: 12px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  display: flex;
  align-items: center;
  pointer-events: none;
}

.input-wrapper input {
  width: 100%;
  padding: 12px 14px 12px 42px;
  border: 1.5px solid #cbd5e1;
  border-radius: 12px;
  font-size: 14px;
  color: #1e293b;
  outline: none;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.input-wrapper input:focus {
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
}

.pdp-consent {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px;
}

.consent-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  font-size: 12px;
  color: #475569;
  line-height: 1.5;
}

.consent-checkbox input {
  margin-top: 3px;
  accent-color: #1B4D3E;
}

.btn-primary {
  background: #1B4D3E;
  color: white;
  border: none;
  padding: 14px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(27, 77, 62, 0.25);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
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

.flex-icon-center {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

@media (max-width: 640px) {
  .public-container {
    padding: 16px 12px;
  }
  .landing-card {
    padding: 24px 18px;
    border-radius: 20px;
  }
  .brand {
    margin-bottom: 16px;
  }
  .brand-logo {
    height: 52px;
  }
  .page-title {
    font-size: 20px;
  }
}
</style>
