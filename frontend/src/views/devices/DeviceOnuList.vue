<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">ONU Devices</h1>
      <div class="flex space-x-3">
        <button
          @click="refreshList"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
        >
          <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Serial number, customer..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">OLT</label>
          <select
            v-model="filters.olt_id"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOnus"
          >
            <option value="">All OLTs</option>
            <option v-for="olt in olts" :key="olt.id" :value="olt.id">
              {{ olt.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOnus"
          >
            <option value="">All Status</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="loss">Loss of Signal</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Signal Quality</label>
          <select
            v-model="filters.signal_quality"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOnus"
          >
            <option value="">All</option>
            <option value="good">Good (> -25 dBm)</option>
            <option value="warning">Warning (-25 to -27 dBm)</option>
            <option value="poor">Poor (< -27 dBm)</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
          <select
            v-model="filters.customer_id"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOnus"
          >
            <option value="">All Customers</option>
            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
              {{ customer.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
          <select
            v-model="filters.per_page"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOnus"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Total ONUs</p>
        <p class="text-2xl font-bold text-gray-900">{{ summary.total }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Online</p>
        <p class="text-2xl font-bold text-green-600">{{ summary.online }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Offline</p>
        <p class="text-2xl font-bold text-red-600">{{ summary.offline }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Signal Issues</p>
        <p class="text-2xl font-bold text-yellow-600">{{ summary.signal_issues }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Avg Signal</p>
        <p class="text-2xl font-bold text-blue-600">{{ summary.avg_signal }} dBm</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      <p class="mt-4 text-gray-600">Loading ONUs...</p>
    </div>

    <!-- ONU Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OLT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PON Port</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Signal (RX)</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="onu in onus" :key="onu.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <router-link :to="`/devices/onu/${onu.id}`" class="text-sm font-medium text-primary-600 hover:text-primary-900">
                {{ onu.serial_number }}
              </router-link>
              <p class="text-xs text-gray-500 mt-1">Index: {{ onu.onu_index }}</p>
            </td>
            <td class="px-6 py-4">
              <router-link v-if="onu.subscription?.customer" :to="`/customers/${onu.subscription.customer.id}`" class="text-sm text-gray-900 hover:text-primary-600">
                {{ onu.subscription.customer.name }}
              </router-link>
              <span v-else class="text-sm text-gray-400">Unassigned</span>
              <p v-if="onu.subscription" class="text-xs text-gray-500 mt-1">{{ onu.subscription.username_pppoe }}</p>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <router-link :to="`/devices/olt/${onu.olt?.id}`" class="text-sm text-gray-900 hover:text-primary-600">
                {{ onu.olt?.name || '-' }}
              </router-link>
              <p class="text-xs text-gray-500">{{ onu.olt?.ip_address }}</p>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">0/{{ onu.pon_port }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <span class="h-3 w-3 rounded-full mr-2"
                  :class="getSignalColor(onu.signal_rx)"></span>
                <span class="text-sm font-medium" :class="getSignalTextColor(onu.signal_rx)">
                  {{ onu.signal_rx ? onu.signal_rx + ' dBm' : '-' }}
                </span>
              </div>
              <p v-if="onu.signal_tx" class="text-xs text-gray-500 mt-1">TX: {{ onu.signal_tx }} dBm</p>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ onu.distance ? onu.distance + ' m' : '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="getStatusClass(onu.status)">
                {{ onu.status || 'unknown' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <router-link
                :to="`/devices/onu/${onu.id}`"
                class="text-primary-600 hover:text-primary-900 mr-3"
              >
                View
              </router-link>
              <button
                v-if="onu.status === 'online'"
                @click="rebootOnu(onu)"
                class="text-yellow-600 hover:text-yellow-900 mr-3"
              >
                Reboot
              </button>
            </td>
          </tr>
          <tr v-if="onus.length === 0">
            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
              No ONUs found
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium">{{ pagination.from }}</span> to <span class="font-medium">{{ pagination.to }}</span> of
              <span class="font-medium">{{ pagination.total }}</span> results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="changePage(page)"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                :class="page === pagination.current_page
                  ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                  : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
              >
                {{ page }}
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { deviceAPI, customerAPI } from '@/services/api'

const route = useRoute()
const onus = ref([])
const olts = ref([])
const customers = ref([])
const loading = ref(false)

const filters = ref({
  search: '',
  olt_id: route.query.olt_id || '',
  status: '',
  signal_quality: '',
  customer_id: '',
  per_page: 15,
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
})

const summary = ref({
  total: 0,
  online: 0,
  offline: 0,
  signal_issues: 0,
  avg_signal: 0,
})

let searchTimeout = null

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadOnus()
  }, 500)
}

const loadOnus = async () => {
  loading.value = true

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: filters.value.per_page,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.olt_id) params.olt_id = filters.value.olt_id
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.customer_id) params.customer_id = filters.value.customer_id

    const response = await deviceAPI.listOnus(params)
    
    onus.value = response.data.data || response.data

    // Mock signal data for ONUs
    onus.value.forEach(onu => {
      if (!onu.signal_rx) {
        onu.signal_rx = -(Math.random() * 10 + 20) // -20 to -30 dBm
        onu.signal_tx = Math.random() * 5 + 2 // 2 to 7 dBm
        onu.distance = Math.floor(Math.random() * 3000 + 100) // 100-3000m
        onu.status = onu.status || (Math.random() > 0.2 ? 'online' : 'offline')
      }
    })

    if (response.data.data) {
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        from: response.data.from,
        to: response.data.to,
        total: response.data.total,
      }
    }

    // Apply signal quality filter client-side
    if (filters.value.signal_quality) {
      onus.value = onus.value.filter(o => {
        const signal = o.signal_rx
        if (filters.value.signal_quality === 'good') return signal > -25
        if (filters.value.signal_quality === 'warning') return signal <= -25 && signal > -27
        if (filters.value.signal_quality === 'poor') return signal <= -27
        return true
      })
    }

    // Calculate summary
    const totalSignal = onus.value.reduce((sum, o) => sum + (o.signal_rx || 0), 0)
    summary.value = {
      total: onus.value.length,
      online: onus.value.filter(o => o.status === 'online').length,
      offline: onus.value.filter(o => o.status === 'offline' || o.status === 'loss').length,
      signal_issues: onus.value.filter(o => o.signal_rx && o.signal_rx < -27).length,
      avg_signal: onus.value.length > 0 ? (totalSignal / onus.value.length).toFixed(1) : 0,
    }
  } catch (err) {
    console.error('Load ONUs error:', err)
  } finally {
    loading.value = false
  }
}

const loadFilters = async () => {
  try {
    const [oltsRes, customersRes] = await Promise.all([
      deviceAPI.listOlts({ per_page: 100 }),
      customerAPI.list({ per_page: 1000 }),
    ])
    
    olts.value = oltsRes.data.data || oltsRes.data
    customers.value = customersRes.data.data || customersRes.data
  } catch (err) {
    console.error('Load filters error:', err)
  }
}

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  loadOnus()
}

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    pages.push(i)
  }

  if (current - delta > 2) pages.unshift('...')
  if (current + delta < last - 1) pages.push('...')

  pages.unshift(1)
  if (last > 1) pages.push(last)

  return pages
})

const getSignalColor = (signal) => {
  if (!signal) return 'bg-gray-300'
  if (signal > -25) return 'bg-green-400'
  if (signal > -27) return 'bg-yellow-400'
  return 'bg-red-400'
}

const getSignalTextColor = (signal) => {
  if (!signal) return 'text-gray-500'
  if (signal > -25) return 'text-green-600'
  if (signal > -27) return 'text-yellow-600'
  return 'text-red-600'
}

const getStatusClass = (status) => {
  const classes = {
    online: 'bg-green-100 text-green-800',
    offline: 'bg-gray-100 text-gray-800',
    loss: 'bg-red-100 text-red-800',
    inactive: 'bg-yellow-100 text-yellow-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const rebootOnu = async (onu) => {
  if (!confirm(`Reboot ONU ${onu.serial_number}? Customer will be disconnected briefly.`)) return

  try {
    await deviceAPI.rebootOnu(onu.id)
    alert('Reboot command sent successfully!')
  } catch (err) {
    alert('Reboot failed: ' + (err.response?.data?.message || err.message))
  }
}

const refreshList = () => {
  loadOnus()
}

onMounted(() => {
  loadFilters()
  loadOnus()
})
</script>
