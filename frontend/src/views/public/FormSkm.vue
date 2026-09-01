<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useAksesPublikStore } from '../../stores/aksesPublik'
import { ClipboardList, AlertTriangle, CheckCircle2, Send } from '@lucide/vue'

const router = useRouter()
const aksesPublikStore = useAksesPublikStore()

// State
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

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

// Proteksi jika token tidak ada
onMounted(async () => {
  if (!aksesPublikStore.hasAkses) {
    router.replace({ name: 'CariPengujian' })
    return
  }

  // Jika sudah pernah mengisi SKM, langsung bypass ke halaman download
  if (aksesPublikStore.skmFilled) {
    router.replace({ name: 'HasilUnduh' })
  }
})

// Unsur pertanyaan kuesioner SKM
const questions = [
  {
    key: 'u1',
    title: 'U1 - Informasi Pelayanan',
    text: 'Informasi pelayanan tersedia melalui media elektronik maupun nonelektronik',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u2',
    title: 'U2 - Kesesuaian Persyaratan',
    text: 'Kesesuaian persyaratan dengan standar pelayanan/informasi yang diberikan',
    options: [
      { score: 1, label: 'Sangat tidak sesuai', emoji: '😣' },
      { score: 2, label: 'Tidak sesuai', emoji: '😕' },
      { score: 3, label: 'Sesuai', emoji: '🙂' },
      { score: 4, label: 'Sangat sesuai', emoji: '😁' }
    ]
  },
  {
    key: 'u3',
    title: 'U3 - Kejelasan Prosedur',
    text: 'Standar dan prosedur layanan diinformasikan dengan jelas',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u4',
    title: 'U4 - Kemudahan Prosedur',
    text: 'Prosedur/Alur layanan mudah dipahami dan dilakukan',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u5',
    title: 'U5 - Integritas Prosedur',
    text: 'Layanan diberikan sesuai prosedur tanpa kecurangan',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u6',
    title: 'U6 - Jangka Waktu Layanan',
    text: 'Jangka waktu layanan sesuai dengan standar pelayanan/ yang diinformasikan',
    options: [
      { score: 1, label: 'Sangat tidak sesuai', emoji: '😣' },
      { score: 2, label: 'Tidak sesuai', emoji: '😕' },
      { score: 3, label: 'Sesuai', emoji: '🙂' },
      { score: 4, label: 'Sangat sesuai', emoji: '😁' }
    ]
  },
  {
    key: 'u7',
    title: 'U7 - Kesesuaian Biaya',
    text: 'Biaya layanan sesuai dengan standar pelayanan/ yang diinformasikan',
    options: [
      { score: 1, label: 'Sangat tidak sesuai', emoji: '😣' },
      { score: 2, label: 'Tidak sesuai', emoji: '😕' },
      { score: 3, label: 'Sesuai', emoji: '🙂' },
      { score: 4, label: 'Sangat sesuai', emoji: '😁' }
    ]
  },
  {
    key: 'u8',
    title: 'U8 - Bebas Pungli',
    text: 'Tidak ada pungutan liar (pungli) dalam pelayanan',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u9',
    title: 'U9 - Bebas Percaloan',
    text: 'Tidak ada percaloan/perantara tidak resmi dalam pelayanan',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u10',
    title: 'U10 - Kesesuaian Hasil Layanan',
    text: 'Produk layanan yang diterima sesuai dengan standar pelayanan / yang dipublikasikan',
    options: [
      { score: 1, label: 'Sangat tidak sesuai', emoji: '😣' },
      { score: 2, label: 'Tidak sesuai', emoji: '😕' },
      { score: 3, label: 'Sesuai', emoji: '🙂' },
      { score: 4, label: 'Sangat sesuai', emoji: '😁' }
    ]
  },
  {
    key: 'u11',
    title: 'U11 - Respon Petugas',
    text: 'Petugas merespon kebutuhan dengan cepat',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u12',
    title: 'U12 - Keramahan Petugas',
    text: 'Petugas melayani saya dengan ramah',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u13',
    title: 'U13 - Keadilan Pelayanan',
    text: 'Seluruh pengguna layanan dilayani secara adil tanpa diskriminasi',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u14',
    title: 'U14 - Integritas Petugas',
    text: 'Pelayanan diberikan tanpa imbalan uang, barang, atau fasilitas di luar aturan',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u15',
    title: 'U15 - Akses Konsultasi/Pengaduan',
    text: 'Layanan konsultasi dan pengaduan mudah diakses',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  },
  {
    key: 'u16',
    title: 'U16 - Kenyamanan Sarpras',
    text: 'Sarana prasarana nyaman dan mudah digunakan',
    options: [
      { score: 1, label: 'Sangat tidak setuju', emoji: '😣' },
      { score: 2, label: 'Tidak setuju', emoji: '😕' },
      { score: 3, label: 'Setuju', emoji: '🙂' },
      { score: 4, label: 'Sangat setuju', emoji: '😁' }
    ]
  }
]

