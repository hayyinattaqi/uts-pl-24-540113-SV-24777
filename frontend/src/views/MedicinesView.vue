<template>
  <main class="page-content">
    <h1 class="page-heading">Obat</h1>
    <p class="page-description">Halaman inventaris obat klinik.</p>
    <button class="button button-secondary" type="button" :disabled="loading" @click="handleLogout">
      {{ loading ? 'Keluar...' : 'Logout' }}
    </button>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { logout } from '../api/auth'

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
