import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from '../api/auth'
import DashboardView from '../views/DashboardView.vue'
import HistoryView from '../views/HistoryView.vue'
import LoginView from '../views/LoginView.vue'
import MedicinesView from '../views/MedicinesView.vue'
import StockInView from '../views/StockInView.vue'
import StockOutView from '../views/StockOutView.vue'

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
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true },
    },
    {
      path: '/medicines',
      name: 'medicines',
      component: MedicinesView,
      meta: { requiresAuth: true },
    },
    {
      path: '/stock-in',
      name: 'stock-in',
      component: StockInView,
      meta: { requiresAuth: true },
    },
    {
      path: '/stock-out',
      name: 'stock-out',
      component: StockOutView,
      meta: { requiresAuth: true },
    },
    {
      path: '/history',
      name: 'history',
      component: HistoryView,
      meta: { requiresAuth: true },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'dashboard' },
    },
  ],
})

router.beforeEach((to) => {
  const token = getToken()

  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }

  if (to.name === 'login' && token) {
    return { name: 'dashboard' }
  }
})

export default router
