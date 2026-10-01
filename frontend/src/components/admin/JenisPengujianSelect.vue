<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Check, Plus, X, Layers, Tag, Trash2, CheckSquare } from '@lucide/vue'
import {
  MASTER_JENIS_PENGUJIAN,
  ALL_PARAMETERS,
  formatCrossJenisPengujian,
  parseCrossJenisPengujian
} from '../../constants/jenisPengujian'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  highlighted: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

// Kategori aktif yang sedang dibuka di dropdown
const activeCategoryName = ref('Analisis SSR/RAPD')

// State parameter terpilih (Set/Array of unique ID: "Kategori::NamaParameter")
const selectedIds = ref([])
const customText = ref('')
const customInput = ref('')

// Dapatkan objek kategori aktif
const currentCategory = computed(() => {
  return MASTER_JENIS_PENGUJIAN.find(c => c.kategori === activeCategoryName.value) || MASTER_JENIS_PENGUJIAN[0]
})

// Hitung berapa item terpilih per kategori
const getSelectedCountForCat = (catName) => {
  return selectedIds.value.filter(id => id.startsWith(`${catName}::`)).length
}

// Cek apakah parameter tertentu sedang terpilih
const isParamSelected = (catName, paramName) => {
  return selectedIds.value.includes(`${catName}::${paramName}`)
}

// Toggle parameter (klik chip)
const toggleParam = (catName, paramName) => {
  if (props.disabled) return
  const id = `${catName}::${paramName}`
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
  emitUpdate()
}

// Pilih semua parameter di kategori aktif
const selectAllInActiveCategory = () => {
  if (props.disabled || !currentCategory.value.subLayanan) return
  currentCategory.value.subLayanan.forEach(sub => {
    const id = `${currentCategory.value.kategori}::${sub}`
    if (!selectedIds.value.includes(id)) {
      selectedIds.value.push(id)
    }
  })
  emitUpdate()
}

// Kosongkan parameter di kategori aktif saja
const clearActiveCategory = () => {
  if (props.disabled) return
  const prefix = `${currentCategory.value.kategori}::`
  selectedIds.value = selectedIds.value.filter(id => !id.startsWith(prefix))
  emitUpdate()
}

// Tambah / Sinkronkan pengujian lainnya (custom)
const onCustomInput = () => {
  if (props.disabled) return
  customText.value = customInput.value.trim()
  emitUpdate()
}

const addCustomParam = () => {
  if (!customInput.value.trim() || props.disabled) return
  customText.value = customInput.value.trim()
  emitUpdate()
}

const removeCustomParam = () => {
  if (props.disabled) return
  customText.value = ''
  customInput.value = ''
  emitUpdate()
}

// Hapus satu parameter langsung dari daftar ringkasan
const removeSelectedId = (id) => {
  if (props.disabled) return
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
    emitUpdate()
  }
}

// Kosongkan seluruh pilihan dari semua kategori
const clearAllSelections = () => {
  if (props.disabled) return
  selectedIds.value = []
  customText.value = ''
  customInput.value = ''
  emitUpdate()
}

// Format chips terpilih untuk tampilan visual
const selectedChips = computed(() => {
  const list = []
  selectedIds.value.forEach(id => {
    const [kategori, nama] = id.split('::')
    list.push({ id, kategori, nama })
  })
  return list
})

// Sinkronkan ke modelValue induk
const emitUpdate = () => {
  const formatted = formatCrossJenisPengujian(selectedIds.value, customText.value)
  emit('update:modelValue', formatted)
}

// Sinkronkan dari props.modelValue
const syncFromProps = (val) => {
  const parsed = parseCrossJenisPengujian(val)
  selectedIds.value = parsed.selectedIds || []
  customText.value = parsed.customText || ''
  customInput.value = parsed.customText || ''

  // Jika ada pilihan, arahkan dropdown kategori ke kategori item pertama agar relevan
  if (selectedIds.value.length > 0) {
    const firstCat = selectedIds.value[0].split('::')[0]
    if (MASTER_JENIS_PENGUJIAN.some(c => c.kategori === firstCat)) {
      activeCategoryName.value = firstCat
    }
  }
}

watch(() => props.modelValue, (newVal) => {
  const currentFormatted = formatCrossJenisPengujian(selectedIds.value, customText.value)
  if (newVal !== currentFormatted) {
    syncFromProps(newVal)
  }
})

