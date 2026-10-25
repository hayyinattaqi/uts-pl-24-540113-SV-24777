<template>
  <header class="topbar">
    <RouterLink :to="{ name: 'dashboard' }" class="brand">
      <BrandMark />
      <span>Inventaris Klinik</span>
    </RouterLink>
    <nav ref="nav" class="main-nav" aria-label="Navigasi utama">
      <RouterLink v-for="link in links" :key="link.name" :to="{ name: link.name }" class="nav-link">
        {{ link.label }}
      </RouterLink>
    </nav>
    <button class="logout-button" type="button" :disabled="loading" @click="handleLogout">
      <AppIcon name="logout" />
      {{ loading ? 'Keluar...' : 'Keluar' }}
    </button>
  </header>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { logout } from '../api/auth'
import AppIcon from './AppIcon.vue'
import BrandMark from './BrandMark.vue'

const links = [
  { name: 'dashboard', label: 'Dashboard' },
  { name: 'medicines', label: 'Obat' },
  { name: 'stock-in', label: 'Obat Masuk' },
  { name: 'stock-out', label: 'Obat Keluar' },
  { name: 'history', label: 'Riwayat' },
]

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const nav = ref(null)

// Di layar sempit tab bisa digeser; pastikan tab aktif selalu terlihat.
async function revealActiveTab() {
  await nextTick()
  nav.value?.querySelector('.router-link-exact-active')?.scrollIntoView({ block: 'nearest', inline: 'nearest' })
}

watch(() => route.name, revealActiveTab)
onMounted(revealActiveTab)

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
