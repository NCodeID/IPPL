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
    {
      path: '/cashier/dashboard',
      name: 'CashierDashboard',
      component: () => import('@/views/cashier/DashboardView.vue'),
      meta: { requiresAuth: true, role: 'cashier' },
    },
    {
      path: '/runner/dashboard',
      name: 'RunnerDashboard',
      component: () => import('@/views/runner/DashboardView.vue'),
      meta: { requiresAuth: true, role: 'runner' },
    },
    {
      path: '/cashier/tables',
      name: 'CashierTables',
      component: () => import('@/views/cashier/TableView.vue'),
      meta: { requiresAuth: true, role: 'cashier' },
    },
    {
      path: '/cashier/orders',
      name: 'CashierNewOrder',
      component: () => import('@/views/cashier/NewOrderView.vue'),
      meta: { requiresAuth: true, role: 'cashier' },
    },
    {
      path: '/cashier/track-orders',
      name: 'CashierOrders',
      component: () => import('@/views/cashier/TrackOrdersView.vue'),
      meta: { requiresAuth: true, role: 'cashier' },
    },
    {
      path: '/cashier/billsm',
      name: 'CashierBillsm',
      component: () => import('@/views/cashier/BillSummaryView.vue'),
      meta: { requiresAuth: true, role: 'cashier' },
    },
    {
      path: '/cashier/bills',
      name: 'CashierBills',
      component: () => import('@/views/cashier/CheckoutListView.vue'),
      meta: { requiresAuth: true, role: 'cashier' },
    },
    {
      path: '/cashier/payment',
      name: 'CashierPayment',
      component: () => import('@/views/cashier/PaymentView.vue'),
      meta: { requiresAuth: true, role: 'cashier' },
    },
    {
      path: '/cashier/payment/success',
      name: 'CashierPaymentSuccess',
      component: () => import('@/views/cashier/PaymentSuccessView.vue'),
      meta: { requiresAuth: true, role: 'cashier' },
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
      if (authStore.userRole === 'cashier') {
        return { path: '/cashier/dashboard' }
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
    if (authStore.userRole === 'cashier') {
      return { path: '/cashier/dashboard' }
    }
  }
})

export default router

