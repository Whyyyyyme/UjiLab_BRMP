<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { Plus, AlertTriangle, CheckCircle2, Loader2, Edit, Trash2, Key, KeyRound, Shield, FlaskConical, X, Save } from '@lucide/vue'

const authStore = useAuthStore()

// State
const petugasList = ref([])
const isLoading = ref(true)
const errorMessage = ref('')
const successMessage = ref('')
const showResetConfirm = ref(false)
const selectedPetugasToReset = ref(null)
const showDeleteConfirm = ref(false)
const selectedPetugasToDelete = ref(null)

// Modal state
const isModalOpen = ref(false)
const modalMode = ref('add') // 'add' atau 'edit'
const selectedPetugasId = ref(null)

const form = ref({
  nama: '',
  username: '',
  email: '',
  role: 'petugas_lab'
})

// Fetch all petugas accounts
const fetchPetugas = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await api.get('/api/admin/petugas')
    petugasList.value = response.data
  } catch (error) {
    errorMessage.value = 'Gagal memuat daftar petugas.'
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

// Open modal for add
const openAddModal = () => {
  modalMode.value = 'add'
  selectedPetugasId.value = null
  form.value = {
    nama: '',
    username: '',
    email: '',
    role: 'petugas_lab'
  }
  isModalOpen.value = true
}

// Open modal for edit
const openEditModal = (petugas) => {
  modalMode.value = 'edit'
  selectedPetugasId.value = petugas.id
  form.value = {
    nama: petugas.nama,
    username: petugas.username,
    email: petugas.email,
    role: petugas.role
  }
  isModalOpen.value = true
}

// Close Modal
const closeModal = () => {
  isModalOpen.value = false
}

// Submit Form (Add/Edit)
const handleSubmit = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  try {
    if (modalMode.value === 'add') {
      await api.post('/api/admin/petugas', form.value)
      successMessage.value = 'Akun petugas baru berhasil dibuat.'
    } else {
      await api.put(`/api/admin/petugas/${selectedPetugasId.value}`, form.value)
      successMessage.value = 'Data petugas berhasil diperbarui.'
    }
    closeModal()
    fetchPetugas()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal memproses data petugas.'
  }
}

// Reset Password Petugas
const handleResetPassword = (petugas) => {
  selectedPetugasToReset.value = petugas
  showResetConfirm.value = true
}

const confirmResetPassword = async () => {
  if (!selectedPetugasToReset.value) return
  const petugas = selectedPetugasToReset.value
  showResetConfirm.value = false
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await api.post(`/api/admin/petugas/${petugas.id}/reset-password`)
    successMessage.value = response.data.message
    fetchPetugas()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mereset password.'
  } finally {
    selectedPetugasToReset.value = null
  }
}

// Delete Petugas
const handleDelete = (petugas) => {
  if (petugas.id === authStore.user?.id) {
    errorMessage.value = 'Anda tidak dapat menghapus akun Anda sendiri.'
    setTimeout(() => { errorMessage.value = '' }, 4000)
    return
  }
  selectedPetugasToDelete.value = petugas
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  if (!selectedPetugasToDelete.value) return
  const petugas = selectedPetugasToDelete.value
  showDeleteConfirm.value = false
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await api.delete(`/api/admin/petugas/${petugas.id}`)
    successMessage.value = response.data.message
    fetchPetugas()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menghapus petugas.'
  } finally {
    selectedPetugasToDelete.value = null
  }
}

onMounted(() => {
  fetchPetugas()
})
</script>