onMounted(() => {
  syncFromProps(props.modelValue)
})
</script>

<template>
  <div class="jenis-pengujian-selector" :class="{ 'is-highlighted': highlighted, 'is-disabled': disabled }">
    
    <!-- Tingkat 1: Dropdown Kategori Layanan (Tetap di posisinya) -->
    <div class="selector-category-row">
      <label class="category-dropdown-label">Pilih Kategori Layanan:</label>
      <select 
        v-model="activeCategoryName" 
        class="category-native-select"
        :disabled="disabled"
      >
        <option 
          v-for="cat in MASTER_JENIS_PENGUJIAN" 
          :key="cat.kategori" 
          :value="cat.kategori"
        >
          {{ cat.kategori }} {{ getSelectedCountForCat(cat.kategori) > 0 ? `(✓ ${getSelectedCountForCat(cat.kategori)} dipilih)` : '' }}
        </option>
      </select>
    </div>

    <!-- Tingkat 2: Panel Parameter / Sub-Layanan Sesuai Kategori yang Dipilih -->
    <div class="active-category-panel">
      <div class="panel-header-bar">
        <div class="panel-title-group">
          <Layers :size="15" class="panel-icon" />
          <span class="panel-title-text">{{ currentCategory.kategori }}</span>
          <span v-if="getSelectedCountForCat(currentCategory.kategori) > 0" class="badge-count-active">
            {{ getSelectedCountForCat(currentCategory.kategori) }} dipilih
          </span>
        </div>

        <!-- Tombol Pintas Kategori (jika memiliki multi sub-layanan) -->
        <div v-if="currentCategory.subLayanan && currentCategory.subLayanan.length > 1 && !disabled" class="panel-actions-quick">
          <button type="button" @click="selectAllInActiveCategory" class="btn-quick-action">Pilih Semua</button>
          <span class="action-sep">•</span>
          <button type="button" @click="clearActiveCategory" class="btn-quick-action text-muted">Batal Kategori Ini</button>
        </div>
      </div>

      <!-- Kategori dengan Beberapa Sub-Layanan (Multi Pill Selection) -->
      <div v-if="currentCategory.subLayanan && currentCategory.subLayanan.length > 0 && !currentCategory.isSingle" class="parameter-chips-grid">
        <button
          v-for="sub in currentCategory.subLayanan"
          :key="sub"
          type="button"
          class="param-toggle-pill"
          :class="{ 'is-active': isParamSelected(currentCategory.kategori, sub) }"
          @click="toggleParam(currentCategory.kategori, sub)"
          :disabled="disabled"
        >
          <span class="pill-check-indicator">
            <Check v-if="isParamSelected(currentCategory.kategori, sub)" :size="13" />
            <Plus v-else :size="13" />
          </span>
          <span class="pill-label">{{ sub }}</span>
        </button>
      </div>

      <!-- Kategori Tunggal (Analisis Ploidi Level, Sensitivitas, dll.) -->
      <div v-else-if="currentCategory.isSingle" class="single-category-cta">
        <p class="single-desc">Layanan ini merupakan pengujian paket tunggal tanpa sub-parameter tambahan.</p>
        <button
          type="button"
          class="param-toggle-pill is-single-btn"
          :class="{ 'is-active': isParamSelected(currentCategory.kategori, currentCategory.kategori) }"
          @click="toggleParam(currentCategory.kategori, currentCategory.kategori)"
          :disabled="disabled"
        >
          <span class="pill-check-indicator">
            <Check v-if="isParamSelected(currentCategory.kategori, currentCategory.kategori)" :size="14" />
            <Plus v-else :size="14" />
          </span>
          <span>{{ isParamSelected(currentCategory.kategori, currentCategory.kategori) ? 'Layanan Ini Telah Dipilih' : 'Pilih Layanan Ini' }}</span>
        </button>
      </div>

      <!-- Kategori Kustom: Pengujian Lainnya -->
      <div v-else-if="currentCategory.isCustom" class="custom-category-box">
        <div class="custom-input-row">
          <input
            type="text"
            v-model="customInput"
            @input="onCustomInput"
            @keydown.enter.prevent="addCustomParam"
            placeholder="Ketikkan nama pengujian spesifik lainnya..."
            class="custom-field"
            :disabled="disabled"
          />
          <button 
            type="button" 
            @click="addCustomParam" 
            class="btn-add-custom"
            :disabled="!customInput.trim() || disabled"
          >
            <Plus :size="14" /> Tambahkan
          </button>
        </div>
        <div v-if="customText" class="custom-active-tag">
          <span>Kustom: <strong>{{ customText }}</strong></span>
          <button type="button" @click="removeCustomParam" class="btn-remove-custom" title="Hapus"><X :size="12" /></button>
        </div>
      </div>
    </div>

    <!-- Ringkasan Akumulatif Cross-Kategori (Semua Parameter yang Sedang Terpilih) -->
    <div class="summary-accumulative-box">
      <div class="summary-header-row">
        <div class="summary-header-left">
          <Tag :size="14" class="summary-icon" />
          <span class="summary-title">Parameter Terpilih</span>
          <span class="summary-badge-total">{{ selectedChips.length + (customText ? 1 : 0) }} item</span>
        </div>
        <button 
          v-if="(selectedChips.length > 0 || customText) && !disabled" 
          type="button" 
          @click="clearAllSelections" 
          class="btn-clear-everything"
          title="Kosongkan semua parameter"
        >
          <Trash2 :size="12" /> Hapus Semua
        </button>
      </div>

      <!-- Jika Belum Ada Pilihan -->
      <div v-if="selectedChips.length === 0 && !customText" class="empty-selection-placeholder">
        <span>Belum ada parameter uji yang dipilih. Klik tombol <strong>+</strong> pada parameter di atas untuk memilih (bisa pilih dari beberapa kategori sekaligus).</span>
      </div>

      <!-- Daftar Chips Terpilih Lintas Kategori -->
      <div v-else class="chips-flex-wrap">
        <div 
          v-for="chip in selectedChips" 
          :key="chip.id" 
          class="chosen-chip"
        >
          <span class="chosen-cat">{{ chip.kategori }}:</span>
          <span class="chosen-name">{{ chip.nama }}</span>
          <button 
            v-if="!disabled" 
            type="button" 
            @click="removeSelectedId(chip.id)" 
            class="chosen-remove-btn" 
            title="Hapus parameter ini"
          >
            <X :size="12" />
          </button>
        </div>

        <div v-if="customText" class="chosen-chip is-custom">
          <span class="chosen-cat">Lainnya:</span>
          <span class="chosen-name">{{ customText }}</span>
          <button 
            v-if="!disabled" 
            type="button" 
            @click="removeCustomParam" 
            class="chosen-remove-btn" 
            title="Hapus"
          >
            <X :size="12" />
          </button>
        </div>
      </div>
    </div>

    <!-- Input tersembunyi untuk validasi form required HTML5 -->
    <input 
      type="text" 
      :value="modelValue" 
      :required="required" 
      tabindex="-1"
      class="hidden-input-validator"
    />
  </div>
