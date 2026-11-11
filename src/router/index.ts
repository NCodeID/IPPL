import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/login',
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/admin/dashboard',
      name: 'AdminDashboard',
      component: () => import('@/views/admin/DashboardView.vue'),
      meta: { requiresAuth: true, role: 'admin' },
    },
    {
      path: '/accountant/dashboard',
      name: 'AccountantDashboard',
      component: () => import('@/views/accountant/DashboardView.vue'),
      meta: { requiresAuth: true, role: 'accountant' },
    },
  ],
})

// ---------------------------------------------------------------------------
// Navigation guard
// ---------------------------------------------------------------------------
router.beforeEach((to) => {
  const authStore = useAuthStore()

  // Route requires authentication
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      return { path: '/login' }
    }

    // Check role if specified
    if (to.meta.role && to.meta.role !== authStore.userRole) {
      // Redirect to the user's own dashboard
      if (authStore.userRole === 'admin') {
        return { path: '/admin/dashboard' }
      }
      if (authStore.userRole === 'accountant') {
        return { path: '/accountant/dashboard' }
      }
      // Fallback: unknown role → back to login
      return { path: '/login' }
    }
  }

  // Guest-only route (e.g. login) — redirect authenticated users away
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    if (authStore.userRole === 'admin') {
      return { path: '/admin/dashboard' }
    }
    if (authStore.userRole === 'accountant') {
      return { path: '/accountant/dashboard' }
    }
  }
})

export default router

