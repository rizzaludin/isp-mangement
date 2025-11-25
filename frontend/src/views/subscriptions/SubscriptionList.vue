<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Subscriptions</h1>
      <router-link
        to="/subscriptions/create"
        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
      >
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Subscription
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
            placeholder="Username, customer..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadSubscriptions"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="suspended">Suspended</option>
            <option value="terminated">Terminated</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
          <select
            v-model="filters.customer_id"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadSubscriptions"
          >
            <option value="">All Customers</option>
            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
              {{ customer.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Connection</label>
          <select
            v-model="filters.connection"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadSubscriptions"
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
            @change="loadSubscriptions"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      <p class="mt-4 text-gray-600">Loading subscriptions...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 rounded-lg p-4 mb-6">
      <div class="flex">
        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <p class="ml-3 text-sm text-red-800">{{ error }}</p>
      </div>
    </div>

    <!-- Subscription Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PPPoE</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Connection</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="subscription in subscriptions" :key="subscription.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <router-link :to="`/customers/${subscription.customer?.id}`" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                {{ subscription.customer?.name }}
              </router-link>
              <p class="text-sm text-gray-500">{{ subscription.customer?.code }}</p>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900">{{ subscription.service?.name }}</div>
              <div class="text-sm text-gray-500">{{ subscription.service?.speed_up }}M / {{ subscription.service?.speed_down }}M</div>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-mono text-gray-900">{{ subscription.username_pppoe }}</div>
              <div class="text-xs text-gray-500">{{ subscription.ip_type }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <div v-if="subscription.router">{{ subscription.router.name }}</div>
              <div v-if="subscription.olt" class="text-xs text-gray-500">{{ subscription.olt.name }}</div>
              <div v-if="!subscription.router && !subscription.olt" class="text-gray-400">-</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="getStatusClass(subscription.status)">
                {{ subscription.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <span class="h-2 w-2 rounded-full mr-2"
                  :class="subscription.is_online ? 'bg-green-400' : 'bg-gray-300'"></span>
                <span class="text-sm text-gray-900">
                  {{ subscription.is_online ? 'Online' : 'Offline' }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex justify-end space-x-2">
                <router-link
                  :to="`/subscriptions/${subscription.id}`"
                  class="text-primary-600 hover:text-primary-900"
                  title="View Details"
                >
                  View
                </router-link>
                
                <button
                  v-if="subscription.status === 'pending'"
                  @click="provisionSubscription(subscription)"
                  class="text-green-600 hover:text-green-900"
                  title="Provision"
                >
                  Provision
                </button>
                
                <button
                  v-if="subscription.status === 'active'"
                  @click="suspendSubscription(subscription)"
                  class="text-yellow-600 hover:text-yellow-900"
                  title="Suspend"
                >
                  Suspend
                </button>
                
                <button
                  v-if="subscription.status === 'suspended'"
                  @click="unsuspendSubscription(subscription)"
                  class="text-blue-600 hover:text-blue-900"
                  title="Unsuspend"
                >
                  Unsuspend
                </button>
                
                <button
                  v-if="subscription.is_online"
                  @click="resetSession(subscription)"
                  class="text-purple-600 hover:text-purple-900"
                  title="Reset Session"
                >
                  Reset
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="subscriptions.length === 0">
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
              No subscriptions found
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
import { subscriptionAPI, customerAPI } from '@/services/api'

const subscriptions = ref([])
const customers = ref([])
const loading = ref(false)
const error = ref(null)

const filters = ref({
  search: '',
  status: '',
  customer_id: '',
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

let searchTimeout = null

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadSubscriptions()
  }, 500)
}

const loadSubscriptions = async () => {
  loading.value = true
  error.value = null

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: filters.value.per_page,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.customer_id) params.customer_id = filters.value.customer_id

    const response = await subscriptionAPI.list(params)
    
    subscriptions.value = response.data.data
    
    // Set online status (mock for now, will be updated with real data)
    subscriptions.value = subscriptions.value.map(sub => ({
      ...sub,
      is_online: sub.status === 'active' && Math.random() > 0.3 // Mock: 70% online
    }))
    
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      from: response.data.from,
      to: response.data.to,
      total: response.data.total,
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load subscriptions'
    console.error('Load subscriptions error:', err)
  } finally {
    loading.value = false
  }
}

const loadCustomers = async () => {
  try {
    const response = await customerAPI.list({ per_page: 1000 })
    customers.value = response.data.data
  } catch (err) {
    console.error('Load customers error:', err)
  }
}

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  loadSubscriptions()
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
    pending: 'bg-yellow-100 text-yellow-800',
    suspended: 'bg-red-100 text-red-800',
    terminated: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const provisionSubscription = async (subscription) => {
  if (!confirm(`Provision subscription for ${subscription.customer?.name}?`)) return

  try {
    await subscriptionAPI.provision(subscription.id)
    subscription.status = 'active'
    alert('Subscription provisioned successfully!')
  } catch (err) {
    alert('Failed to provision: ' + (err.response?.data?.message || err.message))
  }
}

const suspendSubscription = async (subscription) => {
  if (!confirm(`Suspend subscription for ${subscription.customer?.name}?`)) return

  try {
    await subscriptionAPI.suspend(subscription.id)
    subscription.status = 'suspended'
    subscription.is_online = false
    alert('Subscription suspended successfully!')
  } catch (err) {
    alert('Failed to suspend: ' + (err.response?.data?.message || err.message))
  }
}

const unsuspendSubscription = async (subscription) => {
  if (!confirm(`Unsuspend subscription for ${subscription.customer?.name}?`)) return

  try {
    await subscriptionAPI.unsuspend(subscription.id)
    subscription.status = 'active'
    alert('Subscription unsuspended successfully!')
  } catch (err) {
    alert('Failed to unsuspend: ' + (err.response?.data?.message || err.message))
  }
}

const resetSession = async (subscription) => {
  if (!confirm(`Reset PPPoE session for ${subscription.customer?.name}?`)) return

  try {
    await subscriptionAPI.resetSession(subscription.id)
    alert('Session reset requested!')
  } catch (err) {
    alert('Failed to reset session: ' + (err.response?.data?.message || err.message))
  }
}

onMounted(() => {
  loadSubscriptions()
  loadCustomers()
})
</script>
