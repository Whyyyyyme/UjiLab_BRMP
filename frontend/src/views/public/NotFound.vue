<script setup>
import { useRouter } from 'vue-router'
import { Home, HelpCircle } from '@lucide/vue'

const router = useRouter()
</script>

<template>
  <div class="notfound-page">
    <div class="top-accent-bar"></div>

    <div class="notfound-card">
      <!-- Header Instansi -->
      <div class="brand-header">
        <img src="../../assets/logo-kementan.png" alt="Logo Kementerian Pertanian" class="kementan-logo" />
        <div class="brand-text">
          <span class="ministry-tag">KEMENTERIAN PERTANIAN</span>
          <span class="agency-title">BRMP BIOGEN</span>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Ilustrasi / Badge 404 -->
      <div class="status-visual">
        <div class="error-code-badge">404</div>
      </div>

      <!-- Pesan Konten -->
      <div class="content-text">
        <h1 class="error-title">Halaman Tidak Ditemukan</h1>
        <p class="error-desc">
          Tautan yang Anda tuju mungkin salah ketik, telah dipindahkan, atau tidak lagi tersedia di sistem layanan pengujian laboratorium.
        </p>
      </div>

      <!-- Tombol Aksi -->
      <div class="action-buttons">
        <button @click="router.push('/')" class="btn-primary flex-icon-center">
          <Home :size="16" />
          <span>Kembali ke Beranda</span>
        </button>
      </div>

      <!-- Footer Bantuan -->
      <div class="notfound-footer">
        <HelpCircle :size="14" class="footer-icon" />
        <span>Butuh bantuan layanan? Hubungi Call Center BRMP Biogen: <strong>0811-1756-776</strong></span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.notfound-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 24px 16px;
  font-family: var(--font-sans);
  position: relative;
}

.top-accent-bar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #1B4D3E 0%, #2e7d32 60%, #EAB308 100%);
}

.notfound-card {
  width: 100%;
  max-width: 480px;
  background: #ffffff;
  border-radius: 20px;
  padding: 36px 32px;
  box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
  border: 1px solid #e2e8f0;
  text-align: center;
}

/* Brand Header */
.brand-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.kementan-logo {
  width: 38px;
  height: 38px;
  object-fit: contain;
}

.brand-text {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.ministry-tag {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.6px;
  color: #64748b;
  text-transform: uppercase;
}

.agency-title {
  font-size: 16px;
  font-weight: 800;
  color: #1B4D3E;
  letter-spacing: -0.2px;
}

.divider {
  height: 1px;
  background: #e2e8f0;
  margin: 24px 0 20px 0;
}

/* 404 Badge Visual */
.status-visual {
  margin-bottom: 16px;
}

.error-code-badge {
  display: inline-block;
  font-size: 48px;
  font-weight: 900;
  letter-spacing: -1.5px;
  line-height: 1;
  color: #1B4D3E;
  background: rgba(27, 77, 62, 0.07);
  padding: 8px 24px;
  border-radius: 14px;
  border: 1px solid rgba(27, 77, 62, 0.12);
}

/* Content Text */
.content-text {
  margin-bottom: 28px;
}

.error-title {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 8px 0;
  letter-spacing: -0.4px;
}

.error-desc {
  font-size: 14px;
  color: #64748b;
  line-height: 1.55;
  margin: 0;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 24px;
}

.flex-icon-center {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-primary {
  width: 100%;
  padding: 12px 18px;
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 14.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s ease;
  box-shadow: 0 2px 4px rgba(27, 77, 62, 0.15);
}

.btn-primary:hover {
  background: #163f33;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(27, 77, 62, 0.22);
}

.btn-secondary {
  width: 100%;
  padding: 11px 18px;
  background: #f8fafc;
  color: #334155;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s ease;
}

.btn-secondary:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  color: #0f172a;
}

/* Footer Help */
.notfound-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 12px;
  color: #64748b;
  padding-top: 16px;
  border-top: 1px dashed #e2e8f0;
}

.footer-icon {
  color: #1B4D3E;
  flex-shrink: 0;
}

@media (max-width: 480px) {
  .notfound-card {
    padding: 28px 20px;
  }
  .error-code-badge {
    font-size: 40px;
    padding: 6px 20px;
  }
  .error-title {
    font-size: 20px;
  }
}
</style>
