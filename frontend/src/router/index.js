import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from '../api/auth'
import LoginView from '../views/LoginView.vue'
import MedicinesView from '../views/MedicinesView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/',
      name: 'medicines',
      component: MedicinesView,
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach((to) => {
  const token = getToken()

  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }

  if (to.name === 'login' && token) {
    return { name: 'medicines' }
  }
})

export default router
