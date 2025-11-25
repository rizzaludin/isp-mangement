<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-white transform transition-transform duration-300 ease-in-out z-30">
      <div class="flex items-center justify-center h-16 border-b border-gray-800">
        <h1 class="text-xl font-bold">ISP Manager</h1>
      </div>
      
      <nav class="mt-6 px-4">
        <router-link
          v-for="item in navigation"
          :key="item.name"
          :to="item.to"
          class="flex items-center px-4 py-3 mb-2 rounded-lg hover:bg-gray-800 transition-colors"
          :class="{ 'bg-gray-800': $route.path === item.to || $route.path.startsWith(item.to + '/') }"
        >
          <component :is="item.icon" class="h-5 w-5 mr-3" />
          <span>{{ item.name }}</span>
        </router-link>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64">
      <!-- Top Navigation -->
      <div class="bg-white shadow-sm h-16 flex items-center justify-between px-6">
        <h2 class="text-xl font-semibold text-gray-800">{{ pageTitle }}</h2>
        
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-600">{{ authStore.user?.name }}</span>
          <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
            {{ authStore.user?.role_display }}
          </span>
          <button
            @click="handleLogout"
            class="text-gray-600 hover:text-gray-900 transition-colors"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Page Content -->
      <div class="p-6">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon,
  UsersIcon,
  WifiIcon,
  DocumentTextIcon,
  CreditCardIcon,
  CpuChipIcon,
  CogIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const navigation = [
  { name: 'Dashboard', to: '/', icon: HomeIcon },
  { name: 'Customers', to: '/customers', icon: UsersIcon },
  { name: 'Subscriptions', to: '/subscriptions', icon: WifiIcon },
  { name: 'Invoices', to: '/invoices', icon: DocumentTextIcon },
  { name: 'Payments', to: '/payments', icon: CreditCardIcon },
  { name: 'Services', to: '/services', icon: CogIcon },
  { name: 'Devices', to: '/devices/routers', icon: CpuChipIcon },
]

const pageTitle = computed(() => {
  return route.name || 'Dashboard'
})

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'Login' })
}
</script>
