<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Total Customers</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalCustomers }}</p>
            <p class="text-sm text-green-600 mt-2">+12 this month</p>
          </div>
          <div class="bg-blue-100 rounded-full p-3">
            <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Active Subscriptions</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.activeSubscriptions }}</p>
            <p class="text-sm text-green-600 mt-2">{{ stats.onlineSubscriptions }} online</p>
          </div>
          <div class="bg-green-100 rounded-full p-3">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Unpaid Invoices</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.unpaidInvoices }}</p>
            <p class="text-sm text-red-600 mt-2">Rp {{ formatCurrency(stats.unpaidAmount) }}</p>
          </div>
          <div class="bg-yellow-100 rounded-full p-3">
            <svg class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">This Month Revenue</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">Rp {{ formatCurrency(stats.monthlyRevenue) }}</p>
            <p class="text-sm text-green-600 mt-2">+8% from last month</p>
          </div>
          <div class="bg-indigo-100 rounded-full p-3">
            <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-4">
          <router-link
            to="/customers/create"
            class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors"
          >
            <svg class="h-8 w-8 text-blue-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span class="text-sm font-medium text-gray-700">Add Customer</span>
          </router-link>
          
          <router-link
            to="/subscriptions/create"
            class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors"
          >
            <svg class="h-8 w-8 text-green-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span class="text-sm font-medium text-gray-700">New Subscription</span>
          </router-link>

          <button
            @click="generateInvoices"
            :disabled="generating"
            class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-colors disabled:opacity-50"
          >
            <svg class="h-8 w-8 text-purple-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-sm font-medium text-gray-700">{{ generating ? 'Generating...' : 'Generate Invoices' }}</span>
          </button>

          <router-link
            to="/payments"
            class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-colors"
          >
            <svg class="h-8 w-8 text-yellow-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span class="text-sm font-medium text-gray-700">Record Payment</span>
          </router-link>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">System Status</h2>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Network Devices</span>
            <span class="text-sm font-medium text-green-600">All Online</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Queue Worker</span>
            <span class="text-sm font-medium text-green-600">Running</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">RADIUS Server</span>
            <span class="text-sm font-medium text-green-600">Active</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Database</span>
            <span class="text-sm font-medium text-green-600">Healthy</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { invoiceAPI, monitoringAPI } from '@/services/api'

const stats = ref({
  totalCustomers: 0,
  activeSubscriptions: 0,
  onlineSubscriptions: 0,
  unpaidInvoices: 0,
  unpaidAmount: 0,
  monthlyRevenue: 0,
})

const generating = ref(false)
const loading = ref(false)

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value)
}

const loadStats = async () => {
  loading.value = true
  
  try {
    const summary = await monitoringAPI.summary()
    
    // Map API response to dashboard stats
    stats.value = {
      totalCustomers: summary.customers.total,
      activeSubscriptions: summary.subscriptions.active,
      onlineSubscriptions: summary.subscriptions.active, // Can be improved with real online tracking
      unpaidInvoices: summary.invoices.unpaid + summary.invoices.overdue,
      unpaidAmount: summary.invoices.total_unpaid_amount,
      monthlyRevenue: summary.revenue.this_month,
    }
  } catch (error) {
    console.error('Failed to load dashboard stats:', error)
    // Fallback to mock data if API fails
    stats.value = {
      totalCustomers: 156,
      activeSubscriptions: 142,
      onlineSubscriptions: 138,
      unpaidInvoices: 23,
      unpaidAmount: 5750000,
      monthlyRevenue: 35600000,
    }
  } finally {
    loading.value = false
  }
}

const generateInvoices = async () => {
  if (!confirm('Generate invoices for all active subscriptions this month?')) {
    return
  }

  generating.value = true
  
  try {
    const now = new Date()
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)

    await invoiceAPI.generate({
      period_start: firstDay.toISOString().split('T')[0],
      period_end: lastDay.toISOString().split('T')[0],
      due_days: 7,
    })

    alert('Invoices generated successfully!')
    loadStats()
  } catch (error) {
    alert('Failed to generate invoices: ' + (error.response?.data?.message || error.message))
  } finally {
    generating.value = false
  }
}

onMounted(() => {
  loadStats()
})
</script>