// Submit Form
const handleSubmit = async () => {
  // Validasi Biodata
  if (!form.nama) {
    errorMessage.value = 'Mohon isi nama lengkap Anda.'
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

  // Validasi manual jika ada yang belum terpilih
  for (const q of questions) {
    if (form[q.key] === null) {
      errorMessage.value = `Mohon berikan penilaian untuk unsur: ${q.title}`
      // Scroll ke element
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
    
    successMessage.value = response.data.message
    aksesPublikStore.setSkmFilled(true)

    setTimeout(() => {
      router.push({ name: 'HasilUnduh' })
    }, 1500)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengirim survei SKM.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="skm-container">
    <div class="skm-wrapper">
      <div class="skm-header">
        <span class="header-icon" style="display: inline-block; vertical-align: middle;"><ClipboardList :size="32" /></span>
        <h1>Survei Kepuasan Masyarakat</h1>
        <p class="text-muted">Nomor Pengujian: <strong>{{ aksesPublikStore.nomorPengujian }}</strong></p>
        <div class="header-divider"></div>
        <p class="header-desc">
          Sesuai dengan <strong>standar pelayanan BRMP Biogen</strong>, mohon lengkapi biodata dan berikan penilaian objektif Anda terhadap 16 unsur pelayanan pengujian kami. Penilaian Anda sangat berharga bagi peningkatan mutu pelayanan kami.
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="skm-form">
        <div v-if="errorMessage" class="alert alert-danger sticky-alert">
          <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success">
          <CheckCircle2 :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ successMessage }}
        </div>

        <!-- Card Biodata Diri -->
        <div class="card biodata-card">
          <div class="card-header-biodata">
            <h3>Biodata Responden</h3>
            <span class="badge-status-q filled">Wajib Diisi</span>
          </div>
          <p class="question-text">Mohon lengkapi data profil responden di bawah ini sebelum mengisi kuesioner.</p>
          
          <div class="biodata-grid">
            <div class="form-group-biodata">
              <label for="bio-nama">Nama Lengkap</label>
              <input 
                type="text" 
                id="bio-nama" 
                v-model="form.nama" 
                placeholder="Nama Lengkap Anda" 
                required 
              />
            </div>

            <div class="form-group-biodata">
              <label>Jenis Kelamin</label>
              <div class="radio-group-horizontal">
                <label class="radio-inline">
                  <input type="radio" value="Laki-laki" v-model="form.jenis_kelamin" required />
                  Laki-laki
                </label>
                <label class="radio-inline">
                  <input type="radio" value="Perempuan" v-model="form.jenis_kelamin" required />
                  Perempuan
                </label>
              </div>
            </div>

            <div class="form-group-biodata">
              <label for="bio-pendidikan">Pendidikan Terakhir</label>
              <select id="bio-pendidikan" v-model="form.pendidikan" required>
                <option value="" disabled>Pilih Pendidikan</option>
                <option value="Tidak sekolah">Tidak sekolah</option>
                <option value="SD/Sederajat">SD/Sederajat</option>
                <option value="SMP/Sederajat">SMP/Sederajat</option>
                <option value="SMA/Sederajat">SMA/Sederajat</option>
                <option value="D1/D2/D3">D1/D2/D3</option>
                <option value="D4/S1">D4/S1</option>
                <option value="S2">S2</option>
                <option value="S3">S3</option>
              </select>
            </div>

            <div class="form-group-biodata">
              <label for="bio-usia">Usia</label>
              <select id="bio-usia" v-model="form.usia" required>
                <option value="" disabled>Pilih Rentang Usia</option>
                <option value="< 17 tahun">&lt; 17 tahun</option>
                <option value="17-25 tahun">17-25 tahun</option>
                <option value="26-34 tahun">26-34 tahun</option>
                <option value="35-44 tahun">35-44 tahun</option>
                <option value="45-54 tahun">45-54 tahun</option>
                <option value="55-65 tahun">55-65 tahun</option>
                <option value=">65 tahun">&gt; 65 tahun</option>
              </select>
            </div>

            <div class="form-group-biodata">
              <label for="bio-pekerjaan">Pekerjaan Utama</label>
              <select id="bio-pekerjaan" v-model="form.pekerjaan" required>
                <option value="" disabled>Pilih Pekerjaan</option>
                <option value="ASN">ASN</option>
                <option value="TNI">TNI</option>
                <option value="POLRI">POLRI</option>
                <option value="Swasta">Swasta</option>
                <option value="Wirausaha">Wirausaha</option>
                <option value="Ibu Rumah Tangga">Ibu Rumah Tangga</option>
                <option value="Pelajar/Mahasiswa">Pelajar/Mahasiswa</option>
                <option value="Petani/Nelayan">Petani/Nelayan</option>
                <option value="Pekerja Lepas/Freelance">Pekerja Lepas/Freelance</option>
                <option value="Pensiunan">Pensiunan</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>

            <div class="form-group-biodata">
              <label>Status Disabilitas</label>
              <div class="radio-group-horizontal">
                <label class="radio-inline">
                  <input type="radio" value="Tidak" v-model="form.disabilitas" required />
                  Bukan Disabilitas
                </label>
                <label class="radio-inline">
                  <input type="radio" value="Ya" v-model="form.disabilitas" required />
                  Penyandang Disabilitas/Pendamping
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Cards Pertanyaan -->
        <div v-for="q in questions" :key="q.key" :id="q.key" class="card question-card">
          <div class="question-header">
            <h3>{{ q.title }}</h3>
            <span class="badge-status-q" :class="{ filled: form[q.key] !== null }">
              {{ form[q.key] !== null ? 'Sudah Diisi' : 'Wajib Diisi' }}
            </span>
          </div>
          <p class="question-text">{{ q.text }}</p>

          <div class="rating-options">
            <label 
              v-for="opt in q.options" 
              :key="opt.score" 
              class="rating-label"
              :class="{ active: form[q.key] === opt.score }"
            >
              <input 
                type="radio" 
                :name="q.key" 
                :value="opt.score" 
                v-model="form[q.key]" 
                required 
              />
              <span class="option-emoji">{{ opt.emoji }}</span>
              <span class="option-score">{{ opt.score }}</span>
              <span class="option-label">{{ opt.label }}</span>
            </label>
          </div>
        </div>


        <button type="submit" class="btn-submit" :disabled="isLoading">
          <span v-if="isLoading">Menyimpan Survei...</span>
          <span v-else><Send :size="16" style="margin-right: 6px; display: inline-block; vertical-align: middle;" /> Kirim Survei &amp; Ambil Hasil Uji</span>
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.skm-container {
  min-height: 100vh;
  background: #f1f5f9;
  padding: 40px 20px;
}

.skm-wrapper {
  max-width: 680px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.skm-header {
  background: #ffffff;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  text-align: center;
  border: 1px solid #e2e8f0;
}

.header-icon {
  font-size: 48px;
  display: inline-block;
  margin-bottom: 12px;
}

.skm-header h1 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
}

.text-muted {
  margin: 0;
  font-size: 14px;
  color: #64748b;
}

.text-muted strong {
  color: #1B4D3E;
}

.header-divider {
  height: 2px;
  background: #f1f5f9;
  margin: 20px auto;
  width: 80px;
}

.header-desc {
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
  margin: 0;
}

.skm-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.sticky-alert {
  position: sticky;
  top: 20px;
  z-index: 10;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.alert {
  padding: 14px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
}

.alert-danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.alert-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
}

.card {
  background: #ffffff;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 16px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
}

.question-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.question-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.badge-status-q {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 30px;
  background: #fef2f2;
  color: #991b1b;
}

.badge-status-q.filled {
  background: #f0fdf4;
  color: #166534;
}

.question-text {
  margin: 0;
  font-size: 14px;
  color: #475569;
  line-height: 1.6;
}

.rating-options {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

@media (max-width: 550px) {
  .rating-options {
    grid-template-columns: 1fr;
  }
}

.rating-label {
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  background: #ffffff;
  transition: all 0.2s ease;
  user-select: none;
}

.rating-label input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.option-emoji {
  font-size: 24px;
  filter: grayscale(100%);
  opacity: 0.7;
  transition: all 0.2s ease;
}

.option-score {
  font-size: 18px;
  font-weight: 800;
  color: #64748b;
  transition: all 0.2s ease;
}

.option-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-align: center;
}

.rating-label:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.rating-label:hover .option-emoji {
  filter: grayscale(0%);
  opacity: 1;
}

/* Active State styles */
.rating-label.active {
  border-color: #1B4D3E;
  background: rgba(27, 77, 62, 0.08);
  box-shadow: 0 4px 12px rgba(27, 77, 62, 0.15);
}

.rating-label.active .option-emoji {
  filter: grayscale(0%);
  opacity: 1;
  transform: scale(1.15);
}

.rating-label.active .option-score {
  color: #1B4D3E;
}

.rating-label.active .option-label {
  color: #13382D;
}

.comment-card h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  position: relative;
}

textarea {
  width: 100%;
  padding: 14px;
  border: 1.5px solid #cbd5e1;
  border-radius: 12px;
  font-size: 14px;
  color: #1e293b;
  background: #f8fafc;
  outline: none;
  resize: vertical;
  transition: all 0.2s ease;
}

textarea:focus {
  background: #ffffff;
  border-color: #1B4D3E;
  box-shadow: 0 0 0 4px rgba(27, 77, 62, 0.15);
}

.char-counter {
  align-self: flex-end;
  font-size: 11px;
  color: #94a3b8;
}

.btn-submit {
  background: #10b981;
  color: white;
  border: none;
  padding: 16px;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
  transition: all 0.2s ease;
  text-align: center;
}

.btn-submit:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1.5px);
}