</template>

<style scoped>
.jenis-pengujian-selector {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
}

/* Tingkat 1: Dropdown Kategori */
.selector-category-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.category-dropdown-label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.category-native-select {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #DCDACD;
  border-radius: 8px;
  font-size: 14px;
  color: #1e293b;
  background-color: #ffffff;
  outline: none;
  font-weight: 600;
  transition: all 0.2s ease;
  cursor: pointer;
}

.category-native-select:focus {
  border-color: #1B4D3E;
  box-shadow: 0 0 0 3px rgba(184, 144, 31, 0.25);
}

.jenis-pengujian-selector.is-highlighted .category-native-select {
  border-color: #10b981;
  background-color: #f0fdf4;
}

/* Tingkat 2: Panel Parameter Kategori Aktif */
.active-category-panel {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.panel-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 10px;
}

.panel-title-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.panel-icon {
  color: #1B4D3E;
  flex-shrink: 0;
}

.panel-title-text {
  font-size: 13.5px;
  font-weight: 700;
  color: #1B4D3E;
}

.badge-count-active {
  background: #1B4D3E;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 8px;
  border-radius: 9999px;
}

.panel-actions-quick {
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-quick-action {
  background: none;
  border: none;
  color: #0284c7;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 4px;
  transition: all 0.15s ease;
}

.btn-quick-action:hover {
  background: #e0f2fe;
}

.btn-quick-action.text-muted {
  color: #64748b;
}

.btn-quick-action.text-muted:hover {
  background: #f1f5f9;
  color: #334155;
}

.action-sep {
  color: #cbd5e1;
  font-size: 10px;
}

/* Parameter Grid Buttons / Chips */
.parameter-chips-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 6px;
  max-height: 165px;
  overflow-y: auto;
  padding-right: 4px;
}

