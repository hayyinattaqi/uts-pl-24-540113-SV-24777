<template>
  <main class="page-content">
    <header class="medicines-heading">
      <div>
        <p class="eyebrow">INVENTARIS KLINIK</p>
        <h1 class="page-heading">Obat</h1>
        <p class="page-description">Kelola daftar obat dan batas stok minimum.</p>
      </div>
      <button class="button" type="button" @click="openCreateForm">
        <span aria-hidden="true">＋</span>
        Tambah obat
      </button>
    </header>

    <section class="medicine-panel" aria-label="Daftar obat">
      <div class="medicine-toolbar">
        <label class="search-field">
          <span class="search-icon" aria-hidden="true">⌕</span>
          <span class="visually-hidden">Cari obat berdasarkan kode atau nama</span>
          <input
            v-model="search"
            type="search"
            placeholder="Cari nama atau kode obat"
            autocomplete="off"
          />
        </label>
        <span class="medicine-count">{{ medicines.length }} obat</span>
      </div>

      <div v-if="loadError" class="state-message state-error" role="alert">
        <span class="state-icon" aria-hidden="true">!</span>
        <div>
          <strong>Daftar obat tidak dapat dimuat</strong>
          <p>{{ loadError }}</p>
          <button class="text-button" type="button" @click="loadMedicines()">
            Coba lagi
          </button>
        </div>
      </div>

      <div v-else-if="isLoading && medicines.length === 0" class="state-message" role="status">
        <span class="spinner" aria-hidden="true"></span>
        <span>Memuat daftar obat...</span>
      </div>

      <div v-else-if="medicines.length === 0" class="state-message">
        <span class="empty-icon" aria-hidden="true">⌕</span>
        <strong>{{ search ? 'Obat tidak ditemukan' : 'Belum ada obat' }}</strong>
        <p>
          {{
            search
              ? 'Coba kata kunci lain untuk mencari obat.'
              : 'Tambahkan obat pertama untuk mulai mengelola inventaris.'
          }}
        </p>
        <button v-if="!search" class="button button-secondary" type="button" @click="openCreateForm">
          Tambah obat
        </button>
      </div>

      <div v-else class="table-scroll">
        <table class="medicine-table">
          <thead>
            <tr>
              <th scope="col">Kode</th>
              <th scope="col">Nama obat</th>
              <th scope="col">Satuan</th>
              <th scope="col" class="number-cell">Stok minimum</th>
              <th scope="col" class="actions-heading">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="medicine in medicines" :key="medicine.id">
              <td>
                <span class="medicine-code">{{ medicine.code }}</span>
              </td>
              <td class="medicine-name">{{ medicine.name }}</td>
              <td><span class="unit-pill">{{ medicine.unit }}</span></td>
              <td class="number-cell">{{ medicine.min_stock }}</td>
              <td>
                <div class="row-actions">
                  <button
                    class="icon-button edit-button"
                    type="button"
                    :aria-label="`Ubah ${medicine.name}`"
                    @click="openEditForm(medicine)"
                  >
                    <span aria-hidden="true">✎</span>
                    <span>Ubah</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="isLoading" class="refresh-indicator" role="status">
          Memperbarui daftar...
        </div>
      </div>
    </section>

    <Transition name="toast">
      <div v-if="successMessage" class="success-toast" role="status">
        <span aria-hidden="true">✓</span>
        {{ successMessage }}
      </div>
    </Transition>

    <MedicineForm
      v-if="showForm"
      :medicine="selectedMedicine"
      @close="closeForm"
      @saved="handleSaved"
    />
  </main>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import http from '../api/http'
import MedicineForm from '../components/MedicineForm.vue'

const medicines = ref([])
const search = ref('')
const isLoading = ref(false)
const loadError = ref('')
const showForm = ref(false)
const selectedMedicine = ref(null)
const successMessage = ref('')

let searchTimeout
let successTimeout
let requestSequence = 0

async function loadMedicines(searchTerm = search.value, sequence = ++requestSequence) {
  isLoading.value = true
  loadError.value = ''

  try {
    const response = await http.get('/medicines', {
      params: searchTerm ? { search: searchTerm } : {},
    })

    if (sequence === requestSequence) {
      medicines.value = response.data.data
    }
  } catch {
    if (sequence === requestSequence) {
      loadError.value = 'Periksa koneksi, lalu coba muat ulang daftar obat.'
    }
  } finally {
    if (sequence === requestSequence) {
      isLoading.value = false
    }
  }
}

watch(search, (value) => {
  window.clearTimeout(searchTimeout)
  const sequence = ++requestSequence
  isLoading.value = true
  loadError.value = ''
  searchTimeout = window.setTimeout(() => {
    loadMedicines(value, sequence)
  }, 300)
})

function openCreateForm() {
  selectedMedicine.value = null
  showForm.value = true
}

function openEditForm(medicine) {
  selectedMedicine.value = { ...medicine }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  selectedMedicine.value = null
}

async function handleSaved(message) {
  closeForm()
  showSuccess(message)
  await loadMedicines()
}

function showSuccess(message) {
  successMessage.value = message
  window.clearTimeout(successTimeout)
  successTimeout = window.setTimeout(() => {
    successMessage.value = ''
  }, 3500)
}

onMounted(() => {
  loadMedicines()
})

onBeforeUnmount(() => {
  window.clearTimeout(searchTimeout)
  window.clearTimeout(successTimeout)
})
</script>

