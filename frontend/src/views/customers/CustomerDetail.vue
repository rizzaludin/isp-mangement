<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center">
        <router-link to="/customers" class="text-gray-600 hover:text-gray-900 mr-4">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">Customer Details</h1>
      </div>
      <router-link
        :to="`/customers/${customer.id}/edit`"
        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
      >
        Edit Customer
      </router-link>
    </div>

    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="customer.id" class="space-y-6">
      <!-- Customer Info Card -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-semibold">Customer Information</h2>
          <span class="px-3 py-1 rounded-full text-sm font-medium"
            :class="customer.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
            {{ customer.status }}
          </span>
        </div>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <dt class="text-sm font-medium text-gray-500">Customer Code</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.code }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Name</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.name }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Phone</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.phone }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Email</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.email || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Type</dt>
            <dd class="mt-1 text-sm text-gray-900 capitalize">{{ customer.type }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">ID Number</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.id_number || '-' }}</dd>
          </div>
          <div class="col-span-2">
            <dt class="text-sm font-medium text-gray-500">Address</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.address }}</dd>
          </div>
          <div v-if="customer.notes" class="col-span-2">
            <dt class="text-sm font-medium text-gray-500">Notes</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.notes }}</dd>
          </div>
        </dl>
      </div>

      <!-- Subscriptions -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold">Subscriptions ({{ customer.subscriptions?.length || 0 }})</h2>
          <router-link
            :to="`/subscriptions/create?customer_id=${customer.id}`"
            class="text-primary-600 hover:text-primary-900 text-sm font-medium"
          >
            + Add Subscription
          </router-link>
        </div>
        <div v-if="customer.subscriptions?.length" class="divide-y divide-gray-200">
          <div v-for="sub in customer.subscriptions" :key="sub.id" class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <router-link :to="`/subscriptions/${sub.id}`" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                  {{ sub.service?.name }}
                </router-link>
                <p class="text-sm text-gray-500 mt-1">{{ sub.username_pppoe }}</p>
              </div>
              <span class="px-3 py-1 rounded-full text-xs font-medium"
                :class="getSubscriptionStatusClass(sub.status)">
                {{ sub.status }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No subscriptions yet
        </div>
      </div>

      <!-- Invoices -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold">Recent Invoices ({{ customer.invoices?.length || 0 }})</h2>
        </div>
        <div v-if="customer.invoices?.length" class="divide-y divide-gray-200">
          <div v-for="invoice in customer.invoices.slice(0, 5)" :key="invoice.id" class="p-6 flex items-center justify-between">
            <div>
              <router-link :to="`/invoices/${invoice.id}`" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                {{ invoice.invoice_number }}
              </router-link>
              <p class="text-sm text-gray-500 mt-1">{{ formatDate(invoice.created_at) }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">Rp {{ formatCurrency(invoice.total) }}</p>
              <span class="text-xs px-2 py-1 rounded-full"
                :class="getInvoiceStatusClass(invoice.status)">
                {{ invoice.status }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No invoices yet
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { customerAPI } from '@/services/api'

const route = useRoute()
const customer = ref({})
const loading = ref(false)

const loadCustomer = async () => {
  loading.value = true
  try {
    const response = await customerAPI.get(route.params.id)
    customer.value = response.data
  } catch (err) {
    console.error('Load customer error:', err)
  } finally {
    loading.value = false
  }
}

const getSubscriptionStatusClass = (status) => {
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

onMounted(() => {
  loadCustomer()
})
</script>
