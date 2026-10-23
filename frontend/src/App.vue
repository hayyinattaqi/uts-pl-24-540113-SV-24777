<template>
  <div class="app-shell">
    <NavBar v-if="showNavBar" />
    <RouterView />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import NavBar from './components/NavBar.vue'
import { getToken } from './api/auth'

const route = useRoute()
const showNavBar = computed(() => Boolean(getToken()) && route.name !== 'login')
</script>
