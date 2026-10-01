<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAksesPublikStore } from '../../stores/aksesPublik'
import { 
  ClipboardList, 
  AlertTriangle, 
  CheckCircle2, 
  Send, 
  User, 
  Check, 
  ShieldCheck,
  ArrowRight
} from '@lucide/vue'

const router = useRouter()
const aksesPublikStore = useAksesPublikStore()

// State
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const isDraftRestored = ref(false)

const form = reactive({
  nama: aksesPublikStore.nama_pemohon || '',
  jenis_kelamin: '',
  pendidikan: '',
  usia: '',
  pekerjaan: '',
  disabilitas: '',
  u1: null,
  u2: null,
  u3: null,
  u4: null,
  u5: null,
  u6: null,
  u7: null,
  u8: null,
  u9: null,
  u10: null,
  u11: null,
  u12: null,
  u13: null,
  u14: null,
  u15: null,
  u16: null
})

// Kunci penyimpanan draf unik per nomor/ID pengujian
const DRAFT_KEY = computed(() => `skm_draft_${aksesPublikStore.pengujianId || 'active'}`)

const restoreDraft = () => {
  try {
    const saved = localStorage.getItem(DRAFT_KEY.value)
    if (saved) {
      const parsed = JSON.parse(saved)
      let count = 0
      Object.keys(parsed).forEach(k => {
        if (k in form && parsed[k] !== null && parsed[k] !== '') {
          form[k] = parsed[k]
          count++
        }
      })
      if (count > 0) {
        isDraftRestored.value = true
      }
    }
  } catch {
    // Abaikan jika localStorage tidak diizinkan di browser
  }
}

const clearDraft = () => {
  try {
    localStorage.removeItem(DRAFT_KEY.value)
    isDraftRestored.value = false
  } catch {}
}

// Pantau setiap perubahan formulir untuk disimpan secara otomatis
watch(
  form,
  (newVal) => {
    try {
      localStorage.setItem(DRAFT_KEY.value, JSON.stringify(newVal))
    } catch {}
  },
  { deep: true }
)

// Proteksi jika token tidak ada
onMounted(async () => {
  if (!aksesPublikStore.hasAkses) {
    router.replace({ name: 'CariPengujian' })
    return
  }

  // Jika sudah pernah mengisi SKM, langsung bypass ke halaman download
  if (aksesPublikStore.skmFilled) {
    router.replace({ name: 'HasilUnduh' })
    return
  }

  // Cek ke server apakah SKM sudah pernah diisi di database
  try {
    const statusRes = await api.get('/api/public/pengujian/status')
    if (statusRes.data?.skm_diisi) {
      aksesPublikStore.setSkmFilled(true)
      clearDraft()
      router.replace({ name: 'HasilUnduh' })
      return
    }
  } catch {
    // abaikan jika gagal cek jaringan, biarkan user mengisi form
  }

  // Pulihkan draf sebelumnya jika ada
  restoreDraft()
})

