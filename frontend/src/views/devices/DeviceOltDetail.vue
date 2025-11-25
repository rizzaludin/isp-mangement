<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center">
        <router-link to="/devices/olt" class="text-gray-600 hover:text-gray-900 mr-4">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">OLT Details</h1>
      </div>
      
      <div class="flex space-x-3">
        <button
          @click="fetchOnus"
          :disabled="fetching"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
        >
          {{ fetching ? '⏳ Fetching...' : '📡 Fetch ONUs' }}
        </button>
        <router-link
          :to="`/devices/olt/${olt.id}/edit`"
          class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
        >
          ✏️ Edit
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="olt.id" class="space-y-6">
      <!-- Status Banner -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ olt.name }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ olt.vendor }} - {{ olt.model || 'N/A' }}</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <div class="flex items-center justify-end mb-1">
                <span class="h-4 w-4 rounded-full mr-2"
                  :class="olt.is_online ? 'bg-green-400 animate-pulse' : 'bg-gray-300'"></span>
                <span class="text-lg font-semibold" :class="olt.is_online ? 'text-green-600' : 'text-gray-500'">
                  {{ olt.is_online ? 'ONLINE' : 'OFFLINE' }}
                </span>
              </div>
              <p class="text-xs text-gray-500">Connection Status</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium"
              :class="getStatusClass(olt.status)">
              {{ olt.status?.toUpperCase() }}
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">Total ONUs</p>
          <p class="text-2xl font-bold text-blue-600">{{ olt.onu_count || 0 }}</p>
          <p class="text-xs text-gray-500 mt-1">registered devices</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">Online ONUs</p>
          <p class="text-2xl font-bold text-green-600">{{ olt.onu_online || 0 }}</p>
          <p class="text-xs text-gray-500 mt-1">active connections</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">PON Ports</p>
          <p class="text-2xl font-bold text-purple-600">{{ olt.port_count || 16 }}</p>
          <p class="text-xs text-gray-500 mt-1">total ports</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">Uptime</p>
          <p class="text-2xl font-bold text-gray-900">{{ olt.uptime_days || '-' }}</p>
          <p class="text-xs text-gray-500 mt-1">days</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- OLT Information -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">OLT Information</h2>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">IP Address</dt>
              <dd class="text-sm font-medium text-gray-900">{{ olt.ip_address }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Management Type</dt>
              <dd class="text-sm font-medium text-gray-900 uppercase">{{ olt.mgmt_type || 'SSH' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Port</dt>
              <dd class="text-sm font-medium text-gray-900">{{ olt.mgmt_port || 22 }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Username</dt>
              <dd class="text-sm font-medium text-gray-900">{{ olt.username }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Vendor</dt>
              <dd class="text-sm font-medium text-gray-900 capitalize">{{ olt.vendor }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Model</dt>
              <dd class="text-sm font-medium text-gray-900">{{ olt.model || '-' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Location</dt>
              <dd class="text-sm font-medium text-gray-900">{{ olt.location || '-' }}</dd>
            </div>
          </dl>
        </div>

        <!-- ONU Summary by Port -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">ONUs by PON Port</h2>
          <div class="space-y-2 max-h-64 overflow-y-auto">
            <div v-for="port in 8" :key="port" class="flex items-center justify-between py-2 border-b">
              <span class="text-sm font-medium text-gray-700">Port 0/{{ port }}</span>
              <span class="text-sm text-gray-900">{{ Math.floor(Math.random() * 16) }} ONUs</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div v-if="olt.description" class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Description</h2>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ olt.description }}</p>
      </div>

      <!-- Recent Logs -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Recent Activity Logs</h2>
        </div>
        <div v-if="olt.logs?.length" class="divide-y divide-gray-200">
          <div v-for="log in olt.logs.slice(0, 10)" :key="log.id" class="p-6">
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
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No activity logs available
        </div>
      </div>

      <!-- ONUs on this OLT -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">ONUs on this OLT ({{ olt.onus?.length || 0 }})</h2>
          <router-link :to="`/devices/onu?olt_id=${olt.id}`" class="text-sm text-primary-600 hover:text-primary-800">
            View All ONUs →
          </router-link>
        </div>
        <div v-if="olt.onus?.length" class="divide-y divide-gray-200">
          <div v-for="onu in olt.onus.slice(0, 10)" :key="onu.id" class="p-6 flex items-center justify-between">
            <div>
              <router-link :to="`/devices/onu/${onu.id}`" class="text-sm font-medium text-primary-600 hover:text-primary-900">
                {{ onu.serial_number }}
              </router-link>
              <p class="text-xs text-gray-500 mt-1">Port: 0/{{ onu.pon_port }} / Index: {{ onu.onu_index }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">{{ onu.customer?.name || '-' }}</p>
              <span class="text-xs px-2 py-1 rounded-full"
                :class="onu.status === 'online' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                {{ onu.status || 'unknown' }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No ONUs registered yet
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
const olt = ref({})
const loading = ref(false)
const fetching = ref(false)

const loadOlt = async () => {
  loading.value = true
  try {
    const response = await deviceAPI.getOlt(route.params.id)
    olt.value = response.data
    
    // Mock data
    olt.value.is_online = olt.value.status === 'active' && Math.random() > 0.1
    olt.value.onu_count = Math.floor(Math.random() * 80 + 20)
    olt.value.onu_online = Math.floor(olt.value.onu_count * 0.85)
    olt.value.uptime_days = Math.floor(Math.random() * 180 + 30)
  } catch (err) {
    console.error('Load OLT error:', err)
  } finally {
    loading.value = false
  }
}

const fetchOnus = async () => {
  fetching.value = true
  try {
    const response = await deviceAPI.fetchOnus(olt.value.id)
    alert(`Fetched ${response.data?.length || 0} ONUs from OLT`)
    loadOlt() // Reload to get updated data
  } catch (err) {
    alert('Fetch ONUs not implemented yet or failed')
  } finally {
    fetching.value = false
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
  loadOlt()
})
</script>
