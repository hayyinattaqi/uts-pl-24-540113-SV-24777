<template>
  <main class="page-content">
    <header>
      <p class="eyebrow">INVENTARIS KLINIK</p>
      <h1 class="page-heading">Dashboard</h1>
      <p class="page-description">Pantau batch yang hampir kedaluwarsa, sudah kedaluwarsa, dan obat dengan stok menipis.</p>
    </header>

    <div v-if="loadError" class="alert alert-error" role="alert">
      <span>{{ loadError }}</span>
      <button class="text-link" type="button" @click="loadDashboard">Coba lagi</button>
    </div>

    <div class="summary-grid">
      <div class="summary-card summary-warning">
        <span class="summary-label">Hampir kedaluwarsa</span>
        <strong>{{ isLoading ? '…' : dashboard.near_expiry.length }}</strong>
        <span class="summary-caption">batch, ≤ 90 hari lagi</span>
      </div>
      <div class="summary-card summary-danger">
        <span class="summary-label">Kedaluwarsa</span>
        <strong>{{ isLoading ? '…' : dashboard.expired.length }}</strong>
        <span class="summary-caption">batch masih bersisa stok</span>
      </div>
      <div class="summary-card summary-info">
        <span class="summary-label">Stok menipis</span>
        <strong>{{ isLoading ? '…' : dashboard.low_stock.length }}</strong>
        <span class="summary-caption">obat ≤ stok minimum</span>
      </div>
    </div>

    <section class="panel" aria-labelledby="near-expiry-title">
      <div class="panel-header">
        <h2 id="near-expiry-title">Batch hampir kedaluwarsa</h2>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th scope="col">Obat</th>
              <th scope="col">Batch</th>
              <th scope="col">Kedaluwarsa</th>
              <th scope="col">Sisa hari</th>
              <th scope="col" class="number-cell">Stok</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="batch in dashboard.near_expiry" :key="batch.id">
              <td class="cell-strong">{{ batch.medicine.name }}</td>
              <td>{{ batch.batch_number }}</td>
              <td>{{ formatDate(batch.expiry_date) }}</td>
              <td><span class="badge badge-warning">{{ daysUntil(batch.expiry_date) }} hari</span></td>
              <td class="number-cell">{{ batch.current_quantity }} {{ batch.medicine.unit }}</td>
            </tr>
            <tr v-if="!isLoading && dashboard.near_expiry.length === 0">
              <td colspan="5" class="empty-row">Tidak ada batch yang hampir kedaluwarsa.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel" aria-labelledby="expired-title">
      <div class="panel-header">
        <h2 id="expired-title">Batch kedaluwarsa</h2>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th scope="col">Obat</th>
              <th scope="col">Batch</th>
              <th scope="col">Kedaluwarsa</th>
              <th scope="col" class="number-cell">Stok tersisa</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="batch in dashboard.expired" :key="batch.id">
              <td class="cell-strong">{{ batch.medicine.name }}</td>
              <td>{{ batch.batch_number }}</td>
              <td><span class="badge badge-danger">{{ formatDate(batch.expiry_date) }}</span></td>
              <td class="number-cell">{{ batch.current_quantity }} {{ batch.medicine.unit }}</td>
            </tr>
            <tr v-if="!isLoading && dashboard.expired.length === 0">
              <td colspan="4" class="empty-row">Tidak ada batch kedaluwarsa yang masih bersisa stok.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel" aria-labelledby="low-stock-title">
      <div class="panel-header">
        <h2 id="low-stock-title">Obat stok menipis</h2>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th scope="col">Kode</th>
              <th scope="col">Nama obat</th>
              <th scope="col" class="number-cell">Total stok</th>
              <th scope="col" class="number-cell">Stok minimum</th>
              <th scope="col" class="number-cell">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="medicine in dashboard.low_stock" :key="medicine.id">
              <td>{{ medicine.code }}</td>
              <td class="cell-strong">{{ medicine.name }}</td>
              <td class="number-cell"><span class="badge badge-warning">{{ medicine.total_stock }} {{ medicine.unit }}</span></td>
              <td class="number-cell">{{ medicine.min_stock }}</td>
              <td class="number-cell">
                <RouterLink
                  class="shortcut-link"
                  :to="{ name: 'stock-in', query: { medicine_id: medicine.id } }"
                  :aria-label="`Tambah stok ${medicine.name}`"
                >
                  ＋ Tambah stok
                </RouterLink>
              </td>
            </tr>
            <tr v-if="!isLoading && dashboard.low_stock.length === 0">
              <td colspan="5" class="empty-row">Semua obat di atas stok minimum.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import http from '../api/http'
import { daysUntil, formatDate } from '../utils/format'

const dashboard = reactive({ near_expiry: [], expired: [], low_stock: [] })
const isLoading = ref(false)
const loadError = ref('')

async function loadDashboard() {
  isLoading.value = true
  loadError.value = ''

  try {
    const response = await http.get('/dashboard')
    Object.assign(dashboard, response.data.data)
  } catch {
    loadError.value = 'Data dashboard tidak dapat dimuat. Periksa koneksi, lalu coba lagi.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDashboard)
</script>

<style scoped>
.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.summary-card {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 1.15rem 1.3rem;
  border: 1px solid #e3ebee;
  border-left-width: 4px;
  border-radius: 0.9rem;
  background: #fff;
}

.summary-card strong {
  color: #193c43;
  font-size: 2rem;
  line-height: 1.1;
}

.summary-label {
  color: #4e666c;
  font-size: 0.85rem;
  font-weight: 700;
}

.summary-caption {
  color: #8a9a9e;
  font-size: 0.8rem;
}

.summary-warning {
  border-left-color: #e0a526;
}

.summary-danger {
  border-left-color: #c34848;
}

.summary-info {
  border-left-color: #16866e;
}

.shortcut-link {
  display: inline-block;
  padding: 0.35rem 0.7rem;
  border: 1px solid #cbe9dc;
  border-radius: 0.5rem;
  background: #f1fbf6;
  color: #116d5a;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
}

.shortcut-link:hover {
  background: #dff4ee;
}

.text-link {
  padding: 0;
  border: 0;
  background: none;
  color: inherit;
  font-weight: 700;
  text-decoration: underline;
}

@media (max-width: 640px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
