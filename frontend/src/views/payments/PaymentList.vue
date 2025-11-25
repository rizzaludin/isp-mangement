<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Payment History</h1>
      <button
        @click="showPaymentModal = true"
        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
      >
        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Record Payment
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
          <select
            v-model="filters.method"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadPayments"
          >
            <option value="">All Methods</option>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="credit_card">Credit Card</option>
            <option value="e_wallet">E-Wallet</option>
            <option value="gateway">Payment Gateway</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
          <input
            v-model="filters.start_date"
            type="date"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadPayments"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
          <input
            v-model="filters.end_date"
            type="date"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadPayments"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
          <select
            v-model="filters.customer_id"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadPayments"
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
            @change="loadPayments"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Summary Card -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg shadow p-6 mb-6 border border-green-200">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <p class="text-sm text-green-700 font-medium">Total Payments</p>
          <p class="text-3xl font-bold text-green-900">{{ summary.count }}</p>
        </div>
        <div>
          <p class="text-sm text-green-700 font-medium">Total Amount</p>
          <p class="text-3xl font-bold text-green-900">Rp {{ formatCurrency(summary.total) }}</p>
        </div>
        <div>
          <p class="text-sm text-green-700 font-medium">Average Payment</p>
          <p class="text-3xl font-bold text-green-900">Rp {{ formatCurrency(summary.average) }}</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      <p class="mt-4 text-gray-600">Loading payments...</p>
    </div>

    <!-- Payment Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="payment in payments" :key="payment.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ formatDate(payment.paid_at) }}</div>
              <div class="text-xs text-gray-500">{{ formatTime(payment.paid_at) }}</div>
            </td>
            <td class="px-6 py-4">
              <router-link :to="`/customers/${payment.customer?.id}`" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                {{ payment.customer?.name }}
              </router-link>
              <p class="text-xs text-gray-500">{{ payment.customer?.code }}</p>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <router-link :to="`/invoices/${payment.invoice?.id}`" class="text-sm text-primary-600 hover:text-primary-900">
                {{ payment.invoice?.invoice_number }}
              </router-link>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-bold text-green-600">Rp {{ formatCurrency(payment.amount) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                {{ getMethodName(payment.method) }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900">{{ payment.reference || '-' }}</div>
              <div v-if="payment.notes" class="text-xs text-gray-500 mt-1">{{ payment.notes }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="viewDetails(payment)"
                class="text-primary-600 hover:text-primary-900 mr-3"
              >
                View
              </button>
            </td>
          </tr>
          <tr v-if="payments.length === 0">
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
              No payments found
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

    <!-- Payment Modal -->
    <PaymentForm
      v-if="showPaymentModal"
      @close="showPaymentModal = false"
      @saved="onPaymentSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { paymentAPI, customerAPI } from '@/services/api'
import PaymentForm from '../invoices/PaymentForm.vue'

const payments = ref([])
const customers = ref([])
const loading = ref(false)
const showPaymentModal = ref(false)

const filters = ref({
  method: '',
  start_date: '',
  end_date: '',
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
  count: 0,
  total: 0,
  average: 0,
})

const loadPayments = async () => {
  loading.value = true

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: filters.value.per_page,
    }

    if (filters.value.method) params.method = filters.value.method
    if (filters.value.start_date) params.start_date = filters.value.start_date
    if (filters.value.end_date) params.end_date = filters.value.end_date
    if (filters.value.customer_id) params.customer_id = filters.value.customer_id

    const response = await paymentAPI.list(params)
    
    payments.value = response.data.data
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      from: response.data.from,
      to: response.data.to,
      total: response.data.total,
    }

    // Calculate summary
    const totalAmount = payments.value.reduce((sum, p) => sum + parseFloat(p.amount), 0)
    summary.value = {
      count: payments.value.length,
      total: totalAmount,
      average: payments.value.length > 0 ? totalAmount / payments.value.length : 0,
    }
  } catch (err) {
    console.error('Load payments error:', err)
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
  loadPayments()
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

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getMethodName = (method) => {
  const names = {
    cash: 'Cash',
    bank_transfer: 'Bank Transfer',
    credit_card: 'Credit Card',
    e_wallet: 'E-Wallet',
    gateway: 'Payment Gateway',
  }
  return names[method] || method
}

const viewDetails = (payment) => {
  // Navigate to invoice detail
  if (payment.invoice?.id) {
    window.location.href = `/invoices/${payment.invoice.id}`
  }
}

const onPaymentSaved = () => {
  showPaymentModal.value = false
  loadPayments()
}

onMounted(() => {
  loadPayments()
  loadCustomers()
})
</script>
