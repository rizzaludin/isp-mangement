<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">MikroTik Routers</h1>
      <router-link
        to="/devices/routers/create"
        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
      >
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Router
      </router-link>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Router name, IP address..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadRouters"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Connection</label>
          <select
            v-model="filters.connection"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadRouters"
          >
            <option value="">All</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
          <select
            v-model="filters.vendor"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadRouters"
          >
            <option value="">All Vendors</option>
            <option value="mikrotik">MikroTik</option>
            <option value="cisco">Cisco</option>
            <option value="ubiquiti">Ubiquiti</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
          <select
            v-model="filters.per_page"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadRouters"
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Total Routers</p>
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
        <p class="text-sm text-gray-600">Maintenance</p>
        <p class="text-2xl font-bold text-yellow-600">{{ summary.maintenance }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      <p class="mt-4 text-gray-600">Loading routers...</p>
    </div>

    <!-- Router Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Router</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Connection</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="router in routers" :key="router.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <router-link :to="`/devices/routers/${router.id}`" class="text-sm font-medium text-primary-600 hover:text-primary-900">
                {{ router.name }}
              </router-link>
              <p class="text-xs text-gray-500 mt-1">{{ router.vendor }}</p>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ router.ip_address }}</div>
              <div class="text-xs text-gray-500">Port: {{ router.api_port }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ router.type || 'Standard' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <span class="h-3 w-3 rounded-full mr-2"
                  :class="router.is_online ? 'bg-green-400 animate-pulse' : 'bg-gray-300'"></span>
                <span class="text-sm font-medium" :class="router.is_online ? 'text-green-600' : 'text-gray-500'">
                  {{ router.is_online ? 'Online' : 'Offline' }}
                </span>
              </div>
              <div v-if="router.last_ping_at" class="text-xs text-gray-500 mt-1">
                Last: {{ formatTime(router.last_ping_at) }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="getStatusClass(router.status)">
                {{ router.status }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900">{{ router.location || '-' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="pingRouter(router)"
                :disabled="pinging[router.id]"
                class="text-blue-600 hover:text-blue-900 mr-3"
              >
                {{ pinging[router.id] ? '⏳' : '📡' }} Ping
              </button>
              <router-link
                :to="`/devices/routers/${router.id}`"
                class="text-primary-600 hover:text-primary-900 mr-3"
              >
                View
              </router-link>
              <router-link
                :to="`/devices/routers/${router.id}/edit`"
                class="text-yellow-600 hover:text-yellow-900 mr-3"
              >
                Edit
              </router-link>
              <button
                @click="deleteRouter(router)"
                class="text-red-600 hover:text-red-900"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="routers.length === 0">
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
              No routers found
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
import { deviceAPI } from '@/services/api'

const routers = ref([])
const loading = ref(false)
const pinging = ref({})

const filters = ref({
  search: '',
  status: '',
  connection: '',
  vendor: '',
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
  maintenance: 0,
})

let searchTimeout = null

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadRouters()
  }, 500)
}

const loadRouters = async () => {
  loading.value = true

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: filters.value.per_page,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.vendor) params.vendor = filters.value.vendor

    const response = await deviceAPI.listRouters(params)
    
    routers.value = response.data.data || response.data

    // Mock online status (will be real from monitoring later)
    routers.value.forEach(router => {
      router.is_online = router.status === 'active' && Math.random() > 0.2
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

    // Apply connection filter client-side (if not filtered by backend)
    if (filters.value.connection) {
      routers.value = routers.value.filter(r => 
        filters.value.connection === 'online' ? r.is_online : !r.is_online
      )
    }

    // Calculate summary
    summary.value = {
      total: routers.value.length,
      online: routers.value.filter(r => r.is_online).length,
      offline: routers.value.filter(r => !r.is_online).length,
      maintenance: routers.value.filter(r => r.status === 'maintenance').length,
    }
  } catch (err) {
    console.error('Load routers error:', err)
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  loadRouters()
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

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatTime = (date) => {
  return new Date(date).toLocaleString('id-ID', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const pingRouter = async (router) => {
  pinging.value[router.id] = true

  try {
    const response = await deviceAPI.testRouterConnection(router.id)
    router.is_online = response.data.success
    router.last_ping_at = new Date().toISOString()
    alert(response.data.success ? '✅ Router is reachable!' : '❌ Router is not reachable')
  } catch (err) {
    router.is_online = false
    alert('❌ Ping failed: ' + (err.response?.data?.message || err.message))
  } finally {
    pinging.value[router.id] = false
  }
}

const deleteRouter = async (router) => {
  if (!confirm(`Delete router "${router.name}"? This action cannot be undone.`)) return

  try {
    await deviceAPI.deleteRouter(router.id)
    alert('Router deleted successfully!')
    loadRouters()
  } catch (err) {
    alert('Failed to delete router: ' + (err.response?.data?.message || err.message))
  }
}

onMounted(() => {
  loadRouters()
})
</script>