// Unsur pertanyaan kuesioner SKM
const questions = [
  {
    key: 'u1',
    code: 'U1',
    title: 'Informasi Pelayanan',
    text: 'Informasi pelayanan tersedia melalui media elektronik maupun nonelektronik dengan jelas.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u2',
    code: 'U2',
    title: 'Kesesuaian Persyaratan',
    text: 'Kesesuaian persyaratan teknis dan administrasi dengan standar pelayanan yang ditetapkan.',
    options: [
      { score: 1, label: 'Sangat tidak sesuai', emoji: '😣' },
      { score: 2, label: 'Tidak sesuai', emoji: '😕' },
      { score: 3, label: 'Sesuai', emoji: '🙂' },
      { score: 4, label: 'Sangat sesuai', emoji: '😁' }
    ]
  },
  {
    key: 'u3',
    code: 'U3',
    title: 'Kejelasan Prosedur',
    text: 'Standar dan prosedur layanan pengujian lab diinformasikan secara transparan dan mudah dipahami.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u4',
    code: 'U4',
    title: 'Kemudahan Prosedur',
    text: 'Alur penyerahan sampel hingga pengambilan dokumen hasil pengujian mudah dijalankan.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u5',
    code: 'U5',
    title: 'Kecepatan Pelayanan',
    text: 'Pelayanan pengujian laboratorium diberikan secara tepat waktu dan responsif.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u6',
    code: 'U6',
    title: 'Ketepatan Waktu (SLA)',
    text: 'Waktu penyelesaian pengujian sesuai dengan standar estimasi yang telah dijanjikan.',
    options: [
      { score: 1, label: 'Sangat tidak sesuai', emoji: '😣' },
      { score: 2, label: 'Tidak sesuai', emoji: '😕' },
      { score: 3, label: 'Sesuai', emoji: '🙂' },
      { score: 4, label: 'Sangat sesuai', emoji: '😁' }
    ]
  },
  {
    key: 'u7',
    code: 'U7',
    title: 'Kesesuaian Tarif / Biaya',
    text: 'Kesesuaian biaya pelayanan pengujian dengan tarif resmi PNBP yang berlaku.',
    options: [
      { score: 1, label: 'Sangat tidak sesuai', emoji: '😣' },
      { score: 2, label: 'Tidak sesuai', emoji: '😕' },
      { score: 3, label: 'Sesuai', emoji: '🙂' },
      { score: 4, label: 'Sangat sesuai', emoji: '😁' }
    ]
  },
  {
    key: 'u8',
    code: 'U8',
    title: 'Kemudahan Transaksi',
    text: 'Sistem dan kanal pembayaran PNBP mudah diakses dan transparan.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u9',
    code: 'U9',
    title: 'Kesesuaian Hasil Pengujian',
    text: 'Produk Laporan Hasil Pengujian sesuai dengan parameter yang diajukan.',
    options: [
      { score: 1, label: 'Sangat tidak sesuai', emoji: '😣' },
      { score: 2, label: 'Tidak sesuai', emoji: '😕' },
      { score: 3, label: 'Sesuai', emoji: '🙂' },
      { score: 4, label: 'Sangat sesuai', emoji: '😁' }
    ]
  },
  {
    key: 'u10',
    code: 'U10',
    title: 'Kualitas & Keabsahan Dokumen',
    text: 'Dokumen hasil pengujian diterbitkan secara sah, rapi, dan memiliki tingkat akurasi tinggi.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u11',
    code: 'U11',
    title: 'Kompetensi Petugas Lab',
    text: 'Petugas dan analis laboratorium memiliki kompetensi teknis yang memadai.',
    options: [
      { score: 1, label: 'Sangat tidak kompeten', emoji: '😣' },
      { score: 2, label: 'Tidak kompeten', emoji: '😕' },
      { score: 3, label: 'Kompeten', emoji: '🙂' },
      { score: 4, label: 'Sangat kompeten', emoji: '😁' }
    ]
  },
  {
    key: 'u12',
    code: 'U12',
    title: 'Sikap & Keramahan Petugas',
    text: 'Petugas memberikan pelayanan dengan sopan, ramah, dan komunikatif.',
    options: [
      { score: 1, label: 'Sangat tidak sopan', emoji: '😣' },
      { score: 2, label: 'Tidak sopan', emoji: '😕' },
      { score: 3, label: 'Sopan & ramah', emoji: '🙂' },
      { score: 4, label: 'Sangat sopan & ramah', emoji: '😁' }
    ]
  },
  {
    key: 'u13',
    code: 'U13',
    title: 'Keadilan Pelayanan',
    text: 'Pelayanan diberikan secara adil tanpa diskriminasi kepada seluruh pemohon.',
    options: [
      { score: 1, label: 'Sangat tidak adil', emoji: '😣' },
      { score: 2, label: 'Tidak adil', emoji: '😕' },
      { score: 3, label: 'Adil', emoji: '🙂' },
      { score: 4, label: 'Sangat adil', emoji: '😁' }
    ]
  },
  {
    key: 'u14',
    code: 'U14',
    title: 'Integritas Bebas Pungli',
    text: 'Pelayanan diberikan secara bersih tanpa imbalan uang, barang, atau fasilitas di luar ketentuan resmi.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u15',
    code: 'U15',
    title: 'Akses Konsultasi & Pengaduan',
    text: 'Kanal pengaduan, konsultasi teknis, dan informasi lanjutan mudah diakses.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u16',
    code: 'U16',
    title: 'Kenyamanan Sarana & Prasarana',
    text: 'Sarana, prasarana, dan sistem aplikasi pengujian nyaman dan mudah digunakan.',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  }
]

