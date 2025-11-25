import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue'),
      },
      {
        path: 'customers',
        name: 'Customers',
        component: () => import('@/views/customers/CustomerList.vue'),
      },
      {
        path: 'customers/create',
        name: 'CustomerCreate',
        component: () => import('@/views/customers/CustomerForm.vue'),
      },
      {
        path: 'customers/:id',
        name: 'CustomerDetail',
        component: () => import('@/views/customers/CustomerDetail.vue'),
      },
      {
        path: 'customers/:id/edit',
        name: 'CustomerEdit',
        component: () => import('@/views/customers/CustomerForm.vue'),
      },
      {
        path: 'subscriptions',
        name: 'Subscriptions',
        component: () => import('@/views/subscriptions/SubscriptionList.vue'),
      },
      {
        path: 'subscriptions/create',
        name: 'SubscriptionCreate',
        component: () => import('@/views/subscriptions/SubscriptionForm.vue'),
      },
      {
        path: 'subscriptions/:id',
        name: 'SubscriptionDetail',
        component: () => import('@/views/subscriptions/SubscriptionDetail.vue'),
      },
      {
        path: 'invoices',
        name: 'Invoices',
        component: () => import('@/views/invoices/InvoiceList.vue'),
      },
      {
        path: 'invoices/:id',
        name: 'InvoiceDetail',
        component: () => import('@/views/invoices/InvoiceDetail.vue'),
      },
      {
        path: 'payments',
        name: 'Payments',
        component: () => import('@/views/payments/PaymentList.vue'),
      },
      {
        path: 'services',
        name: 'Services',
        component: () => import('@/views/services/ServiceList.vue'),
      },
      {
        path: 'devices/routers',
        name: 'Routers',
        component: () => import('@/views/devices/DeviceRouterList.vue'),
      },
      {
        path: 'devices/routers/create',
        name: 'RouterCreate',
        component: () => import('@/views/devices/DeviceRouterForm.vue'),
      },
      {
        path: 'devices/routers/:id',
        name: 'RouterDetail',
        component: () => import('@/views/devices/DeviceRouterDetail.vue'),
      },
      {
        path: 'devices/routers/:id/edit',
        name: 'RouterEdit',
        component: () => import('@/views/devices/DeviceRouterForm.vue'),
      },
      {
        path: 'devices/olt',
        name: 'OLTs',
        component: () => import('@/views/devices/DeviceOltList.vue'),
      },
      {
        path: 'devices/olt/create',
        name: 'OltCreate',
        component: () => import('@/views/devices/DeviceOltForm.vue'),
      },
      {
        path: 'devices/olt/:id',
        name: 'OltDetail',
        component: () => import('@/views/devices/DeviceOltDetail.vue'),
      },
      {
        path: 'devices/olt/:id/edit',
        name: 'OltEdit',
        component: () => import('@/views/devices/DeviceOltForm.vue'),
      },
      {
        path: 'devices/onu',
        name: 'ONUs',
        component: () => import('@/views/devices/DeviceOnuList.vue'),
      },
      {
        path: 'devices/onu/:id',
        name: 'OnuDetail',
        component: () => import('@/views/devices/DeviceOnuDetail.vue'),
      },
      {
        path: 'profile',
        name: 'Profile',
        component: () => import('@/views/Profile.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' })
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router