<style scoped>
.medicines-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.medicines-heading > .button {
  flex: 0 0 auto;
}

.eyebrow {
  margin: 0 0 0.45rem;
  color: #16866e;
  font-size: 0.72rem;
  font-weight: 750;
  letter-spacing: 0.12em;
}

.medicine-panel {
  overflow: hidden;
  border: 1px solid #e3ebee;
  border-radius: 1rem;
  background: #fff;
  box-shadow: 0 12px 35px rgb(28 61 70 / 5%);
}

.medicine-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.15rem 1.35rem;
  border-bottom: 1px solid #edf1f3;
}

.search-field {
  display: flex;
  width: min(100%, 400px);
  min-height: 42px;
  align-items: center;
  gap: 0.55rem;
  padding: 0 0.75rem;
  border: 1px solid #dce6e9;
  border-radius: 0.65rem;
  color: #73878c;
}

.search-field:focus-within {
  border-color: #16866e;
  box-shadow: 0 0 0 3px rgb(22 134 110 / 10%);
}

.search-icon {
  font-size: 1.45rem;
  line-height: 1;
}

.search-field input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #243f46;
}

.search-field input::placeholder {
  color: #9aa9ad;
}

.medicine-count {
  color: #73878c;
  font-size: 0.85rem;
  white-space: nowrap;
}

.table-scroll {
  overflow-x: auto;
}

.medicine-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.medicine-table th {
  padding: 0.85rem 1.25rem;
  background: #f8fafb;
  color: #6c8187;
  font-size: 0.73rem;
  font-weight: 700;
  letter-spacing: 0.045em;
  text-transform: uppercase;
  white-space: nowrap;
}

.medicine-table td {
  padding: 1rem 1.25rem;
  border-top: 1px solid #edf1f3;
  color: #4e666c;
  font-size: 0.9rem;
}

.medicine-table tbody tr:hover {
  background: #fbfdfd;
}

.medicine-code {
  display: inline-block;
  padding: 0.28rem 0.5rem;
  border-radius: 0.4rem;
  background: #f0f5f5;
  color: #476168;
  font-size: 0.8rem;
  font-weight: 650;
}

.medicine-name {
  color: #243f46 !important;
  font-weight: 650;
}

.unit-pill {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border: 1px solid #e2ebed;
  border-radius: 999px;
  color: #627a80;
  font-size: 0.78rem;
}

.number-cell {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.actions-heading {
  text-align: right;
}

.row-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.45rem;
}

.icon-button {
  display: inline-flex;
  min-height: 34px;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.55rem;
  border: 1px solid transparent;
  border-radius: 0.5rem;
  background: transparent;
  font-size: 0.82rem;
  font-weight: 650;
}

.edit-button {
  color: #247d6b;
}

.edit-button:hover {
  background: #eaf7f2;
}

.state-message {
  display: flex;
  min-height: 240px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 2rem;
  color: #647b81;
  text-align: center;
}

.state-message strong {
  color: #29484e;
  font-size: 1rem;
}

.state-message p {
  max-width: 380px;
  margin: 0;
  font-size: 0.9rem;
}

.state-message .button {
  margin-top: 0.6rem;
}

.state-error {
  flex-direction: row;
  justify-content: flex-start;
  color: #8f3d3d;
  text-align: left;
}

.state-icon,
.empty-icon {
  display: grid;
  width: 2.6rem;
  height: 2.6rem;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 50%;
  background: #fff0ef;
  color: #b24d4d;
  font-weight: 750;
}

.empty-icon {
  background: #eaf7f2;
  color: #16866e;
  font-size: 1.4rem;
}

.text-button {
  margin-top: 0.45rem;
  padding: 0;
  border: 0;
  background: none;
  color: #16866e;
  font: inherit;
  font-size: 0.86rem;
  font-weight: 700;
}

.spinner {
  width: 1.5rem;
  height: 1.5rem;
  border: 2px solid #dcebe7;
  border-top-color: #16866e;
  border-radius: 50%;
  animation: spin 700ms linear infinite;
}

.refresh-indicator {
  padding: 0.65rem 1.25rem;
  border-top: 1px solid #edf1f3;
  color: #6e8287;
  font-size: 0.8rem;
  text-align: center;
}

.success-toast {
  position: fixed;
  z-index: 20;
  right: 1.5rem;
  bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.85rem 1.1rem;
  border: 1px solid #cbe9dc;
  border-radius: 0.7rem;
  background: #f1fbf6;
  box-shadow: 0 12px 35px rgb(28 61 70 / 12%);
  color: #176b56;
  font-size: 0.9rem;
  font-weight: 650;
}

.success-toast span {
  display: grid;
  width: 1.35rem;
  height: 1.35rem;
  place-items: center;
  border-radius: 50%;
  background: #d8f1e5;
}

.toast-enter-active,
.toast-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.toast-enter-from,
.toast-leave-to {
  transform: translateY(8px);
  opacity: 0;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  clip-path: inset(50%);
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 640px) {
  .medicines-heading {
    align-items: flex-start;
    flex-direction: column;
    margin-bottom: 1.5rem;
  }

  .medicines-heading > .button {
    width: 100%;
  }

  .medicine-toolbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .medicine-table {
    min-width: 700px;
  }

  .success-toast {
    right: 1rem;
    bottom: 1rem;
    left: 1rem;
    justify-content: center;
  }
}
</style>
