<template>
  <main class="page-content">
    <header class="page-header">
      <div>
        <h1 class="page-heading">Riwayat</h1>
        <p class="page-description">
          Maksimal 100 transaksi terbaru. Kolom Sisa menunjukkan stok batch setelah transaksi itu. Transaksi tidak dapat diubah atau dihapus.
        </p>
      </div>
    </header>

    <div v-if="loadError" class="alert alert-error" role="alert">
      <AppIcon name="alert" />
      <span>{{ loadError }}</span>
      <button class="text-button" type="button" @click="loadMovements">Coba lagi</button>
    </div>

    <section class="card" aria-labelledby="history-title">
      <div class="card-head">
        <h2 id="history-title">Kartu transaksi stok</h2>
        <span class="card-count">
          <template v-if="isLoading">Memuat...</template>
          <template v-else><strong>{{ movements.length }}</strong> transaksi</template>
        </span>
      </div>
      <div class="card-meta">
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
      </div>
      <div class="table-scroll">
        <table class="data-table history-table stack-table">
          <thead>
            <tr>
              <th scope="col">Waktu</th>
              <th scope="col">Obat</th>
              <th scope="col">Batch</th>
              <th scope="col" class="number-cell">Masuk</th>
              <th scope="col" class="number-cell">Keluar</th>
              <th v-if="showBalance" scope="col" class="number-cell">Sisa</th>
              <th scope="col">Petugas</th>
              <th scope="col">Catatan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="movement in movements" :key="movement.id">
              <td class="cell-muted time-cell" data-label="Waktu">{{ formatDateTime(movement.moved_at) }}</td>
              <td class="cell-strong stack-title">{{ movement.batch.medicine.name }}</td>
              <td class="code-tag" data-label="Batch">{{ movement.batch.batch_number }}</td>
              <td class="number-cell in-cell" data-label="Masuk">
                <template v-if="movement.type === 'in'">
                  <span class="qty-in">{{ movement.quantity }}</span><span class="unit">{{ movement.batch.medicine.unit }}</span>
                </template>
              </td>
              <td class="number-cell" data-label="Keluar">
                <template v-if="movement.type === 'out'">
                  <span class="qty-out">{{ movement.quantity }}</span><span class="unit">{{ movement.batch.medicine.unit }}</span>
                </template>
              </td>
              <td v-if="showBalance" class="number-cell" data-label="Sisa">
                <span class="qty-strong">{{ balances.get(movement.id) }}</span>
              </td>
              <td data-label="Petugas">{{ movement.user?.name ?? '-' }}</td>
              <td class="cell-muted notes-cell" data-label="Catatan">{{ movement.notes || '-' }}</td>
            </tr>
            <tr v-if="!isLoading && movements.length === 0">
              <td :colspan="showBalance ? 8 : 7" class="empty-row">{{ emptyMessage }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import http from '../api/http'
import AppIcon from '../components/AppIcon.vue'
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

// Saldo hanya benar jika semua transaksi tampil; filter tipe memutus urutannya.
const showBalance = computed(() => type.value === '')

// Sisa setelah tiap transaksi, dihitung mundur dari stok batch saat ini
// (daftar sudah urut terbaru dulu).
const balances = computed(() => {
  const running = new Map()
  const result = new Map()
  for (const movement of movements.value) {
    const batchId = movement.batch.id
    const balance = running.has(batchId) ? running.get(batchId) : movement.batch.current_quantity
    result.set(movement.id, balance)
    running.set(batchId, movement.type === 'in' ? balance - movement.quantity : balance + movement.quantity)
  }
  return result
})

const emptyMessage = computed(() => {
  if (type.value === 'in') return 'Belum ada transaksi obat masuk.'
  if (type.value === 'out') return 'Belum ada transaksi obat keluar.'
  return 'Belum ada transaksi.'
})

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

<style scoped>
.history-table th:nth-child(4),
.history-table td:nth-child(4) {
  border-left: 1px solid var(--line);
}

.history-table th:nth-child(5),
.history-table td:nth-child(5) {
  border-right: 1px solid var(--line);
}

.in-cell {
  background: rgb(227 240 230 / 45%);
}

.time-cell {
  white-space: nowrap;
}

.notes-cell {
  max-width: 28ch;
}
</style>