// Penghitung Pertanyaan Terisi secara Real-Time
const answeredCount = computed(() => {
  return questions.filter(q => form[q.key] !== null).length
})

const completionPercentage = computed(() => {
  return Math.round((answeredCount.value / questions.length) * 100)
})

// Submit Form
const handleSubmit = async () => {
  // Validasi Biodata
  if (!form.nama) {
    errorMessage.value = 'Mohon isi nama lengkap responden.'
    return
  }
  if (!form.jenis_kelamin) {
    errorMessage.value = 'Mohon pilih jenis kelamin Anda.'
    return
  }
  if (!form.pendidikan) {
    errorMessage.value = 'Mohon pilih tingkat pendidikan terakhir Anda.'
    return
  }
  if (!form.usia) {
    errorMessage.value = 'Mohon pilih rentang usia Anda.'
    return
  }
  if (!form.pekerjaan) {
    errorMessage.value = 'Mohon pilih kategori pekerjaan Anda.'
    return
  }
  if (!form.disabilitas) {
    errorMessage.value = 'Mohon pilih status disabilitas Anda.'
    return
  }

  // Validasi jika ada unsur yang belum diisi
  for (const q of questions) {
    if (form[q.key] === null) {
      errorMessage.value = `Mohon berikan penilaian untuk unsur: ${q.code} - ${q.title}`
      const el = document.getElementById(q.key)
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      return
    }
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/api/public/skm', form)
    
    successMessage.value = response.data.message || 'Survei berhasil disimpan! Mengalihkan ke dokumen hasil uji...'
    aksesPublikStore.setSkmFilled(true)
    clearDraft()

    setTimeout(() => {
      router.push({ name: 'HasilUnduh' })
    }, 1200)
  } catch (error) {
    if (error.response?.status === 400 && error.response?.data?.message?.toLowerCase().includes('sudah mengisi')) {
      aksesPublikStore.setSkmFilled(true)
      clearDraft()
      successMessage.value = 'Survei SKM telah diisi sebelumnya. Mengalihkan ke dokumen hasil uji...'
      setTimeout(() => {
        router.replace({ name: 'HasilUnduh' })
      }, 1200)
    } else {
      errorMessage.value = error.response?.data?.message || 'Gagal mengirim survei SKM. Silakan coba kembali.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="skm-page">
    <!-- Top Decorative Accent Bar (Kementan Green & Gold) -->
    <div class="top-accent-bar"></div>

    <div class="skm-layout-container">
      
      <!-- HEADER INSTITUSI BRMP BIOGEN -->
      <header class="skm-hero-card">
        <!-- Logo & Identitas Resmi -->
        <div class="brand-badge">
          <img src="../../assets/logo-kementan.png" alt="Logo Kementerian Pertanian" class="kementan-logo" />
          <div class="brand-text">
            <span class="ministry-sub">KEMENTERIAN PERTANIAN REPUBLIK INDONESIA</span>
            <span class="agency-main">BRMP BIOGEN</span>
          </div>
        </div>

        <div class="hero-divider"></div>

        <!-- Step Indicator & Judul -->
        <div class="step-pill">
          <span class="step-tag">Langkah 3 dari 3</span>
          <span class="step-desc">Survei Kepuasan Masyarakat (SKM)</span>
        </div>

        <h1 class="skm-title">Kuesioner Kepuasan Layanan Laboratorium</h1>
        <p class="skm-subtitle">
          Sesuai standar mutu pelayanan publik, masukan objektif Anda menjadi dasar peningkatan kualitas pengujian kami.
        </p>

        <!-- Context Nomor Pengujian -->
        <div class="reference-badge">
          <span class="ref-label">Nomor Pengujian:</span>
          <strong class="ref-value">{{ aksesPublikStore.nomorPengujian }}</strong>
        </div>
      </header>

      <!-- STICKY REAL-TIME COMPLETION TRACKER -->
      <div class="sticky-progress-card">
        <div class="progress-info-row">
          <div class="progress-label-wrap">
            <ClipboardList :size="16" class="progress-ico" />
            <span class="progress-title">Progres Pengisian Kuesioner:</span>
            <strong class="progress-count">{{ answeredCount }} / {{ questions.length }} Terisi</strong>
          </div>
          <span class="progress-percentage-pill" :class="{ 'completed': answeredCount === questions.length }">
            {{ completionPercentage }}%
          </span>
        </div>
        <div class="progress-track">
          <div class="progress-fill" :style="{ width: completionPercentage + '%' }"></div>
        </div>
      </div>

      <!-- FORM SURVEI UTAMA -->
      <form @submit.prevent="handleSubmit" class="skm-form-flow">
        
        <!-- Alert Notifikasi -->
        <div v-if="errorMessage" class="alert-error">
          <AlertTriangle :size="18" class="alert-ico" />
          <span>{{ errorMessage }}</span>
        </div>

        <div v-if="successMessage" class="alert-success">
          <CheckCircle2 :size="18" class="alert-ico" />
          <span>{{ successMessage }}</span>
        </div>

        <!-- Notifikasi Draf Dipulihkan -->
        <div v-if="isDraftRestored" class="alert-draft">
          <div class="draft-text-wrap">
            <CheckCircle2 :size="18" class="draft-ico" />
            <span><strong>Draf Jawaban Dipulihkan:</strong> Jawaban survei yang sebelumnya Anda isi telah dipulihkan secara otomatis.</span>
          </div>
          <button type="button" @click="isDraftRestored = false" class="draft-close-btn" title="Tutup">&times;</button>
        </div>

        <!-- SECTION 1: BIODATA RESPONDEN -->
        <div class="content-card biodata-section">
          <div class="section-header">
            <div class="section-title-wrap">
              <div class="section-icon-badge">
                <User :size="18" />
              </div>
              <div>
                <h3 class="section-title">Profil Responden</h3>
                <p class="section-desc">Lengkapi profil demografis pemohon untuk keperluan agregasi statistik layanan.</p>
              </div>
            </div>
            <span class="status-badge required">Wajib Dilengkapi</span>
          </div>

          <div class="biodata-grid">
            <!-- Nama Lengkap -->
            <div class="field-item">
              <label for="bio-nama">Nama Lengkap</label>
              <input 
                type="text" 
                id="bio-nama" 
                v-model="form.nama" 
                placeholder="Nama Lengkap Anda" 
                required 
              />
            </div>

            <!-- Jenis Kelamin (Radio Pills) -->
            <div class="field-item">
              <label>Jenis Kelamin</label>
              <div class="segmented-radio-group">
                <label class="radio-pill" :class="{ selected: form.jenis_kelamin === 'Laki-laki' }">
                  <input type="radio" value="Laki-laki" v-model="form.jenis_kelamin" required />
                  <span>Laki-laki</span>
                </label>
                <label class="radio-pill" :class="{ selected: form.jenis_kelamin === 'Perempuan' }">
                  <input type="radio" value="Perempuan" v-model="form.jenis_kelamin" required />
                  <span>Perempuan</span>
                </label>
              </div>
            </div>

            <!-- Pendidikan -->
            <div class="field-item">
              <label for="bio-pendidikan">Pendidikan Terakhir</label>
              <select id="bio-pendidikan" v-model="form.pendidikan" required>
                <option value="" disabled>Pilih Jenjang Pendidikan</option>
                <option value="Tidak sekolah">Tidak sekolah</option>
                <option value="SD/Sederajat">SD / Sederajat</option>
                <option value="SMP/Sederajat">SMP / Sederajat</option>
                <option value="SMA/Sederajat">SMA / Sederajat</option>
                <option value="D1/D2/D3">D1 / D2 / D3</option>
                <option value="D4/S1">D4 / S1</option>
                <option value="S2">S2 (Magister)</option>
                <option value="S3">S3 (Doktor)</option>
              </select>
            </div>

            <!-- Usia -->
            <div class="field-item">
              <label for="bio-usia">Rentang Usia</label>
              <select id="bio-usia" v-model="form.usia" required>
                <option value="" disabled>Pilih Rentang Usia</option>
                <option value="< 17 tahun">&lt; 17 tahun</option>
                <option value="17-25 tahun">17 – 25 tahun</option>
                <option value="26-34 tahun">26 – 34 tahun</option>
                <option value="35-44 tahun">35 – 44 tahun</option>
                <option value="45-54 tahun">45 – 54 tahun</option>
                <option value="55-65 tahun">55 – 65 tahun</option>
                <option value=">65 tahun">&gt; 65 tahun</option>
              </select>
            </div>

            <!-- Pekerjaan -->
            <div class="field-item">
              <label for="bio-pekerjaan">Pekerjaan Utama</label>
              <select id="bio-pekerjaan" v-model="form.pekerjaan" required>
                <option value="" disabled>Pilih Pekerjaan</option>
                <option value="ASN">ASN / Pegawai Negeri</option>
                <option value="TNI">TNI</option>
                <option value="POLRI">POLRI</option>
                <option value="Swasta">Karyawan Swasta</option>
                <option value="Wirausaha">Wirausaha / Pengusaha</option>
                <option value="Ibu Rumah Tangga">Ibu Rumah Tangga</option>
                <option value="Pelajar/Mahasiswa">Pelajar / Mahasiswa</option>
                <option value="Petani/Nelayan">Petani / Nelayan</option>
                <option value="Pekerja Lepas/Freelance">Pekerja Lepas / Freelance</option>
                <option value="Pensiunan">Pensiunan</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>

            <!-- Status Disabilitas -->
            <div class="field-item">
              <label>Status Disabilitas</label>
              <div class="segmented-radio-group">
                <label class="radio-pill" :class="{ selected: form.disabilitas === 'Tidak' }">
                  <input type="radio" value="Tidak" v-model="form.disabilitas" required />
                  <span>Bukan Disabilitas</span>
                </label>
                <label class="radio-pill" :class="{ selected: form.disabilitas === 'Ya' }">
                  <input type="radio" value="Ya" v-model="form.disabilitas" required />
                  <span>Penyandang Disabilitas</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 2: DAFTAR 16 PERTANYAAN SKM (REFACTORING UI CARDS) -->
        <div 
          v-for="(q, idx) in questions" 
          :key="q.key" 
          :id="q.key" 
          class="content-card question-item-card"
          :class="{ 'card-answered': form[q.key] !== null }"
        >
          <!-- Header Pertanyaan -->
          <div class="question-top-bar">
            <div class="question-code-title">
              <span class="code-badge">{{ q.code }}</span>
              <h4 class="q-title">{{ q.title }}</h4>
            </div>

            <!-- Dynamic Answer Status Badge -->
            <span 
              class="answer-status-pill" 
              :class="{ 'is-filled': form[q.key] !== null }"
            >
              <span v-if="form[q.key] !== null" class="filled-content">
                <Check :size="12" /> Terisi
              </span>
              <span v-else>Wajib Diisi</span>
            </span>
          </div>

          <p class="question-statement">{{ q.text }}</p>

          <!-- 4 Rating Options (Tiles) -->
          <div class="rating-tiles-grid">
            <label 
              v-for="opt in q.options" 
              :key="opt.score" 
              class="rating-tile-box"
              :class="{ 'is-selected': form[q.key] === opt.score }"
            >
              <input 
                type="radio" 
                :name="q.key" 
                :value="opt.score" 
                v-model="form[q.key]" 
                required 
              />
              <span class="tile-emoji">{{ opt.emoji }}</span>
              <span class="tile-score">{{ opt.score }}</span>
              <span class="tile-label">{{ opt.label }}</span>
            </label>
          </div>
        </div>

        <!-- BOTTOM ACTION SUBMIT -->
        <div class="submit-tray-card">
          <div class="tray-info">
            <h4>Selesai Mengisi Survei?</h4>
            <p>Pastikan semua unsur telah dinilai untuk membuka akses unduhan Laporan Hasil Pengujian resmi Anda.</p>
          </div>

          <button 
            type="submit" 
            class="primary-submit-btn" 
            :disabled="isLoading || answeredCount < questions.length"
          >
            <span v-if="isLoading" class="btn-spinner-state">
              <span class="spinner-ring"></span>
              Menyimpan Data Survei...
            </span>
            <span v-else class="btn-normal-state">
              <span>Kirim Survei &amp; Buka Hasil Uji</span>
              <ArrowRight :size="18" />
            </span>
          </button>
        </div>

      </form>

      <!-- Footer Hak Cipta -->
      <footer class="skm-footer-credit">
        <p>&copy; 2026 Balai Besar Pengujian Standar Instrumen Bioteknologi dan Sumber Daya Genetik Pertanian (BRMP Biogen).</p>
        <p class="credit-sub">Kementerian Pertanian Republik Indonesia &bull; Evaluasi Pelayanan Publik</p>
      </footer>

    </div>
  </div>
</template>

<style scoped>
/* Page Layout */
.skm-page {
  min-height: 100vh;
  background-color: #f8fafc;
  background-image: 
    radial-gradient(circle at 10% 20%, rgba(27, 77, 62, 0.04) 0%, transparent 40%),
    radial-gradient(circle at 90% 80%, rgba(234, 179, 8, 0.05) 0%, transparent 40%),
    linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
  font-family: var(--font-sans);
  color: #1e293b;
  box-sizing: border-box;
  padding-bottom: 60px;
}

.top-accent-bar {
  height: 6px;
  width: 100%;
  background: linear-gradient(90deg, #1B4D3E 0%, #246B56 70%, #EAB308 100%);
}

.skm-layout-container {
  max-width: 780px;
  margin: 0 auto;
  padding: 36px 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* =========================================================
   HERO CARD INSTITUSI
   ========================================================= */
.skm-hero-card {
  background: #ffffff;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.05);
  padding: 36px 32px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.brand-badge {
  display: inline-flex;
  align-items: center;
  gap: 14px;
}

.kementan-logo {
  height: 54px;
  width: auto;
  object-fit: contain;
}

.brand-text {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.ministry-sub {
  font-size: 11px;
  font-weight: 750;
  letter-spacing: 0.8px;
  color: #64748b;
  text-transform: uppercase;
}

.agency-main {
  font-size: 19px;
  font-weight: 800;
  color: #1B4D3E;
  letter-spacing: 0.5px;
}

.hero-divider {
  width: 60px;
  height: 2px;
  background: #e2e8f0;
  margin: 20px 0;
}

.step-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(27, 77, 62, 0.08);
  border: 1px solid rgba(27, 77, 62, 0.2);
  padding: 4px 12px;
  border-radius: 999px;
  margin-bottom: 12px;
}

.step-tag {
  background: #1B4D3E;
  color: #ffffff;
  font-size: 11px;
  font-weight: 750;
  padding: 2px 7px;
  border-radius: 999px;
}

.step-desc {
  font-size: 12.5px;
  font-weight: 600;
  color: #1B4D3E;
}

.skm-title {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 8px 0;
  letter-spacing: -0.3px;
}

.skm-subtitle {
  font-size: 14px;
  color: #64748b;
  max-width: 580px;
  line-height: 1.55;
  margin: 0 0 16px 0;
}

.reference-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 6px 14px;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.ref-label {
  color: #64748b;
}

.ref-value {
  color: #1B4D3E;
  font-weight: 750;
}

/* =========================================================
   STICKY PROGRESS COMPLETION TRACKER
   ========================================================= */
.sticky-progress-card {
  position: sticky;
  top: 16px;
  z-index: 20;
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(14px);
  border-radius: 14px;
  border: 1px solid #cbd5e1;
  box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
  padding: 14px 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  transition: all 0.2s ease;
}

.progress-info-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.progress-label-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #334155;
}

.progress-ico {
  color: #1B4D3E;
}

.progress-title {
  font-weight: 600;
}

.progress-count {
  color: #0f172a;
  font-weight: 750;
}

.progress-percentage-pill {
  font-size: 12px;
  font-weight: 750;
  padding: 2px 8px;
  border-radius: 6px;
  background: #e2e8f0;
  color: #475569;
}

.progress-percentage-pill.completed {
  background: #dcfce7;
  color: #15803d;
}

.progress-track {
  height: 7px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #1B4D3E 0%, #22c55e 100%);
  border-radius: 999px;
  transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

/* =========================================================
   FORM CONTENT & CARDS
   ========================================================= */
.skm-form-flow {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Alerts */
.alert-error {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fef2f2;
  border: 1px solid #fee2e2;
  color: #991b1b;
  padding: 14px 18px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
}

.alert-success {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f0fdf4;
  border: 1px solid #dcfce7;
  color: #15803d;
  padding: 14px 18px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
}

.alert-draft {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-left: 4px solid #1B4D3E;
  color: #334155;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13.5px;
}

.draft-text-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.draft-ico {
  color: #1B4D3E;
  flex-shrink: 0;
}

.draft-close-btn {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
  padding: 0 4px;
}

.draft-close-btn:hover {
  color: #475569;
}

.alert-ico {
  flex-shrink: 0;
}

/* Generic Content Card */
.content-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 24px 28px;
  box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.03);
  display: flex;
  flex-direction: column;
  gap: 16px;
  transition: all 0.2s ease;
}

/* Section Header */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 14px;
}

.section-title-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-icon-badge {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(27, 77, 62, 0.1);
  color: #1B4D3E;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.section-title {
  margin: 0;
  font-size: 16px;
  font-weight: 750;
  color: #0f172a;
}

.section-desc {
  margin: 2px 0 0 0;
  font-size: 13px;
  color: #64748b;
}

.status-badge {
  font-size: 11.5px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 6px;
}

.status-badge.required {
  background: #fef2f2;
  color: #b91c1c;
}

/* Biodata Grid */
.biodata-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

.field-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field-item label {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.field-item input[type="text"],
.field-item select {
  padding: 11px 14px;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: 14px;
  color: #0f172a;
  background: #f8fafc;
  outline: none;
  transition: all 0.2s ease;
}

.field-item input[type="text"]:focus,
.field-item select:focus {
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.12);
}

/* Segmented Radio Group */
.segmented-radio-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.radio-pill {
  border: 1.5px solid #cbd5e1;
  background: #f8fafc;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  text-align: center;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  user-select: none;
}

.radio-pill input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.radio-pill:hover {
  background: #f1f5f9;
}

.radio-pill.selected {
  border-color: #1B4D3E;
  background: rgba(27, 77, 62, 0.08);
  color: #1B4D3E;
  font-weight: 750;
}

/* =========================================================
   QUESTION CARDS (16 ITEMS)
   ========================================================= */
.question-item-card {
  border-left: 4px solid #cbd5e1;
}

.question-item-card.card-answered {
  border-left-color: #1B4D3E;
  box-shadow: 0 4px 16px -2px rgba(27, 77, 62, 0.06);
}

.question-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.question-code-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.code-badge {
  background: #f1f5f9;
  color: #475569;
  font-size: 11.5px;
  font-weight: 800;
  padding: 2px 7px;
  border-radius: 6px;
  letter-spacing: 0.5px;
}

.card-answered .code-badge {
  background: rgba(27, 77, 62, 0.1);
  color: #1B4D3E;
}

.q-title {
  margin: 0;
  font-size: 15px;
  font-weight: 750;
  color: #0f172a;
}

.answer-status-pill {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #94a3b8;
}

.answer-status-pill.is-filled {
  background: #dcfce7;
  color: #15803d;
}

.filled-content {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.question-statement {
  margin: 0;
  font-size: 14px;
  color: #475569;
  line-height: 1.55;
}

/* 4 Rating Option Tiles */
.rating-tiles-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.rating-tile-box {
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  background: #ffffff;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  user-select: none;
  position: relative;
}

.rating-tile-box input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.tile-emoji {
  font-size: 22px;
  filter: grayscale(80%);
  opacity: 0.75;
  transition: transform 0.2s ease, filter 0.2s ease;
}

.tile-score {
  font-size: 16px;
  font-weight: 800;
  color: #64748b;
  transition: color 0.2s ease;
}

.tile-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-align: center;
  line-height: 1.3;
}

.rating-tile-box:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
  transform: translateY(-1px);
}

.rating-tile-box:hover .tile-emoji {
  filter: grayscale(0%);
  opacity: 1;
}

.rating-tile-box.is-selected {
  border-color: #1B4D3E;
  background: rgba(27, 77, 62, 0.08);
  box-shadow: 0 4px 12px rgba(27, 77, 62, 0.15);
  transform: translateY(-2px);
}

.rating-tile-box.is-selected .tile-emoji {
  filter: grayscale(0%);
  opacity: 1;
  transform: scale(1.15);
}

.rating-tile-box.is-selected .tile-score {
  color: #1B4D3E;
}

.rating-tile-box.is-selected .tile-label {
  color: #13392E;
  font-weight: 700;
}

/* =========================================================
   SUBMIT TRAY
   ========================================================= */
.submit-tray-card {
  background: #ffffff;
  border-radius: 18px;
  border: 1px solid #cbd5e1;
  box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.07);
  padding: 24px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.tray-info h4 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 750;
  color: #0f172a;
}

.tray-info p {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.45;
}

.primary-submit-btn {
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  padding: 14px 24px;
  border-radius: 12px;
  font-size: 14.5px;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  box-shadow: 0 4px 14px rgba(27, 77, 62, 0.25);
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.primary-submit-btn:hover:not(:disabled) {
  background: #13392E;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(27, 77, 62, 0.35);
}

.primary-submit-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-normal-state {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-spinner-state {
  display: flex;
  align-items: center;
  gap: 8px;
}

.spinner-ring {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Footer Credit */
.skm-footer-credit {
  text-align: center;
  padding-top: 16px;
  font-size: 12.5px;
  color: #64748b;
}

.skm-footer-credit p {
  margin: 0;
}

.credit-sub {
  margin-top: 4px !important;
  font-size: 11.5px;
  color: #94a3b8;
}

/* =========================================================
   RESPONSIVE DESIGN (MOBILE & TABLET)
   ========================================================= */
@media (max-width: 768px) {
  .skm-layout-container {
    padding: 24px 14px;
  }

  .skm-hero-card {
    padding: 24px 18px;
  }

  .skm-title {
    font-size: 20px;
  }

  .sticky-progress-card {
    top: 8px;
    padding: 10px 14px;
    border-radius: 12px;
  }

  .progress-title {
    font-size: 12px;
  }

  .content-card {
    padding: 18px 16px;
  }

  .biodata-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .rating-tiles-grid {
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }

  .submit-tray-card {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .primary-submit-btn {
    width: 100%;
  }
}
</style>
