<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">OLT Devices</h1>
      <router-link
        to="/devices/olt/create"
        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
      >
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add OLT
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
            placeholder="OLT name, IP address..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOlts"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
          <select
            v-model="filters.vendor"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOlts"
          >
            <option value="">All Vendors</option>
            <option value="huawei">Huawei</option>
            <option value="zte">ZTE</option>
            <option value="fiberhome">FiberHome</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Connection</label>
          <select
            v-model="filters.connection"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOlts"
          >
            <option value="">All</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
          <select
            v-model="filters.per_page"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadOlts"
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
        <p class="text-sm text-gray-600">Total OLTs</p>
        <p class="text-2xl font-bold text-gray-900">{{ summary.total }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Online</p>
        <p class="text-2xl font-bold text-green-600">{{ summary.online }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Total ONUs</p>
        <p class="text-2xl font-bold text-blue-600">{{ summary.total_onus }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Total Ports</p>
        <p class="text-2xl font-bold text-purple-600">{{ summary.total_ports }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      <p class="mt-4 text-gray-600">Loading OLTs...</p>
    </div>

    <!-- OLT Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OLT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ports</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ONUs</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Connection</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="olt in olts" :key="olt.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <router-link :to="`/devices/olt/${olt.id}`" class="text-sm font-medium text-primary-600 hover:text-primary-900">
                {{ olt.name }}
              </router-link>
              <p class="text-xs text-gray-500 mt-1">{{ olt.vendor }}</p>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ olt.ip_address }}</div>
              <div class="text-xs text-gray-500">{{ olt.mgmt_type || 'SSH' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ olt.model || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ olt.port_count || 0 }}</div>
              <div class="text-xs text-gray-500">PON ports</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-blue-600">{{ olt.onu_count || 0 }}</div>
              <div class="text-xs text-gray-500">devices</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <span class="h-3 w-3 rounded-full mr-2"
                  :class="olt.is_online ? 'bg-green-400 animate-pulse' : 'bg-gray-300'"></span>
                <span class="text-sm font-medium" :class="olt.is_online ? 'text-green-600' : 'text-gray-500'">
                  {{ olt.is_online ? 'Online' : 'Offline' }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="getStatusClass(olt.status)">
                {{ olt.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <router-link
                :to="`/devices/olt/${olt.id}`"
                class="text-primary-600 hover:text-primary-900 mr-3"
              >
                View
              </router-link>
              <router-link
                :to="`/devices/olt/${olt.id}/edit`"
                class="text-yellow-600 hover:text-yellow-900 mr-3"
              >
                Edit
              </router-link>
              <button
                @click="deleteOlt(olt)"
                class="text-red-600 hover:text-red-900"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="olts.length === 0">
            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
              No OLTs found
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

const olts = ref([])
const loading = ref(false)

const filters = ref({
  search: '',
  status: '',
  vendor: '',
  connection: '',
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
  total_onus: 0,
  total_ports: 0,
})

let searchTimeout = null

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadOlts()
  }, 500)
}

const loadOlts = async () => {
  loading.value = true

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: filters.value.per_page,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.vendor) params.vendor = filters.value.vendor

    const response = await deviceAPI.listOlts(params)
    
    olts.value = response.data.data || response.data

    // Mock online status
    olts.value.forEach(olt => {
      olt.is_online = olt.status === 'active' && Math.random() > 0.15
      olt.onu_count = Math.floor(Math.random() * 50 + 10)
      olt.port_count = olt.port_count || 16
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

    // Apply connection filter
    if (filters.value.connection) {
      olts.value = olts.value.filter(o => 
        filters.value.connection === 'online' ? o.is_online : !o.is_online
      )
    }

    // Calculate summary
    summary.value = {
      total: olts.value.length,
      online: olts.value.filter(o => o.is_online).length,
      total_onus: olts.value.reduce((sum, o) => sum + (o.onu_count || 0), 0),
      total_ports: olts.value.reduce((sum, o) => sum + (o.port_count || 0), 0),
    }
  } catch (err) {
    console.error('Load OLTs error:', err)
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  loadOlts()
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

const deleteOlt = async (olt) => {
  if (!confirm(`Delete OLT "${olt.name}"? This action cannot be undone.`)) return

  try {
    await deviceAPI.deleteOlt(olt.id)
    alert('OLT deleted successfully!')
    loadOlts()
  } catch (err) {
    alert('Failed to delete OLT: ' + (err.response?.data?.message || err.message))
  }
}

onMounted(() => {
  loadOlts()
})
</script>