.btn-submit:disabled {
  background: #94a3b8;
  box-shadow: none;
  cursor: not-allowed;
}

/* Biodata Card Styles */
.biodata-card {
  padding: 24px;
  border-radius: 16px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

.card-header-biodata {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.card-header-biodata h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
}

.biodata-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-top: 20px;
}

@media (max-width: 640px) {
  .biodata-grid {
    grid-template-columns: 1fr;
  }
}

.form-group-biodata {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group-biodata label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  text-align: left;
}

.form-group-biodata input[type="text"],
.form-group-biodata select {
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 14px;
  color: #0f172a;
  outline: none;
  background: #ffffff;
  transition: all 0.2s;
}

.form-group-biodata input[type="text"]:focus,
.form-group-biodata select:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
}

.radio-group-horizontal {
  display: flex;
  gap: 24px;
  align-items: center;
  height: 42px;
}

.radio-inline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #334155;
  cursor: pointer;
}

.radio-inline input[type="radio"] {
  width: 16px;
  height: 16px;
  accent-color: var(--accent);
  cursor: pointer;
}

@media (max-width: 640px) {
  .skm-container {
    padding: 12px 12px 32px 12px;
  }

  .skm-card-header, .card {
    padding: 20px 16px;
  }

  .header-title h1 {
    font-size: 20px;
  }

  .options-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .biodata-grid-auto {
    grid-template-columns: 1fr;
  }

  .radio-group-horizontal {
    height: auto;
    flex-wrap: wrap;
    gap: 16px;
  }
}
</style>
