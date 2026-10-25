<template>
  <main class="page-content">
    <header>
      <p class="eyebrow">TRANSAKSI STOK</p>
      <h1 class="page-heading">Riwayat</h1>
      <p class="page-description">100 transaksi obat masuk dan keluar terbaru.</p>
    </header>

    <div v-if="loadError" class="alert alert-error" role="alert">
      <span>{{ loadError }}</span>
    </div>

    <section class="panel" aria-label="Riwayat transaksi">
      <div class="panel-header">
        <div class="filter-tabs" role="group" aria-label="Filter tipe transaksi">
          <button
            v-for="filter in filters"
            :key="filter.value"
            type="button"
            class="filter-tab"
            :class="{ active: type === filter.value }"
            :aria-pressed="type === filter.value"
            @click="type = filter.value"
          >
            {{ filter.label }}
          </button>
        </div>
        <span class="cell-muted">{{ isLoading ? 'Memuat...' : `${movements.length} transaksi` }}</span>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th scope="col">Waktu</th>
              <th scope="col">Tipe</th>
              <th scope="col">Obat</th>
              <th scope="col">Batch</th>
              <th scope="col" class="number-cell">Jumlah</th>
              <th scope="col">Petugas</th>
              <th scope="col">Catatan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="movement in movements" :key="movement.id">
              <td>{{ formatDateTime(movement.moved_at) }}</td>
              <td>
                <span class="badge" :class="movement.type === 'in' ? 'badge-in' : 'badge-out'">
                  {{ movement.type === 'in' ? 'Masuk' : 'Keluar' }}
                </span>
              </td>
              <td class="cell-strong">{{ movement.batch.medicine.name }}</td>
              <td>{{ movement.batch.batch_number }}</td>
              <td class="number-cell">
                {{ movement.type === 'in' ? '+' : '−' }}{{ movement.quantity }} {{ movement.batch.medicine.unit }}
              </td>
              <td>{{ movement.user?.name ?? '-' }}</td>
              <td class="cell-muted">{{ movement.notes || '-' }}</td>
            </tr>
            <tr v-if="!isLoading && movements.length === 0">
              <td colspan="7" class="empty-row">Belum ada transaksi.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import http from '../api/http'
import { formatDateTime } from '../utils/format'

const filters = [
  { value: '', label: 'Semua' },
  { value: 'in', label: 'Masuk' },
  { value: 'out', label: 'Keluar' },
]

const type = ref('')
const movements = ref([])
const isLoading = ref(false)
const loadError = ref('')
let requestSequence = 0

async function loadMovements() {
  const sequence = ++requestSequence
  isLoading.value = true
  loadError.value = ''

  try {
    const response = await http.get('/stock-movements', { params: type.value ? { type: type.value } : {} })
    if (sequence === requestSequence) movements.value = response.data.data
  } catch {
    if (sequence === requestSequence) loadError.value = 'Riwayat tidak dapat dimuat. Periksa koneksi, lalu coba lagi.'
  } finally {
    if (sequence === requestSequence) isLoading.value = false
  }
}

watch(type, loadMovements)
onMounted(loadMovements)
</script>
