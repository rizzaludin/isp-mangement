<template>
  <div>
    <div class="mb-6 flex items-center">
      <router-link to="/subscriptions" class="text-gray-600 hover:text-gray-900 mr-4">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit Subscription' : 'New Subscription' }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Customer Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
          <select
            v-model="form.customer_id"
            required
            :disabled="isEdit"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100"
            @change="onCustomerChange"
          >
            <option value="">Select Customer</option>
            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
              {{ customer.name }} ({{ customer.code }})
            </option>
          </select>
          <p v-if="selectedCustomer" class="mt-1 text-sm text-gray-500">
            Phone: {{ selectedCustomer.phone }} | Type: {{ selectedCustomer.type }}
          </p>
        </div>

        <!-- Service Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Service Package *</label>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="service in services"
              :key="service.id"
              @click="selectService(service)"
              class="border-2 rounded-lg p-4 cursor-pointer transition-all"
              :class="form.service_id === service.id 
                ? 'border-primary-500 bg-primary-50' 
                : 'border-gray-200 hover:border-primary-300'"
            >
              <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-gray-900">{{ service.name }}</h3>
                <input
                  type="radio"
                  :value="service.id"
                  v-model="form.service_id"
                  class="text-primary-600"
                />
              </div>
              <p class="text-sm text-gray-600 mb-2">
                ⬆️ {{ service.speed_up }}M / ⬇️ {{ service.speed_down }}M
              </p>
              <p class="text-lg font-bold text-primary-600">
                Rp {{ formatCurrency(service.price) }}
              </p>
              <p class="text-xs text-gray-500 mt-1">per {{ service.billing_cycle }}</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Router Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Router</label>
            <select
              v-model="form.router_id"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">Select Router (Optional)</option>
              <option v-for="router in routers" :key="router.id" :value="router.id">
                {{ router.name }} ({{ router.ip_address }})
              </option>
            </select>
          </div>

          <!-- OLT Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">OLT</label>
            <select
              v-model="form.olt_id"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="">Select OLT (Optional)</option>
              <option v-for="olt in olts" :key="olt.id" :value="olt.id">
                {{ olt.name }} ({{ olt.ip_address }})
              </option>
            </select>
          </div>

          <!-- IP Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">IP Type *</label>
            <select
              v-model="form.ip_type"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="dhcp">DHCP (Dynamic)</option>
              <option value="static">Static IP</option>
            </select>
          </div>

          <!-- Static IP (if selected) -->
          <div v-if="form.ip_type === 'static'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Static IP Address</label>
            <input
              v-model="form.ip_static"
              type="text"
              placeholder="192.168.1.100"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <!-- VLAN ID -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">VLAN ID</label>
            <input
              v-model.number="form.vlan_id"
              type="number"
              placeholder="100"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <!-- Start Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
            <input
              v-model="form.start_date"
              type="date"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder="Installation notes, special requirements..."
          ></textarea>
        </div>

        <!-- Generated Credentials (after creation) -->
        <div v-if="createdSubscription" class="bg-green-50 border border-green-200 rounded-lg p-4">
          <h3 class="font-semibold text-green-900 mb-2">✓ Subscription Created Successfully!</h3>
          <div class="space-y-2">
            <div>
              <span class="text-sm font-medium text-green-800">PPPoE Username:</span>
              <code class="ml-2 bg-white px-2 py-1 rounded text-sm">{{ createdSubscription.username_pppoe }}</code>
            </div>
            <div>
              <span class="text-sm font-medium text-green-800">PPPoE Password:</span>
              <code class="ml-2 bg-white px-2 py-1 rounded text-sm">{{ createdSubscription.password_pppoe }}</code>
            </div>
            <div class="mt-3 pt-3 border-t border-green-200">
              <router-link
                :to="`/subscriptions/${createdSubscription.id}`"
                class="text-green-700 hover:text-green-900 font-medium"
              >
                View Subscription Details →
              </router-link>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3">
          <router-link
            to="/subscriptions"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            {{ createdSubscription ? 'Back to List' : 'Cancel' }}
          </router-link>
          <button
            v-if="!createdSubscription"
            type="submit"
            :disabled="loading || !form.customer_id || !form.service_id"
            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? 'Creating...' : 'Create Subscription' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { subscriptionAPI, customerAPI, serviceAPI, deviceAPI } from '@/services/api'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => route.name === 'SubscriptionEdit')

const form = ref({
  customer_id: '',
  service_id: '',
  router_id: '',
  olt_id: '',
  onu_id: '',
  vlan_id: null,
  ip_static: '',
  ip_type: 'dhcp',
  start_date: new Date().toISOString().split('T')[0],
  notes: '',
})

const customers = ref([])
const services = ref([])
const routers = ref([])
const olts = ref([])
const loading = ref(false)
const error = ref(null)
const createdSubscription = ref(null)

const selectedCustomer = computed(() => {
  return customers.value.find(c => c.id === form.value.customer_id)
})

const loadData = async () => {
  try {
    const [customersRes, servicesRes, routersRes, oltsRes] = await Promise.all([
      customerAPI.list({ per_page: 1000, status: 'active' }),
      serviceAPI.list({ status: 'active' }),
      deviceAPI.listRouters({ status: 'active' }),
      deviceAPI.listOlts({ status: 'active' }),
    ])

    customers.value = customersRes.data.data
    services.value = servicesRes.data
    routers.value = routersRes.data
    olts.value = oltsRes.data

    // Pre-select customer if provided in query
    if (route.query.customer_id) {
      form.value.customer_id = parseInt(route.query.customer_id)
    }
  } catch (err) {
    error.value = 'Failed to load form data'
    console.error(err)
  }
}

const selectService = (service) => {
  form.value.service_id = service.id
}

const onCustomerChange = () => {
  // Can load customer-specific data here if needed
  console.log('Customer selected:', selectedCustomer.value)
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value)
}

const handleSubmit = async () => {
  loading.value = true
  error.value = null

  try {
    const payload = { ...form.value }
    
    // Remove empty fields
    if (!payload.router_id) delete payload.router_id
    if (!payload.olt_id) delete payload.olt_id
    if (!payload.onu_id) delete payload.onu_id
    if (!payload.vlan_id) delete payload.vlan_id
    if (payload.ip_type === 'dhcp') delete payload.ip_static

    const response = await subscriptionAPI.create(payload)
    
    createdSubscription.value = response.data
    
    // Scroll to top to show credentials
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create subscription'
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>
