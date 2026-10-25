<template>
  <main class="page-content">
    <header class="page-header">
      <div>
        <h1 class="page-heading">Dashboard</h1>
        <p class="page-description">Batch dan obat yang perlu ditindaklanjuti per {{ todayLabel() }}.</p>
      </div>
    </header>

    <div v-if="loadError" class="alert alert-error" role="alert">
      <AppIcon name="alert" />
      <span>{{ loadError }}</span>
      <button class="text-button" type="button" @click="loadDashboard">Coba lagi</button>
    </div>

    <section class="card" aria-labelledby="near-expiry-title">
      <div class="card-head card-head-warn">
        <h2 id="near-expiry-title">Batch hampir kedaluwarsa</h2>
        <span class="card-count" :class="{ 'has-items': dashboard.near_expiry.length }"><strong>{{ isLoading ? '…' : dashboard.near_expiry.length }}</strong> batch</span>
      </div>
      <p class="card-meta">Kedaluwarsa dalam 90 hari ke depan dan masih bersisa stok. Keluarkan lebih dulu.</p>
      <div class="table-scroll">
        <table class="data-table stack-table">
          <thead>
            <tr>
              <th scope="col">Obat</th>
              <th scope="col">Batch</th>
              <th scope="col">Kedaluwarsa</th>
              <th scope="col">Status</th>
              <th scope="col" class="number-cell">Sisa stok</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="batch in dashboard.near_expiry" :key="batch.id">
              <td class="cell-strong stack-title">{{ batch.medicine.name }}</td>
              <td class="code-tag" data-label="Batch">{{ batch.batch_number }}</td>
              <td data-label="Kedaluwarsa">{{ formatDate(batch.expiry_date) }}</td>
              <td data-label="Status"><span class="status status-warn">{{ daysUntil(batch.expiry_date) }} hari lagi</span></td>
              <td class="number-cell" data-label="Sisa stok">
                <span class="qty-strong">{{ batch.current_quantity }}</span><span class="unit">{{ batch.medicine.unit }}</span>
              </td>
            </tr>
            <tr v-if="!isLoading && dashboard.near_expiry.length === 0">
              <td colspan="5" class="empty-row">Tidak ada batch yang kedaluwarsa dalam 90 hari ke depan.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div class="dashboard-pair">
      <section class="card" aria-labelledby="expired-title">
        <div class="card-head card-head-danger">
          <h2 id="expired-title">Batch kedaluwarsa</h2>
          <span class="card-count" :class="{ 'has-items': dashboard.expired.length }"><strong>{{ isLoading ? '…' : dashboard.expired.length }}</strong> batch</span>
        </div>
        <p class="card-meta">Sudah lewat tanggal dan tidak bisa dikeluarkan.</p>
        <div class="table-scroll">
          <table class="data-table stack-table">
            <thead>
              <tr>
                <th scope="col">Obat</th>
                <th scope="col">Batch</th>
                <th scope="col">Kedaluwarsa</th>
                <th scope="col" class="number-cell">Sisa stok</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="batch in dashboard.expired" :key="batch.id">
                <td class="cell-strong stack-title">{{ batch.medicine.name }}</td>
                <td class="code-tag" data-label="Batch">{{ batch.batch_number }}</td>
                <td data-label="Kedaluwarsa"><span class="status status-danger">{{ formatDate(batch.expiry_date) }}</span></td>
                <td class="number-cell" data-label="Sisa stok">
                  <span class="qty-out">{{ batch.current_quantity }}</span><span class="unit">{{ batch.medicine.unit }}</span>
                </td>
              </tr>
              <tr v-if="!isLoading && dashboard.expired.length === 0">
                <td colspan="4" class="empty-row">Tidak ada batch kedaluwarsa yang masih bersisa stok.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card" aria-labelledby="low-stock-title">
        <div class="card-head">
          <h2 id="low-stock-title">Obat stok menipis</h2>
          <span class="card-count"><strong>{{ isLoading ? '…' : dashboard.low_stock.length }}</strong> obat</span>
        </div>
        <p class="card-meta">Total stok layak pakai sama dengan atau di bawah stok minimum.</p>
        <div class="table-scroll">
          <table class="data-table stack-table">
            <thead>
              <tr>
                <th scope="col">Obat</th>
                <th scope="col" class="number-cell">Stok / min.</th>
                <th scope="col" class="number-cell"><span class="visually-hidden">Aksi</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="medicine in dashboard.low_stock" :key="medicine.id">
                <td class="stack-title">
                  <span class="cell-strong">{{ medicine.name }}</span>
                  <span class="code-tag code-below">{{ medicine.code }}</span>
                </td>
                <td class="number-cell" data-label="Stok / min.">
                  <span class="qty-low">{{ medicine.total_stock }}</span>
                  <span class="unit">/ {{ medicine.min_stock }} {{ medicine.unit }}</span>
                </td>
                <td class="number-cell stack-actions">
                  <RouterLink
                    class="row-button"
                    :to="{ name: 'stock-in', query: { medicine_id: medicine.id } }"
                    :aria-label="`Tambah stok ${medicine.name}`"
                  >
                    <AppIcon name="plus" />
                    Tambah stok
                  </RouterLink>
                </td>
              </tr>
              <tr v-if="!isLoading && dashboard.low_stock.length === 0">
                <td colspan="3" class="empty-row">Semua obat di atas stok minimum.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppIcon from '../components/AppIcon.vue'
import http from '../api/http'
import { daysUntil, formatDate, todayLabel } from '../utils/format'

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
.card-meta {
  margin: 0;
}

.dashboard-pair {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0 1.5rem;
  align-items: start;
}

.code-below {
  display: block;
  font-size: 0.8rem;
}

.qty-low {
  color: var(--warn);
  font-weight: 750;
}

@media (max-width: 980px) {
  .dashboard-pair {
    grid-template-columns: 1fr;
  }
}
</style>
