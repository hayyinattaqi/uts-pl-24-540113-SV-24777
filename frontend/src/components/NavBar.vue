<template>
  <header class="topbar">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true">✚</span>
      <span>Inventaris Stok Obat Klinik</span>
    </div>
    <button class="button button-secondary" type="button" :disabled="loading" @click="handleLogout">
      {{ loading ? 'Keluar...' : 'Logout' }}
    </button>
  </header>
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