<template>
  <div class="petugas-mgmt-view">
    <div class="header-section">
      <div class="header-info">
        <h2>Manajemen Akun Petugas</h2>
        <p class="text-muted">Kelola akun administrator dan petugas laboratorium BRMP Biogen.</p>
      </div>
      <button @click="openAddModal" class="btn-primary flex-icon-center">
        <Plus :size="16" /> Tambah Petugas Baru
      </button>
    </div>

    <!-- Feedback Alerts -->
    <div v-if="errorMessage" class="alert alert-danger">
      <AlertTriangle :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ errorMessage }}
    </div>

    <div v-if="successMessage" class="alert alert-success">
      <CheckCircle2 :size="16" style="margin-right: 8px; display: inline-block; vertical-align: middle;" /> {{ successMessage }}
    </div>

    <!-- Spinner Loader -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner"><Loader2 :size="32" /></div>
      <p>Memuat daftar akun petugas...</p>
    </div>

    <!-- Petugas Table List -->
    <div v-else class="card list-card">
      <div class="table-container">
        <table class="petugas-table">
          <thead>
            <tr>
              <th>Nama Lengkap</th>
              <th>Username</th>
              <th>Email</th>
              <th>Hak Akses / Role</th>
              <th>Status Password</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="petugasList.length === 0">
              <td colspan="6" class="empty-row">Belum ada akun petugas terdaftar.</td>
            </tr>
            <tr v-for="p in petugasList" :key="p.id">
              <td class="text-bold">{{ p.nama }} <span v-if="p.id === authStore.user?.id" class="badge-self">(Anda)</span></td>
              <td class="font-mono">{{ p.username }}</td>
              <td>{{ p.email }}</td>
              <td>
                <span class="badge-role flex-icon-center" :class="p.role" style="gap: 4px; display: inline-flex; align-items: center;">
                  <Shield v-if="p.role === 'admin'" :size="12" />
                  <FlaskConical v-else :size="12" />
                  {{ p.role === 'admin' ? 'Administrator' : 'Petugas Lab' }}
                </span>
              </td>
              <td>
                <span class="badge-status-p" :class="{ warning: p.wajib_ganti_password }">
                  <span v-if="p.wajib_ganti_password" class="badge-status wajib-ganti flex-icon-center" style="gap: 4px; display: inline-flex;">
                    <Key :size="12" /> Wajib Ubah
                  </span>
                  <span v-else class="badge-status aktif">
                    Aktif
                  </span>
                </span>
              </td>
              <td>
                <div class="actions-group">
                  <button @click="openEditModal(p)" class="btn-action btn-edit flex-icon-center" title="Edit Akun"><Edit :size="12" /> Edit</button>
                  <button @click="handleResetPassword(p)" class="btn-action btn-reset flex-icon-center" title="Reset Password"><KeyRound :size="12" /> Reset</button>
                  <button 
                    @click="handleDelete(p)" 
                    class="btn-action btn-delete flex-icon-center" 
                    :disabled="p.id === authStore.user?.id"
                    title="Hapus Akun"
                  >
                    <Trash2 :size="12" /> Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modals (Add / Edit Form) -->
    <div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3>{{ modalMode === 'add' ? 'Tambah Akun Petugas Baru' : 'Edit Detail Petugas' }}</h3>
          <button @click="closeModal" class="btn-close-modal" aria-label="Tutup Modal"><X :size="18" /></button>
        </div>
        
        <form @submit.prevent="handleSubmit" class="modal-form">
          <div class="form-group">
            <label>Nama Lengkap</label>
            <input 
              type="text" 
              placeholder="Masukkan nama lengkap..." 
              v-model="form.nama" 
              required 
            />
          </div>

          <div class="form-group">
            <label>Username</label>
            <input 
              type="text" 
              placeholder="Masukkan username..." 
              v-model="form.username" 
              required 
              :disabled="modalMode === 'edit'"
            />
          </div>

          <div class="form-group">
            <label>Alamat Email</label>
            <input 
              type="email" 
              placeholder="Masukkan email resmi..." 
              v-model="form.email" 
              required 
            />
          </div>

          <div class="form-group">
            <label>Hak Akses / Role</label>
            <select v-model="form.role" required>
              <option value="petugas_lab">Petugas Lab</option>
              <option value="admin">Administrator</option>
            </select>
          </div>

          <div v-if="modalMode === 'add'" class="password-notice">
            <AlertTriangle :size="14" style="display: inline-block; vertical-align: middle; margin-right: 4px;" /> <strong>Catatan:</strong> Akun baru akan didaftarkan dengan password default: <strong>Password123!</strong>. Petugas diwajibkan mengganti password saat pertama kali login.
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary flex-icon-center">
              <Save :size="16" /> {{ modalMode === 'add' ? 'Simpan Akun' : 'Perbarui Data' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Custom Reset Password Confirmation Modal -->
    <div v-if="showResetConfirm" class="modal-backdrop-confirm">
      <div class="confirm-card">
        <div class="confirm-header">
          <AlertTriangle :size="24" class="text-warning" />
          <h3>Reset Password Petugas</h3>
        </div>
        <div class="confirm-body">
          Apakah Anda yakin ingin mereset password petugas <strong>{{ selectedPetugasToReset?.nama }}</strong> ke password default <strong>Password123!</strong>? Petugas akan dipaksa mengubah password pada login berikutnya.
        </div>
        <div class="confirm-footer">
          <button @click="showResetConfirm = false" class="btn-cancel">Batal</button>
          <button @click="confirmResetPassword" class="btn-confirm-warning">Reset Password</button>
        </div>
      </div>
    </div>

    <!-- Custom Delete Account Confirmation Modal -->
    <div v-if="showDeleteConfirm" class="modal-backdrop-confirm">
      <div class="confirm-card">
        <div class="confirm-header">
          <AlertTriangle :size="24" class="text-danger" />
          <h3>Hapus Akun Petugas</h3>
        </div>
        <div class="confirm-body">
          Apakah Anda yakin ingin menghapus akun petugas <strong>{{ selectedPetugasToDelete?.nama }}</strong> secara permanen? Tindakan ini tidak dapat dibatalkan.
        </div>
        <div class="confirm-footer">
          <button @click="showDeleteConfirm = false" class="btn-cancel">Batal</button>
          <button @click="confirmDelete" class="btn-confirm-delete">Hapus Akun</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.petugas-mgmt-view {
  display: flex;
  flex-direction: column;
  gap: 20px;
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
  white-space: nowrap;
}

.btn-primary:hover {
  background: #13382D;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border: 1.5px solid #cbd5e1;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #e2e8f0;
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

.alert-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
}

.card {
  background: #ffffff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01), 0 10px 15px -3px rgba(0, 0, 0, 0.02);
  border: 1px solid #f1f5f9;
}

.table-container {
  overflow-x: auto;
}

.petugas-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.petugas-table th {
  background: #f8fafc;
  padding: 12px 16px;
  font-weight: 600;
  color: #475569;
  border-bottom: 1.5px solid #e2e8f0;
}

.petugas-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.petugas-table tbody tr:hover {
  background: #f8fafc;
}

.empty-row {
  text-align: center;
  color: #94a3b8;
  padding: 40px !important;
}

.text-bold {
  font-weight: 600;
  color: #0f172a;
}

.font-mono {
  font-family: monospace;
}

.badge-self {
  font-size: 11px;
  background: #f1f5f9;
  color: #64748b;
  padding: 2px 6px;
  border-radius: 4px;
  margin-left: 4px;
}

.badge-role {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
}

.badge-role.admin {
  background: rgba(27, 77, 62, 0.08);
  color: #1B4D3E;
}

.badge-role.petugas_lab {
  background: #f5f3ff;
  color: #5b21b6;
}

.badge-status-p {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
}

.badge-status-p.warning {
  background: #fffbeb;
  color: #92400e;
}

.badge-status-p:not(.warning) {
  background: #ecfdf5;
  color: #065f46;
}

.actions-group {
  display: flex;
  gap: 6px;
}

.btn-action {
  border: none;
  background: none;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.btn-edit {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #475569;
}

.btn-edit:hover {
  background: #cbd5e1;
}

.btn-reset {
  background: #fffbeb;
  border-color: #fde68a;
  color: #92400e;
}

.btn-reset:hover {
  background: #fef3c7;
}

.btn-delete {
  background: #fef2f2;
  border-color: #fecaca;
  color: #b91c1c;
}

.btn-delete:hover:not(:disabled) {
  background: #fecaca;
}

.btn-delete:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modals Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-card {
  background: #ffffff;
  border-radius: 20px;
  width: 100%;
  max-width: 460px;
  padding: 32px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: scaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scaleUp {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 16px;
  margin-bottom: 20px;
}

.modal-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
}

.btn-close-modal {
  background: none;
  border: none;
  font-size: 18px;
  color: #94a3b8;
  cursor: pointer;
  transition: color 0.2s ease;
}

.btn-close-modal:hover {
  color: #ef4444;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 11px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-group input, .form-group select {
  padding: 10px 14px;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  background: #ffffff;
}

.form-group input:focus, .form-group select:focus {
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.15);
}

.password-notice {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
  padding: 12px;
  border-radius: 8px;
  font-size: 11px;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 12px;
}

/* Spinner Loader */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  color: #64748b;
  gap: 12px;
}

.spinner {
  font-size: 28px;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.flex-icon-center {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
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

.text-danger {
  color: #ef4444;
}

.confirm-body {
  font-size: 14px;
  color: #64748b;
  line-height: 1.5;
  margin-bottom: 24px;
}

.confirm-body strong {
  color: #0f172a;
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

.btn-confirm-delete {
  background: #ef4444;
  color: #ffffff;
  border: none;
}

.btn-confirm-delete:hover {
  background: #dc2626;
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

.btn-confirm-warning {
  background: #f59e0b;
  color: #ffffff;
  border: none;
}

.btn-confirm-warning:hover {
  background: #d97706;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleIn {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
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
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .actions-group {
    flex-wrap: wrap;
    gap: 6px;
  }
  .modal-card {
    width: 95%;
    padding: 20px;
    max-height: 90vh;
    overflow-y: auto;
  }
}
</style>
