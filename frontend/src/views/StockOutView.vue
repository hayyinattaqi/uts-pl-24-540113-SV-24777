<template>
  <main class="page-content">
    <header class="page-header">
      <div>
        <h1 class="page-heading">Obat Keluar</h1>
        <p class="page-description">Pilih obat, lalu pilih batch yang dikeluarkan. Batch kedaluwarsa dan batch kosong tidak bisa dipilih.</p>
      </div>
    </header>

    <div v-if="successMessage" class="alert alert-success" role="status">
      <AppIcon name="check" />
      <span>{{ successMessage }}</span>
      <button class="alert-close" type="button" aria-label="Tutup pesan" @click="successMessage = ''">
        <AppIcon name="close" />
      </button>
    </div>
    <div v-if="generalError" class="alert alert-error" role="alert">
      <AppIcon name="alert" />
      <span>{{ generalError }}</span>
    </div>

    <div class="stock-layout">
    <form novalidate @submit.prevent="submitForm">
      <section class="card" aria-labelledby="stock-out-title">
        <div class="card-head">
          <h2 id="stock-out-title">Formulir obat keluar</h2>
        </div>
        <div class="card-body medicine-step">
          <div class="form-field">
            <label for="stock-out-medicine">Obat</label>
            <select id="stock-out-medicine" v-model="medicineId" :disabled="medicinesLoading" @change="loadBatches">
              <option value="">{{ medicinesLoading ? 'Memuat daftar obat...' : 'Pilih obat' }}</option>
              <option v-for="medicine in medicines" :key="medicine.id" :value="medicine.id">
                {{ medicine.code }} · {{ medicine.name }} (stok {{ medicine.total_stock }} {{ medicine.unit }})
              </option>
            </select>
          </div>
        </div>

        <fieldset class="batch-step" :aria-invalid="Boolean(fieldErrors.batch_id)" aria-describedby="batch-step-hint">
          <legend class="card-meta batch-legend">
            <span class="field-label">Batch</span>
            <span id="batch-step-hint">{{ batchPlaceholder }}</span>
          </legend>
          <div v-if="availableBatches.length" class="table-scroll">
            <table class="data-table batch-table stack-table">
              <thead>
                <tr>
                  <th scope="col"><span class="visually-hidden">Pilih</span></th>
                  <th scope="col">Batch</th>
                  <th scope="col">Kedaluwarsa</th>
                  <th scope="col">Status</th>
                  <th scope="col" class="number-cell">Sisa</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="batch in availableBatches"
                  :key="batch.id"
                  :class="{ selected: form.batch_id === batch.id }"
                  @click="selectBatch(batch.id)"
                >
                  <td class="radio-cell stack-title">
                    <input
                      :id="`batch-${batch.id}`"
                      v-model="form.batch_id"
                      type="radio"
                      name="batch"
                      :value="batch.id"
                      @change="clearFieldError('batch_id')"
                    />
                  </td>
                  <td data-label="Batch">
                    <label class="code-tag batch-label" :for="`batch-${batch.id}`">{{ batch.batch_number }}</label>
                  </td>
                  <td data-label="Kedaluwarsa">{{ formatDate(batch.expiry_date) }}</td>
                  <td data-label="Status">
                    <span class="status" :class="batchStatusLabels[batch.status].className">
                      {{ batchStatusLabels[batch.status].label }}
                    </span>
                  </td>
                  <td class="number-cell" data-label="Sisa">
                    <span class="qty-strong">{{ batch.current_quantity }}</span><span class="unit">{{ selectedMedicine?.unit }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="fieldErrors.batch_id" class="field-error batch-error" role="alert">{{ fieldErrors.batch_id }}</p>
        </fieldset>

        <div class="card-body detail-step">
          <div class="form-field quantity-field">
            <label for="stock-out-quantity">
              Jumlah<span v-if="selectedMedicine" class="optional"> ({{ selectedMedicine.unit }})</span>
            </label>
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
            <p v-else-if="selectedBatch" class="field-hint">Maksimal {{ selectedBatch.current_quantity }} dari batch {{ selectedBatch.batch_number }}.</p>
          </div>

          <div class="form-field">
            <label for="stock-out-notes">Catatan <span class="optional">(opsional)</span></label>
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

          <div class="form-actions">
            <button class="button" type="submit" :disabled="isSubmitting">
              <AppIcon name="out" />
              {{ isSubmitting ? 'Menyimpan...' : 'Simpan obat keluar' }}
            </button>
          </div>
        </div>
      </section>
    </form>

    <aside class="card batch-summary" aria-labelledby="batch-summary-title">
      <div class="card-head">
        <h2 id="batch-summary-title">{{ selectedBatch ? selectedBatch.batch_number : 'Batch dipilih' }}</h2>
        <span class="card-count">{{ selectedBatch ? `ED ${formatDate(selectedBatch.expiry_date)}` : 'Belum ada' }}</span>
      </div>
      <p class="card-meta">{{ selectedMedicine ? selectedMedicine.name : 'Pilih obat dan batch di formulir.' }}</p>
      <dl class="summary-list">
        <div>
          <dt>Sisa sekarang</dt>
          <dd>{{ selectedBatch ? selectedBatch.current_quantity : '-' }}</dd>
        </div>
        <div>
          <dt>Keluar</dt>
          <dd>{{ selectedBatch ? `−${outQuantity}` : '-' }}</dd>
        </div>
        <div class="summary-total">
          <dt>Sisa sesudah</dt>
          <dd>
            <span :class="remainingAfter < 0 ? 'qty-over' : 'qty-strong'">{{ selectedBatch ? remainingAfter : '-' }}</span><span class="unit">{{ selectedBatch ? selectedMedicine?.unit : '' }}</span>
          </dd>
        </div>
      </dl>
      <p v-if="remainingAfter < 0" class="field-error summary-warning">Jumlah melebihi stok batch.</p>
    </aside>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import http from '../api/http'
import AppIcon from '../components/AppIcon.vue'
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

const selectedMedicine = computed(() => medicines.value.find((medicine) => medicine.id === medicineId.value))
const availableBatches = computed(() =>
  batches.value.filter((batch) => batch.status !== 'expired' && batch.current_quantity > 0),
)
const selectedBatch = computed(() => availableBatches.value.find((batch) => batch.id === form.batch_id))
const outQuantity = computed(() => (Number.isInteger(form.quantity) && form.quantity > 0 ? form.quantity : 0))
const remainingAfter = computed(() => (selectedBatch.value ? selectedBatch.value.current_quantity - outQuantity.value : 0))
const batchPlaceholder = computed(() => {
  if (!medicineId.value) return 'Pilih obat terlebih dahulu.'
  if (batchesLoading.value) return 'Memuat batch...'
  return availableBatches.value.length
    ? `${availableBatches.value.length} batch bisa dikeluarkan. Pilih satu.`
    : 'Tidak ada batch yang bisa dikeluarkan untuk obat ini.'
})

function clearFieldError(field) {
  delete fieldErrors[field]
  generalError.value = ''
}

function selectBatch(id) {
  form.batch_id = id
  clearFieldError('batch_id')
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

<style scoped>
.stock-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 1.5rem;
  align-items: start;
}

.batch-summary .card-meta {
  margin: 0;
}

.summary-list {
  margin: 0;
}

.summary-list div {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.65rem 1.1rem;
  border-bottom: 1px solid var(--line-soft);
}

.summary-list dt {
  color: var(--ink-muted);
  font-size: 0.88rem;
}

.summary-list dd {
  margin: 0;
  font-weight: 600;
}

.summary-total {
  border-bottom: 0 !important;
}

.summary-total .qty-strong,
.summary-total .qty-over {
  font-size: 1.5rem;
}

.qty-over {
  color: var(--danger);
  font-weight: 750;
}

.summary-warning {
  padding: 0 1.1rem 0.9rem;
}

@media (max-width: 900px) {
  .stock-layout {
    grid-template-columns: 1fr;
  }
}

.medicine-step {
  padding-bottom: 0.4rem;
}

.medicine-step .form-field {
  max-width: 520px;
}

.batch-step {
  margin: 0;
  padding: 0;
  border: 0;
  border-top: 1px solid var(--line-soft);
}

.batch-legend {
  display: flex;
  width: 100%;
  float: left;
  justify-content: flex-start;
  gap: 0.75rem;
}

.batch-legend .field-label {
  margin: 0;
}

.batch-step .table-scroll {
  clear: both;
}

.batch-step[aria-invalid="true"] .batch-legend {
  border-bottom-color: var(--danger);
}

.batch-table tbody tr {
  cursor: pointer;
}

.batch-table tbody tr.selected td {
  background: var(--green-100);
}

.radio-cell {
  width: 2.5rem;
}

.radio-cell input {
  width: 1.1rem;
  height: 1.1rem;
  margin: 0;
  accent-color: var(--green-800);
}

.batch-label {
  cursor: pointer;
}

.batch-error {
  clear: both;
  padding: 0.5rem 1.1rem 0;
}

.detail-step {
  clear: both;
  border-top: 1px solid var(--line-soft);
}

.quantity-field {
  max-width: 240px;
}

@media (max-width: 640px) {
  .batch-table tbody tr {
    position: relative;
    padding-left: 3rem;
  }

  .batch-table td.radio-cell {
    position: absolute;
    top: 0.95rem;
    left: 1.1rem;
    width: auto;
  }
}
</style>
