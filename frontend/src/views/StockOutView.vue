<template>
  <main class="page-content">
    <header>
      <p class="eyebrow">TRANSAKSI STOK</p>
      <h1 class="page-heading">Obat Keluar</h1>
      <p class="page-description">Pilih obat, lalu pilih batch yang akan dikeluarkan. Batch kedaluwarsa dan batch kosong tidak ditampilkan.</p>
    </header>

    <div v-if="successMessage" class="alert alert-success" role="status">
      <span>{{ successMessage }}</span>
      <button class="alert-close" type="button" aria-label="Tutup pesan" @click="successMessage = ''">×</button>
    </div>
    <div v-if="generalError" class="alert alert-error" role="alert">
      <span>{{ generalError }}</span>
    </div>

    <section class="panel">
      <div class="panel-body">
        <form class="stock-form" novalidate @submit.prevent="submitForm">
          <div class="form-field">
            <label for="stock-out-medicine">Obat</label>
            <select
              id="stock-out-medicine"
              v-model="medicineId"
              :disabled="medicinesLoading"
              @change="loadBatches"
            >
              <option value="">{{ medicinesLoading ? 'Memuat daftar obat...' : 'Pilih obat' }}</option>
              <option v-for="medicine in medicines" :key="medicine.id" :value="medicine.id">
                {{ medicine.code }} — {{ medicine.name }} (stok {{ medicine.total_stock }} {{ medicine.unit }})
              </option>
            </select>
          </div>

          <div class="form-field">
            <label for="stock-out-batch">Batch</label>
            <select
              id="stock-out-batch"
              v-model="form.batch_id"
              :disabled="!medicineId || batchesLoading"
              :aria-invalid="Boolean(fieldErrors.batch_id)"
              @change="clearFieldError('batch_id')"
            >
              <option value="">{{ batchPlaceholder }}</option>
              <option v-for="batch in availableBatches" :key="batch.id" :value="batch.id">
                {{ batch.batch_number }} — kedaluwarsa {{ formatDate(batch.expiry_date) }} — sisa {{ batch.current_quantity }}
              </option>
            </select>
            <p v-if="fieldErrors.batch_id" class="field-error" role="alert">{{ fieldErrors.batch_id }}</p>
            <p v-else-if="selectedBatch" class="field-hint">
              Status:
              <span class="badge" :class="batchStatusLabels[selectedBatch.status].className">
                {{ batchStatusLabels[selectedBatch.status].label }}
              </span>
            </p>
          </div>

          <div class="form-field">
            <label for="stock-out-quantity">Jumlah</label>
            <input
              id="stock-out-quantity"
              v-model.number="form.quantity"
              type="number"
              min="1"
              step="1"
              :max="selectedBatch?.current_quantity"
              :aria-invalid="Boolean(fieldErrors.quantity)"
              @input="clearFieldError('quantity')"
            />
            <p v-if="fieldErrors.quantity" class="field-error" role="alert">{{ fieldErrors.quantity }}</p>
          </div>

          <div class="form-field">
            <label for="stock-out-notes">Catatan (opsional)</label>
            <textarea
              id="stock-out-notes"
              v-model.trim="form.notes"
              maxlength="255"
              placeholder="Contoh: resep pasien poli umum"
              :aria-invalid="Boolean(fieldErrors.notes)"
              @input="clearFieldError('notes')"
            ></textarea>
            <p v-if="fieldErrors.notes" class="field-error" role="alert">{{ fieldErrors.notes }}</p>
          </div>

          <button class="button" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Menyimpan...' : 'Simpan obat keluar' }}
          </button>
        </form>
      </div>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import http from '../api/http'
import { batchStatusLabels, firstErrors, formatDate } from '../utils/format'

const medicines = ref([])
const medicinesLoading = ref(false)
const medicineId = ref('')
const batches = ref([])
const batchesLoading = ref(false)
const isSubmitting = ref(false)
const successMessage = ref('')
const generalError = ref('')

const form = reactive({ batch_id: '', quantity: 1, notes: '' })
const fieldErrors = reactive({})

const availableBatches = computed(() =>
  batches.value.filter((batch) => batch.status !== 'expired' && batch.current_quantity > 0),
)
const selectedBatch = computed(() => availableBatches.value.find((batch) => batch.id === form.batch_id))
const batchPlaceholder = computed(() => {
  if (!medicineId.value) return 'Pilih obat terlebih dahulu'
  if (batchesLoading.value) return 'Memuat batch...'
  return availableBatches.value.length ? 'Pilih batch' : 'Tidak ada batch yang bisa dikeluarkan'
})

function clearFieldError(field) {
  delete fieldErrors[field]
  generalError.value = ''
}

function resetErrors() {
  Object.keys(fieldErrors).forEach((field) => delete fieldErrors[field])
  generalError.value = ''
}

async function loadMedicines() {
  medicinesLoading.value = true
  try {
    const response = await http.get('/medicines')
    medicines.value = response.data.data
  } catch {
    generalError.value = 'Daftar obat tidak dapat dimuat. Muat ulang halaman.'
  } finally {
    medicinesLoading.value = false
  }
}

async function loadBatches() {
  form.batch_id = ''
  batches.value = []
  resetErrors()
  if (!medicineId.value) return

  batchesLoading.value = true
  try {
    const response = await http.get(`/medicines/${medicineId.value}`)
    batches.value = response.data.data.batches
  } catch {
    generalError.value = 'Batch obat tidak dapat dimuat. Coba pilih ulang obatnya.'
  } finally {
    batchesLoading.value = false
  }
}

async function submitForm() {
  if (isSubmitting.value) return

  resetErrors()
  successMessage.value = ''
  isSubmitting.value = true

  try {
    const response = await http.post('/stock-out', {
      batch_id: form.batch_id || null,
      quantity: form.quantity,
      notes: form.notes || null,
    })
    const movement = response.data.data
    successMessage.value = `Obat keluar tercatat: ${movement.batch.medicine.name}, batch ${movement.batch.batch_number}, ${movement.quantity} ${movement.batch.medicine.unit}. Sisa batch ${movement.batch.current_quantity}.`
    Object.assign(form, { batch_id: '', quantity: 1, notes: '' })
    await Promise.all([loadMedicines(), refreshBatches()])
  } catch (error) {
    const status = error.response?.status
    if (status === 422) {
      Object.assign(fieldErrors, firstErrors(error.response.data.errors))
    } else if (status === 404) {
      generalError.value = 'Batch tidak ditemukan. Pilih ulang obat dan batch.'
      await refreshBatches()
    } else {
      generalError.value = 'Obat keluar gagal disimpan. Periksa koneksi, lalu coba lagi.'
    }
  } finally {
    isSubmitting.value = false
  }
}

async function refreshBatches() {
  if (!medicineId.value) return
  const response = await http.get(`/medicines/${medicineId.value}`)
  batches.value = response.data.data.batches
}

onMounted(loadMedicines)
</script>
