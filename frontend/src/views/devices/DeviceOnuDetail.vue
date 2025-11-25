<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center">
        <router-link to="/devices/onu" class="text-gray-600 hover:text-gray-900 mr-4">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">ONU Details</h1>
      </div>
      
      <div class="flex space-x-3">
        <button
          v-if="onu.status === 'online'"
          @click="rebootOnu"
          :disabled="processing"
          class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 disabled:opacity-50"
        >
          {{ processing ? '⏳ Processing...' : '🔄 Reboot' }}
        </button>
        <button
          v-if="onu.status === 'online'"
          @click="deauthOnu"
          :disabled="processing"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
        >
          {{ processing ? '⏳ Processing...' : '❌ Deauth' }}
        </button>
        <button
          @click="refreshStatus"
          :disabled="refreshing"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          {{ refreshing ? '⏳ Refreshing...' : '🔄 Refresh Status' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="onu.id" class="space-y-6">
      <!-- Status Banner -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ onu.serial_number }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ onu.olt?.name }} - Port 0/{{ onu.pon_port }}</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <div class="flex items-center justify-end mb-1">
                <span class="h-4 w-4 rounded-full mr-2"
                  :class="onu.status === 'online' ? 'bg-green-400 animate-pulse' : 'bg-gray-300'"></span>
                <span class="text-lg font-semibold" 
                  :class="onu.status === 'online' ? 'text-green-600' : 'text-gray-500'">
                  {{ onu.status?.toUpperCase() || 'UNKNOWN' }}
                </span>
              </div>
              <p class="text-xs text-gray-500">Connection Status</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Signal Quality Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">RX Signal</p>
          <div class="flex items-center mt-2">
            <span class="h-4 w-4 rounded-full mr-2" :class="getSignalColor(onu.signal_rx)"></span>
            <p class="text-2xl font-bold" :class="getSignalTextColor(onu.signal_rx)">
              {{ onu.signal_rx ? onu.signal_rx.toFixed(2) + ' dBm' : '-' }}
            </p>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            {{ getSignalQuality(onu.signal_rx) }}
          </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">TX Power</p>
          <p class="text-2xl font-bold text-gray-900 mt-2">
            {{ onu.signal_tx ? onu.signal_tx.toFixed(2) + ' dBm' : '-' }}
          </p>
          <p class="text-xs text-gray-500 mt-1">Transmit power</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">Distance</p>
          <p class="text-2xl font-bold text-purple-600 mt-2">
            {{ onu.distance ? onu.distance + ' m' : '-' }}
          </p>
          <p class="text-xs text-gray-500 mt-1">
            {{ onu.distance ? (onu.distance / 1000).toFixed(2) + ' km' : 'Unknown' }}
          </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-sm text-gray-600">Uptime</p>
          <p class="text-2xl font-bold text-gray-900 mt-2">
            {{ onu.uptime_days || '-' }}
          </p>
          <p class="text-xs text-gray-500 mt-1">days online</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- ONU Information -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">ONU Information</h2>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Serial Number</dt>
              <dd class="text-sm font-medium text-gray-900">{{ onu.serial_number }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">ONU Index</dt>
              <dd class="text-sm font-medium text-gray-900">{{ onu.onu_index }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">PON Port</dt>
              <dd class="text-sm font-medium text-gray-900">0/{{ onu.pon_port }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">OLT</dt>
              <dd class="text-sm font-medium">
                <router-link :to="`/devices/olt/${onu.olt?.id}`" class="text-primary-600 hover:text-primary-900">
                  {{ onu.olt?.name }}
                </router-link>
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">MAC Address</dt>
              <dd class="text-sm font-medium text-gray-900">{{ onu.mac_address || '-' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Vendor</dt>
              <dd class="text-sm font-medium text-gray-900">{{ onu.vendor || '-' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Model</dt>
              <dd class="text-sm font-medium text-gray-900">{{ onu.model || '-' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Firmware</dt>
              <dd class="text-sm font-medium text-gray-900">{{ onu.firmware_version || '-' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Related Subscription -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Related Subscription</h2>
          
          <div v-if="onu.subscription" class="space-y-4">
            <div class="border-b pb-4">
              <h3 class="text-sm font-medium text-gray-500 mb-2">Customer</h3>
              <router-link :to="`/customers/${onu.subscription.customer?.id}`" 
                class="text-lg font-semibold text-primary-600 hover:text-primary-900">
                {{ onu.subscription.customer?.name }}
              </router-link>
              <p class="text-sm text-gray-600 mt-1">{{ onu.subscription.customer?.phone }}</p>
              <p class="text-sm text-gray-600">{{ onu.subscription.customer?.address }}</p>
            </div>
            
            <div>
              <h3 class="text-sm font-medium text-gray-500 mb-2">Service</h3>
              <p class="text-base font-semibold text-gray-900">{{ onu.subscription.service?.name }}</p>
              <p class="text-sm text-gray-600 mt-1">
                ⬆️ {{ onu.subscription.service?.speed_up }}M / ⬇️ {{ onu.subscription.service?.speed_down }}M
              </p>
              <p class="text-sm text-gray-600">
                Rp {{ formatCurrency(onu.subscription.service?.price) }}/month
              </p>
            </div>
            
            <div>
              <h3 class="text-sm font-medium text-gray-500 mb-2">PPPoE Credentials</h3>
              <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ onu.subscription.username_pppoe }}</code>
            </div>
            
            <div class="pt-4 border-t">
              <router-link :to="`/subscriptions/${onu.subscription.id}`" 
                class="text-sm text-primary-600 hover:text-primary-900 font-medium">
                View Full Subscription →
              </router-link>
            </div>
          </div>
          
          <div v-else class="text-center text-gray-500 py-8">
            <p>No subscription assigned to this ONU</p>
          </div>
        </div>
      </div>

      <!-- Signal History Chart (Placeholder) -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Signal History (Last 24 Hours)</h2>
        <div class="bg-gray-50 rounded-lg p-8 text-center text-gray-500">
          <p>Signal history chart will be displayed here</p>
          <p class="text-sm mt-2">Coming soon: Real-time signal monitoring</p>
        </div>
      </div>

      <!-- Recent Events -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Recent Events</h2>
        </div>
        <div v-if="onu.events?.length" class="divide-y divide-gray-200">
          <div v-for="event in onu.events.slice(0, 10)" :key="event.id" class="p-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ event.event }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(event.created_at) }}</p>
              </div>
              <span class="px-2 py-1 text-xs font-medium rounded-full"
                :class="event.type === 'error' ? 'bg-red-100 text-red-800' : 
                        event.type === 'warning' ? 'bg-yellow-100 text-yellow-800' : 
                        'bg-blue-100 text-blue-800'">
                {{ event.type }}
              </span>
            </div>
            <p v-if="event.details" class="text-sm text-gray-600 mt-2">{{ event.details }}</p>
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No recent events
        </div>
      </div>

      <!-- Technical Details -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Technical Details</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-xs text-gray-500">Temperature</p>
            <p class="text-sm font-medium text-gray-900">{{ onu.temperature ? onu.temperature + '°C' : '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Voltage</p>
            <p class="text-sm font-medium text-gray-900">{{ onu.voltage ? onu.voltage + 'V' : '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">TX Bias</p>
            <p class="text-sm font-medium text-gray-900">{{ onu.tx_bias ? onu.tx_bias + 'mA' : '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">CRC Errors</p>
            <p class="text-sm font-medium text-gray-900">{{ onu.crc_errors || '0' }}</p>
          </div>
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
const onu = ref({})
const loading = ref(false)
const processing = ref(false)
const refreshing = ref(false)

const loadOnu = async () => {
  loading.value = true
  try {
    const response = await deviceAPI.getOnu(route.params.id)
    onu.value = response.data
    
    // Mock data if not present
    if (!onu.value.signal_rx) {
      onu.value.signal_rx = -(Math.random() * 10 + 20)
      onu.value.signal_tx = Math.random() * 5 + 2
      onu.value.distance = Math.floor(Math.random() * 3000 + 100)
      onu.value.uptime_days = Math.floor(Math.random() * 90 + 1)
      onu.value.temperature = Math.floor(Math.random() * 20 + 40)
      onu.value.voltage = (Math.random() * 0.5 + 3.0).toFixed(2)
      onu.value.tx_bias = (Math.random() * 20 + 10).toFixed(1)
      onu.value.crc_errors = Math.floor(Math.random() * 100)
    }
  } catch (err) {
    console.error('Load ONU error:', err)
  } finally {
    loading.value = false
  }
}

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

const getSignalQuality = (signal) => {
  if (!signal) return 'Unknown'
  if (signal > -25) return 'Excellent'
  if (signal > -27) return 'Warning'
  return 'Poor - Check connection'
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value)
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

const refreshStatus = async () => {
  refreshing.value = true
  try {
    await loadOnu()
    alert('Status refreshed successfully!')
  } catch (err) {
    alert('Failed to refresh status')
  } finally {
    refreshing.value = false
  }
}

const rebootOnu = async () => {
  if (!confirm('Reboot this ONU? Customer will be disconnected briefly.')) return

  processing.value = true
  try {
    await deviceAPI.rebootOnu(onu.value.id)
    alert('Reboot command sent successfully!')
    setTimeout(() => loadOnu(), 2000)
  } catch (err) {
    alert('Reboot failed: ' + (err.response?.data?.message || err.message))
  } finally {
    processing.value = false
  }
}

const deauthOnu = async () => {
  if (!confirm('Deauthenticate this ONU? Customer will be disconnected.')) return

  processing.value = true
  try {
    await deviceAPI.deauthOnu(onu.value.id)
    alert('Deauth command sent successfully!')
    setTimeout(() => loadOnu(), 2000)
  } catch (err) {
    alert('Deauth failed: ' + (err.response?.data?.message || err.message))
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  loadOnu()
})
</script>