.param-toggle-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 9px;
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 6px;
  font-size: 12px;
  color: #334155;
  text-align: left;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
  line-height: 1.3;
}

.param-toggle-pill:hover:not(:disabled) {
  border-color: #1B4D3E;
  background: #f1f5f9;
}

.param-toggle-pill.is-active {
  background: #1B4D3E;
  border-color: #1B4D3E;
  color: #ffffff;
  box-shadow: 0 2px 6px rgba(27, 77, 62, 0.25);
}

.pill-check-indicator {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s ease;
}

.param-toggle-pill.is-active .pill-check-indicator {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

.pill-label {
  font-weight: 500;
}

.param-toggle-pill.is-active .pill-label {
  font-weight: 600;
}

/* Single Category View */
.single-category-cta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-start;
}

.single-desc {
  margin: 0;
  font-size: 12.5px;
  color: #64748b;
}

.is-single-btn {
  max-width: 320px;
  padding: 10px 16px;
}

/* Custom Category View */
.custom-category-box {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.custom-input-row {
  display: flex;
  gap: 8px;
}

.custom-field {
  flex: 1;
  padding: 8px 12px;
  border: 1.5px solid #cbd5e1;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
}

.custom-field:focus {
  border-color: #1B4D3E;
}

.btn-add-custom {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 8px 14px;
  background: #1B4D3E;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
}

.btn-add-custom:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.custom-active-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 6px;
  font-size: 12px;
  color: #166534;
  width: fit-content;
}

.btn-remove-custom {
  background: none;
  border: none;
  color: #991b1b;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
}

/* Ringkasan Akumulatif Cross-Kategori */
.summary-accumulative-box {
  background: #ffffff;
  border: 1.5px dashed #cbd5e1;
  border-radius: 8px;
  padding: 8px 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.summary-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.summary-header-left {
  display: flex;
  align-items: center;
  gap: 6px;
}

.summary-icon {
  color: #1B4D3E;
}

.summary-title {
  font-size: 12px;
  font-weight: 700;
  color: #1B4D3E;
}

.summary-badge-total {
  font-size: 10.5px;
  font-weight: 700;
  background: #e2e8f0;
  color: #334155;
  padding: 1px 6px;
  border-radius: 9999px;
}

.btn-clear-everything {
  background: none;
  border: none;
  color: #b91c1c;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 1px 5px;
  border-radius: 4px;
  transition: all 0.15s ease;
}

.btn-clear-everything:hover {
  background: #fee2e2;
}

.empty-selection-placeholder {
  font-size: 11.5px;
  color: #94a3b8;
  line-height: 1.35;
  padding: 2px 0;
}

/* Chips Flex Wrap */
.chips-flex-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  max-height: 80px;
  overflow-y: auto;
  padding-right: 2px;
}

.chosen-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  background: #f0fdf4;
  border: 1px solid #86efac;
  border-radius: 5px;
  font-size: 11.5px;
  color: #166534;
  line-height: 1.3;
  animation: popIn 0.15s ease;
}

.chosen-chip.is-custom {
  background: #fffbeb;
  border-color: #fde68a;
  color: #92400e;
}

.chosen-cat {
  font-weight: 700;
  color: #1B4D3E;
  font-size: 11px;
}

.chosen-chip.is-custom .chosen-cat {
  color: #b45309;
}

.chosen-name {
  color: #1e293b;
}

.chosen-remove-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  margin-left: 2px;
  transition: all 0.15s ease;
}

.chosen-remove-btn:hover {
  background: #fecaca;
  color: #b91c1c;
}

/* Hidden HTML5 validator */
.hidden-input-validator {
  position: absolute;
  opacity: 0;
  pointer-events: none;
  height: 0;
  width: 0;
}

@keyframes popIn {
  from { opacity: 0; transform: scale(0.92); }
  to { opacity: 1; transform: scale(1); }
}

@media (max-width: 640px) {
  .parameter-chips-grid {
    grid-template-columns: 1fr;
  }
}
</style>
