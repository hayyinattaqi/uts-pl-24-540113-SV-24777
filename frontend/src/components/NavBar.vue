<template>
  <header class="topbar">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true">✚</span>
      <span>Inventaris Klinik</span>
    </div>
    <nav class="main-nav" aria-label="Navigasi utama">
      <RouterLink v-for="link in links" :key="link.name" :to="{ name: link.name }" class="nav-link">
        {{ link.label }}
      </RouterLink>
    </nav>
    <button class="button button-secondary" type="button" :disabled="loading" @click="handleLogout">
      {{ loading ? 'Keluar...' : 'Logout' }}
    </button>
  </header>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { logout } from '../api/auth'

const links = [
  { name: 'dashboard', label: 'Dashboard' },
  { name: 'medicines', label: 'Obat' },
  { name: 'stock-in', label: 'Obat Masuk' },
  { name: 'stock-out', label: 'Obat Keluar' },
  { name: 'history', label: 'Riwayat' },
]

const router = useRouter()
const loading = ref(false)

async function handleLogout() {
  if (loading.value) return

  loading.value = true
  try {
    await logout()
  } catch (error) {
    console.error('Permintaan logout gagal:', error)
  } finally {
    await router.replace({ name: 'login' })
    loading.value = false
  }
}
</script>
