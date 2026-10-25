<template>
  <main class="page-content">
    <header>
      <p class="eyebrow">TRANSAKSI STOK</p>
      <h1 class="page-heading">Obat Masuk</h1>
      <p class="page-description">Catat penerimaan obat. Setiap penerimaan membuat batch baru dengan tanggal kedaluwarsanya.</p>
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
            <label for="stock-in-medicine">Obat</label>
            <select
              id="stock-in-medicine"
              v-model="form.medicine_id"
              :aria-invalid="Boolean(fieldErrors.medicine_id)"
              :disabled="medicinesLoading"
              @change="clearFieldError('medicine_id')"
            >
              <option value="">{{ medicinesLoading ? 'Memuat daftar obat...' : 'Pilih obat' }}</option>
              <option v-for="medicine in medicines" :key="medicine.id" :value="medicine.id">
                {{ medicine.code }} — {{ medicine.name }} ({{ medicine.unit }})
              </option>
            </select>
            <p v-if="fieldErrors.medicine_id" class="field-error" role="alert">{{ fieldErrors.medicine_id }}</p>
          </div>

          <div class="form-grid-2">
            <div class="form-field">
              <label for="stock-in-batch">Nomor batch</label>
              <input
                id="stock-in-batch"
                v-model.trim="form.batch_number"
                type="text"
                maxlength="50"
                autocomplete="off"
                placeholder="Contoh: BN-2026-01"
                :aria-invalid="Boolean(fieldErrors.batch_number)"
                @input="clearFieldError('batch_number')"
              />
              <p v-if="fieldErrors.batch_number" class="field-error" role="alert">{{ fieldErrors.batch_number }}</p>
            </div>

            <div class="form-field">
              <label for="stock-in-expiry">Tanggal kedaluwarsa</label>
              <input
                id="stock-in-expiry"
                v-model="form.expiry_date"
                type="date"
                :min="minExpiryDate"
                :aria-invalid="Boolean(fieldErrors.expiry_date)"
                @input="clearFieldError('expiry_date')"
              />
              <p v-if="fieldErrors.expiry_date" class="field-error" role="alert">{{ fieldErrors.expiry_date }}</p>
            </div>
          </div>

          <div class="form-field">
            <label for="stock-in-quantity">Jumlah</label>
            <input
              id="stock-in-quantity"
              v-model.number="form.quantity"
              type="number"
              min="1"
              step="1"
              :aria-invalid="Boolean(fieldErrors.quantity)"
              @input="clearFieldError('quantity')"
            />
            <p v-if="fieldErrors.quantity" class="field-error" role="alert">{{ fieldErrors.quantity }}</p>
          </div>

          <div class="form-field">
            <label for="stock-in-notes">Catatan (opsional)</label>
            <textarea
              id="stock-in-notes"
              v-model.trim="form.notes"
              maxlength="255"
              placeholder="Contoh: kiriman distributor"
              :aria-invalid="Boolean(fieldErrors.notes)"
              @input="clearFieldError('notes')"
            ></textarea>
            <p v-if="fieldErrors.notes" class="field-error" role="alert">{{ fieldErrors.notes }}</p>
          </div>

          <button class="button" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Menyimpan...' : 'Simpan obat masuk' }}
          </button>
        </form>
      </div>
    </section>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import http from '../api/http'
import { firstErrors, formatDate, toInputDate } from '../utils/format'

const route = useRoute()
const medicines = ref([])
const medicinesLoading = ref(false)
const isSubmitting = ref(false)
const successMessage = ref('')
const generalError = ref('')

const tomorrow = new Date()
tomorrow.setDate(tomorrow.getDate() + 1)
const minExpiryDate = toInputDate(tomorrow)

const emptyForm = () => ({ medicine_id: '', batch_number: '', expiry_date: '', quantity: 1, notes: '' })
const form = reactive(emptyForm())
const fieldErrors = reactive({})

function clearFieldError(field) {
  delete fieldErrors[field]
  generalError.value = ''
}

async function loadMedicines() {
  medicinesLoading.value = true
  try {
    const response = await http.get('/medicines')
    medicines.value = response.data.data

    // Pintasan "Tambah stok" dari Dashboard mengirim ?medicine_id=<id>.
    const preselected = Number(route.query.medicine_id)
    if (medicines.value.some((medicine) => medicine.id === preselected)) {
      form.medicine_id = preselected
    }
  } catch {
    generalError.value = 'Daftar obat tidak dapat dimuat. Muat ulang halaman.'
  } finally {
    medicinesLoading.value = false
  }
}

async function submitForm() {
  if (isSubmitting.value) return

  Object.keys(fieldErrors).forEach((field) => delete fieldErrors[field])
  generalError.value = ''
  successMessage.value = ''
  isSubmitting.value = true

  try {
    const response = await http.post('/stock-in', {
      ...form,
      medicine_id: form.medicine_id || null,
      notes: form.notes || null,
    })
    const { batch } = response.data.data
    successMessage.value = `Obat masuk tercatat: ${batch.medicine.name}, batch ${batch.batch_number}, ${batch.current_quantity} ${batch.medicine.unit}, kedaluwarsa ${formatDate(batch.expiry_date)}.`
    Object.assign(form, emptyForm())
  } catch (error) {
    if (error.response?.status === 422) {
      Object.assign(fieldErrors, firstErrors(error.response.data.errors))
    } else {
      generalError.value = 'Obat masuk gagal disimpan. Periksa koneksi, lalu coba lagi.'
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(loadMedicines)
</script>
