<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center">
        <router-link to="/subscriptions" class="text-gray-600 hover:text-gray-900 mr-4">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">Subscription Details</h1>
      </div>
      
      <!-- Action Buttons -->
      <div class="flex space-x-3">
        <button
          v-if="subscription.status === 'pending'"
          @click="provisionSubscription"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
        >
          ⚡ Provision
        </button>
        
        <button
          v-if="subscription.status === 'active'"
          @click="suspendSubscription"
          class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
        >
          ⏸️ Suspend
        </button>
        
        <button
          v-if="subscription.status === 'suspended'"
          @click="unsuspendSubscription"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          ▶️ Unsuspend
        </button>
        
        <button
          v-if="subscription.is_online"
          @click="resetSession"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
        >
          🔄 Reset Session
        </button>
        
        <button
          v-if="subscription.status !== 'terminated'"
          @click="terminateSubscription"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
        >
          ❌ Terminate
        </button>
      </div>
    </div>

    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="subscription.id" class="space-y-6">
      <!-- Status Banner -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">{{ subscription.customer?.name }}</h2>
              <p class="text-sm text-gray-500">{{ subscription.service?.name }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium"
              :class="getStatusClass(subscription.status)">
              {{ subscription.status?.toUpperCase() }}
            </span>
          </div>
          <div class="text-right">
            <div class="flex items-center justify-end mb-1">
              <span class="h-3 w-3 rounded-full mr-2"
                :class="subscription.is_online ? 'bg-green-400 animate-pulse' : 'bg-gray-300'"></span>
              <span class="text-sm font-medium" :class="subscription.is_online ? 'text-green-600' : 'text-gray-500'">
                {{ subscription.is_online ? 'ONLINE' : 'OFFLINE' }}
              </span>
            </div>
            <p class="text-xs text-gray-500">Connection Status</p>
          </div>
        </div>
      </div>

      <!-- PPPoE Credentials -->
      <div class="bg-gradient-to-r from-primary-50 to-blue-50 rounded-lg shadow p-6 border-2 border-primary-200">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">🔐 PPPoE Credentials</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-white rounded-lg p-4">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Username</label>
            <div class="flex items-center justify-between">
              <code class="text-sm font-mono text-gray-900">{{ subscription.username_pppoe }}</code>
              <button @click="copyToClipboard(subscription.username_pppoe)" class="text-primary-600 hover:text-primary-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </button>
            </div>
          </div>
          <div class="bg-white rounded-lg p-4">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Password</label>
            <div class="flex items-center justify-between">
              <code class="text-sm font-mono text-gray-900">{{ showPassword ? subscription.password_pppoe : '••••••••••••' }}</code>
              <button @click="showPassword = !showPassword" class="text-primary-600 hover:text-primary-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Subscription Info -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Information</h2>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Service Package</dt>
              <dd class="text-sm font-medium text-gray-900">{{ subscription.service?.name }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Bandwidth</dt>
              <dd class="text-sm font-medium text-gray-900">
                ⬆️ {{ subscription.service?.speed_up }}M / ⬇️ {{ subscription.service?.speed_down }}M
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Monthly Price</dt>
              <dd class="text-sm font-medium text-gray-900">Rp {{ formatCurrency(subscription.service?.price) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">IP Type</dt>
              <dd class="text-sm font-medium text-gray-900 uppercase">{{ subscription.ip_type }}</dd>
            </div>
            <div v-if="subscription.ip_static" class="flex justify-between">
              <dt class="text-sm text-gray-500">Static IP</dt>
              <dd class="text-sm font-medium text-gray-900">{{ subscription.ip_static }}</dd>
            </div>
            <div v-if="subscription.vlan_id" class="flex justify-between">
              <dt class="text-sm text-gray-500">VLAN ID</dt>
              <dd class="text-sm font-medium text-gray-900">{{ subscription.vlan_id }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500">Start Date</dt>
              <dd class="text-sm font-medium text-gray-900">{{ formatDate(subscription.start_date) }}</dd>
            </div>
            <div v-if="subscription.end_date" class="flex justify-between">
              <dt class="text-sm text-gray-500">End Date</dt>
              <dd class="text-sm font-medium text-red-600">{{ formatDate(subscription.end_date) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Device Binding -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Device Binding</h2>
          <dl class="space-y-3">
            <div>
              <dt class="text-sm text-gray-500 mb-1">Customer</dt>
              <dd class="text-sm font-medium text-gray-900">
                <router-link :to="`/customers/${subscription.customer?.id}`" class="text-primary-600 hover:text-primary-800">
                  {{ subscription.customer?.name }} ({{ subscription.customer?.code }})
                </router-link>
              </dd>
            </div>
            <div v-if="subscription.router">
              <dt class="text-sm text-gray-500 mb-1">🖥️ Router</dt>
              <dd class="text-sm font-medium text-gray-900">
                {{ subscription.router.name }}<br>
                <span class="text-xs text-gray-500">{{ subscription.router.ip_address }}</span>
              </dd>
            </div>
            <div v-if="subscription.olt">
              <dt class="text-sm text-gray-500 mb-1">📡 OLT</dt>
              <dd class="text-sm font-medium text-gray-900">
                {{ subscription.olt.name }}<br>
                <span class="text-xs text-gray-500">{{ subscription.olt.ip_address }}</span>
              </dd>
            </div>
            <div v-if="subscription.onu">
              <dt class="text-sm text-gray-500 mb-1">📶 ONU</dt>
              <dd class="text-sm font-medium text-gray-900">
                SN: {{ subscription.onu.serial_number }}<br>
                <span class="text-xs text-gray-500">
                  Port: {{ subscription.onu.pon_port }} / Index: {{ subscription.onu.onu_index }}
                </span><br>
                <span v-if="subscription.onu.signal_rx" class="text-xs"
                  :class="subscription.onu.signal_rx > -25 ? 'text-green-600' : 'text-red-600'">
                  Signal: {{ subscription.onu.signal_rx }} dBm
                </span>
              </dd>
            </div>
            <div v-if="!subscription.router && !subscription.olt">
              <p class="text-sm text-gray-400">No devices bound yet</p>
            </div>
          </dl>
        </div>
      </div>

      <!-- Recent Invoices -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Recent Invoices</h2>
        </div>
        <div v-if="subscription.invoices?.length" class="divide-y divide-gray-200">
          <div v-for="invoice in subscription.invoices.slice(0, 5)" :key="invoice.id" class="p-6 flex items-center justify-between">
            <div>
              <router-link :to="`/invoices/${invoice.id}`" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                {{ invoice.invoice_number }}
              </router-link>
              <p class="text-sm text-gray-500 mt-1">
                {{ formatDate(invoice.period_start) }} - {{ formatDate(invoice.period_end) }}
              </p>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">Rp {{ formatCurrency(invoice.total) }}</p>
              <span class="text-xs px-2 py-1 rounded-full" :class="getInvoiceStatusClass(invoice.status)">
                {{ invoice.status }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No invoices yet
        </div>
      </div>

      <!-- Notes -->
      <div v-if="subscription.notes" class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">📝 Notes</h2>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ subscription.notes }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { subscriptionAPI } from '@/services/api'

const route = useRoute()
const router = useRouter()

const subscription = ref({})
const loading = ref(false)
const showPassword = ref(false)

const loadSubscription = async () => {
  loading.value = true
  try {
    const response = await subscriptionAPI.get(route.params.id)
    subscription.value = response.data
    
    // Mock online status (will be real from RADIUS sessions later)
    subscription.value.is_online = subscription.value.status === 'active' && Math.random() > 0.3
  } catch (err) {
    console.error('Load subscription error:', err)
  } finally {
    loading.value = false
  }
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    suspended: 'bg-red-100 text-red-800',
    terminated: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getInvoiceStatusClass = (status) => {
  const classes = {
    paid: 'bg-green-100 text-green-800',
    unpaid: 'bg-yellow-100 text-yellow-800',
    overdue: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text)
  alert('Copied to clipboard!')
}

const provisionSubscription = async () => {
  if (!confirm('Provision this subscription? This will activate the service on network devices.')) return

  try {
    await subscriptionAPI.provision(subscription.value.id)
    alert('Subscription provisioned successfully!')
    loadSubscription()
  } catch (err) {
    alert('Failed to provision: ' + (err.response?.data?.message || err.message))
  }
}

const suspendSubscription = async () => {
  if (!confirm('Suspend this subscription? Customer will lose internet access.')) return

  try {
    await subscriptionAPI.suspend(subscription.value.id)
    alert('Subscription suspended successfully!')
    loadSubscription()
  } catch (err) {
    alert('Failed to suspend: ' + (err.response?.data?.message || err.message))
  }
}

const unsuspendSubscription = async () => {
  if (!confirm('Unsuspend this subscription? Service will be restored.')) return

  try {
    await subscriptionAPI.unsuspend(subscription.value.id)
    alert('Subscription unsuspended successfully!')
    loadSubscription()
  } catch (err) {
    alert('Failed to unsuspend: ' + (err.response?.data?.message || err.message))
  }
}

const resetSession = async () => {
  if (!confirm('Reset PPPoE session? Customer will be disconnected briefly.')) return

  try {
    await subscriptionAPI.resetSession(subscription.value.id)
    alert('Session reset requested!')
  } catch (err) {
    alert('Failed to reset session: ' + (err.response?.data?.message || err.message))
  }
}

const terminateSubscription = async () => {
  if (!confirm('TERMINATE this subscription? This action cannot be undone!')) return

  try {
    await subscriptionAPI.terminate(subscription.value.id)
    alert('Subscription terminated!')
    router.push('/subscriptions')
  } catch (err) {
    alert('Failed to terminate: ' + (err.response?.data?.message || err.message))
  }
}

onMounted(() => {
  loadSubscription()
})
</script>
