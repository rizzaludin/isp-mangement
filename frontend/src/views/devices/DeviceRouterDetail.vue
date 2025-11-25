<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center">
        <router-link to="/devices/routers" class="text-gray-600 hover:text-gray-900 mr-4">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">Router Details</h1>
      </div>
      
      <div class="flex space-x-3">
        <button
          @click="testConnection"
          :disabled="testing"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          {{ testing ? '⏳ Testing...' : '🔍 Test Connection' }}
        </button>
        <router-link
          :to="`/devices/routers/${router.id}/edit`"
          class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
        >
          ✏️ Edit
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="router.id" class="space-y-6">
      <!-- Status Banner -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ router.name }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ router.vendor }} - {{ router.type || 'Standard' }}</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <div class="flex items-center justify-end mb-1">
                <span class="h-4 w-4 rounded-full mr-2"
                  :class="router.is_online ? 'bg-green-400 animate-pulse' : 'bg-gray-300'"></span>
                <span class="text-lg font-semibold" :class="router.is_online ? 'text-green-600' : 'text-gray-500'">
                  {{ router.is_online ? 'ONLINE' : 'OFFLINE' }}
                </span>
              </div>
              <p class="text-xs text-gray-500">Connection Status</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium"
              :class="getStatusClass(router.status)">
              {{ router.status?.toUpperCase() }}
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Stats (if available) -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">CPU Usage</p>
          <p class="text-2xl font-bold text-gray-900">{{ router.cpu_usage || '-' }}%</p>
          <p class="text-xs text-gray-500 mt-1">{{ router.cpu_count || '-' }} cores</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">Memory Usage</p>
          <p class="text-2xl font-bold text-gray-900">{{ router.memory_usage || '-' }}%</p>
          <p class="text-xs text-gray-500 mt-1">{{ formatBytes(router.memory_total) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">Uptime</p>
          <p class="text-2xl font-bold text-gray-900">{{ router.uptime_days || '-' }}</p>
          <p class="text-xs text-gray-500 mt-1">days</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">Active Users</p>
          <p class="text-2xl font-bold text-gray-900">{{ router.active_users || '-' }}</p>
          <p class="text-xs text-gray-500 mt-1">PPPoE connections</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Router Information -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Router Information</h2>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">IP Address</dt>
              <dd class="text-sm font-medium text-gray-900">{{ router.ip_address }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">API Port</dt>
              <dd class="text-sm font-medium text-gray-900">{{ router.api_port }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">SSH Port</dt>
              <dd class="text-sm font-medium text-gray-900">{{ router.ssh_port || 22 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Username</dt>
              <dd class="text-sm font-medium text-gray-900">{{ router.username }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Vendor</dt>
              <dd class="text-sm font-medium text-gray-900 capitalize">{{ router.vendor }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Type</dt>
              <dd class="text-sm font-medium text-gray-900">{{ router.type || '-' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Location</dt>
              <dd class="text-sm font-medium text-gray-900">{{ router.location || '-' }}</dd>
            </div>
            <div v-if="router.last_ping_at" class="flex justify-between">
              <dt class="text-sm text-gray-500">Last Ping</dt>
              <dd class="text-sm font-medium text-gray-900">{{ formatDateTime(router.last_ping_at) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Connection Test Results -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Connection Test</h2>
          
          <div v-if="testResult" class="space-y-4">
            <div class="rounded-lg p-4"
              :class="testResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
              <p class="font-semibold mb-2"
                :class="testResult.success ? 'text-green-900' : 'text-red-900'">
                {{ testResult.success ? '✅ Connection Successful' : '❌ Connection Failed' }}
              </p>
              <p class="text-sm"
                :class="testResult.success ? 'text-green-700' : 'text-red-700'">
                {{ testResult.message }}
              </p>
            </div>
            
            <div v-if="testResult.details" class="text-sm text-gray-600">
              <p><strong>Response Time:</strong> {{ testResult.details.response_time }}ms</p>
              <p><strong>Router OS:</strong> {{ testResult.details.router_os || 'N/A' }}</p>
              <p><strong>Board Name:</strong> {{ testResult.details.board_name || 'N/A' }}</p>
            </div>
          </div>
          
          <div v-else class="text-center text-gray-500 py-8">
            <p>Click "Test Connection" to check router accessibility</p>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div v-if="router.description" class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Description</h2>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ router.description }}</p>
      </div>

      <!-- Recent Monitoring Logs -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Recent Monitoring Logs</h2>
        </div>
        <div v-if="router.logs?.length" class="divide-y divide-gray-200">
          <div v-for="log in router.logs.slice(0, 10)" :key="log.id" class="p-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ log.event }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(log.created_at) }}</p>
              </div>
              <span class="px-2 py-1 text-xs font-medium rounded-full"
                :class="log.level === 'error' ? 'bg-red-100 text-red-800' : 
                        log.level === 'warning' ? 'bg-yellow-100 text-yellow-800' : 
                        'bg-blue-100 text-blue-800'">
                {{ log.level }}
              </span>
            </div>
            <p v-if="log.details" class="text-sm text-gray-600 mt-2">{{ log.details }}</p>
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No monitoring logs available
        </div>
      </div>

      <!-- Active Subscriptions -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">Active Subscriptions ({{ router.subscriptions?.length || 0 }})</h2>
          <router-link to="/subscriptions?router_id=" class="text-sm text-primary-600 hover:text-primary-800">
            View All →
          </router-link>
        </div>
        <div v-if="router.subscriptions?.length" class="divide-y divide-gray-200">
          <div v-for="sub in router.subscriptions.slice(0, 5)" :key="sub.id" class="p-6 flex items-center justify-between">
            <div>
              <router-link :to="`/subscriptions/${sub.id}`" class="text-sm font-medium text-primary-600 hover:text-primary-900">
                {{ sub.customer?.name }}
              </router-link>
              <p class="text-xs text-gray-500 mt-1">{{ sub.username_pppoe }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">{{ sub.service?.name }}</p>
              <span class="text-xs px-2 py-1 rounded-full"
                :class="sub.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                {{ sub.status }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No active subscriptions on this router
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { deviceAPI } from '@/services/api'

const route = useRoute()
const router = ref({})
const loading = ref(false)
const testing = ref(false)
const testResult = ref(null)

const loadRouter = async () => {
  loading.value = true
  try {
    const response = await deviceAPI.getRouter(route.params.id)
    router.value = response.data
    
    // Mock online status
    router.value.is_online = router.value.status === 'active' && Math.random() > 0.2
    
    // Mock stats (will be real from monitoring later)
    router.value.cpu_usage = Math.floor(Math.random() * 80 + 10)
    router.value.cpu_count = 4
    router.value.memory_usage = Math.floor(Math.random() * 70 + 20)
    router.value.memory_total = 4 * 1024 * 1024 * 1024 // 4GB
    router.value.uptime_days = Math.floor(Math.random() * 90 + 1)
    router.value.active_users = Math.floor(Math.random() * 50 + 10)
  } catch (err) {
    console.error('Load router error:', err)
  } finally {
    loading.value = false
  }
}

const testConnection = async () => {
  testing.value = true
  testResult.value = null

  try {
    const response = await deviceAPI.testRouterConnection(router.value.id)
    testResult.value = response.data
    router.value.is_online = response.data.success
    router.value.last_ping_at = new Date().toISOString()
  } catch (err) {
    testResult.value = {
      success: false,
      message: err.response?.data?.message || err.message,
    }
    router.value.is_online = false
  } finally {
    testing.value = false
  }
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatBytes = (bytes) => {
  if (!bytes) return '-'
  const gb = bytes / (1024 * 1024 * 1024)
  return gb.toFixed(1) + ' GB'
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadRouter()
})
</script>
