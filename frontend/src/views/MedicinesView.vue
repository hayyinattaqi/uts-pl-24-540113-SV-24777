<template>
  <main class="page-content">
    <header class="page-header">
      <div>
        <h1 class="page-heading">Obat</h1>
        <p class="page-description">Daftar obat, satuan, dan batas stok minimum. Total stok dihitung dari batch yang belum kedaluwarsa.</p>
      </div>
      <button class="button" type="button" @click="openCreateForm">
        <AppIcon name="plus" />
        Tambah obat
      </button>
    </header>

    <div v-if="actionError" class="alert alert-error" role="alert">
      <AppIcon name="alert" />
      <span>{{ actionError }}</span>
      <button class="alert-close" type="button" aria-label="Tutup pesan" @click="actionError = ''">
        <AppIcon name="close" />
      </button>
    </div>

    <section class="card" aria-labelledby="medicine-list-title">
      <div class="card-head">
        <h2 id="medicine-list-title">Daftar obat</h2>
        <span class="card-count"><strong>{{ medicines.length }}</strong> obat</span>
      </div>
      <div class="card-meta">
        <label class="search-field">
          <AppIcon name="search" />
          <span class="visually-hidden">Cari obat berdasarkan kode atau nama</span>
          <input v-model="search" type="search" placeholder="Cari nama atau kode obat" autocomplete="off" />
        </label>
        <span v-if="isLoading && medicines.length" role="status">Memperbarui daftar...</span>
      </div>

      <div v-if="loadError" class="state-message" role="alert">
        <strong>Daftar obat tidak dapat dimuat</strong>
        <p>{{ loadError }}</p>
        <button class="button button-secondary" type="button" @click="loadMedicines()">Coba lagi</button>
      </div>

      <div v-else-if="isLoading && medicines.length === 0" class="state-message" role="status">
        <span class="spinner" aria-hidden="true"></span>
        <span>Memuat daftar obat...</span>
      </div>

      <div v-else-if="medicines.length === 0" class="state-message">
        <strong>{{ search ? `Tidak ada obat dengan kata kunci "${search}"` : 'Belum ada obat' }}</strong>
        <p>
          {{ search ? 'Periksa ejaan, atau cari dengan kode obat.' : 'Tambahkan obat pertama, lalu catat stoknya lewat Obat Masuk.' }}
        </p>
        <button v-if="!search" class="button" type="button" @click="openCreateForm">
          <AppIcon name="plus" />
          Tambah obat
        </button>
      </div>

      <div v-else class="table-scroll">
        <table class="data-table medicine-table stack-table">
          <thead>
            <tr>
              <th scope="col">Kode</th>
              <th scope="col">Nama obat</th>
              <th scope="col">Satuan</th>
              <th scope="col" class="number-cell">Total stok</th>
              <th scope="col" class="number-cell">Stok min.</th>
              <th scope="col" class="number-cell"><span class="visually-hidden">Aksi</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="medicine in medicines" :key="medicine.id">
              <td class="code-tag" data-label="Kode">{{ medicine.code }}</td>
              <td class="cell-strong stack-title">{{ medicine.name }}</td>
              <td class="cell-muted" data-label="Satuan">{{ medicine.unit }}</td>
              <td class="number-cell" data-label="Total stok">
                <span v-if="medicine.is_low_stock" class="status status-warn low-flag">Menipis</span>
                <span :class="medicine.is_low_stock ? 'qty-low' : 'qty-strong'">{{ medicine.total_stock }}</span>
              </td>
              <td class="number-cell cell-muted" data-label="Stok min.">{{ medicine.min_stock }}</td>
              <td class="stack-actions">
                <div class="row-actions">
                  <button class="row-button" type="button" :aria-label="`Ubah ${medicine.name}`" @click="openEditForm(medicine)">
                    <AppIcon name="edit" />
                    Ubah
                  </button>
                  <button
                    class="row-button row-button-danger"
                    type="button"
                    :aria-label="`Hapus ${medicine.name}`"
                    :disabled="deletingId === medicine.id"
                    @click="deleteMedicine(medicine)"
                  >
                    <AppIcon name="trash" />
                    {{ deletingId === medicine.id ? 'Menghapus...' : 'Hapus' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <Transition name="toast">
      <div v-if="successMessage" class="toast" role="status">
        <AppIcon name="check" />
        {{ successMessage }}
      </div>
    </Transition>

    <MedicineForm v-if="showForm" :medicine="selectedMedicine" @close="closeForm" @saved="handleSaved" />
  </main>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import http from '../api/http'
import AppIcon from '../components/AppIcon.vue'
import MedicineForm from '../components/MedicineForm.vue'

const medicines = ref([])
const search = ref('')
const isLoading = ref(false)
const loadError = ref('')
const showForm = ref(false)
const selectedMedicine = ref(null)
const deletingId = ref(null)
const successMessage = ref('')
const actionError = ref('')

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

async function deleteMedicine(medicine) {
  const confirmed = window.confirm(`Hapus obat "${medicine.name}"?`)

  if (!confirmed) return

  deletingId.value = medicine.id
  actionError.value = ''

  try {
    await http.delete(`/medicines/${medicine.id}`)
    showSuccess('Obat berhasil dihapus.')
    await loadMedicines()
  } catch (error) {
    const status = error.response?.status

    if (status === 409) {
      actionError.value = `"${medicine.name}" tidak dapat dihapus karena masih memiliki batch stok. Riwayat stoknya harus tetap tersimpan.`
    } else if (status === 404) {
      actionError.value = 'Obat tidak ditemukan. Muat ulang daftar obat.'
    } else {
      actionError.value = 'Obat gagal dihapus. Silakan coba lagi.'
    }
  } finally {
    deletingId.value = null
  }
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
.qty-low {
  color: var(--warn);
  font-weight: 750;
}

.low-flag {
  margin-right: 0.6rem;
  font-size: 0.8rem;
}

.medicine-table td:first-child {
  width: 1%;
}
</style>
