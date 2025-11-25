<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
      <div class="flex space-x-3">
        <button
          @click="showGenerateModal = true"
          class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
        >
          <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Generate Bulk
        </button>
        <button
          @click="showPaymentModal = true"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
        >
          <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          Record Payment
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Invoice number, customer..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadInvoices"
          >
            <option value="">All Status</option>
            <option value="unpaid">Unpaid</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
          <input
            v-model="filters.start_date"
            type="date"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadInvoices"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
          <input
            v-model="filters.end_date"
            type="date"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadInvoices"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
          <select
            v-model="filters.per_page"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="loadInvoices"
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
        <p class="text-sm text-gray-600">Total Invoices</p>
        <p class="text-2xl font-bold text-gray-900">{{ summary.total }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Unpaid</p>
        <p class="text-2xl font-bold text-yellow-600">{{ summary.unpaid }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Overdue</p>
        <p class="text-2xl font-bold text-red-600">{{ summary.overdue }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-600">Total Unpaid Amount</p>
        <p class="text-xl font-bold text-red-600">Rp {{ formatCurrency(summary.unpaid_amount) }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      <p class="mt-4 text-gray-600">Loading invoices...</p>
    </div>

    <!-- Invoice Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <router-link :to="`/invoices/${invoice.id}`" class="text-sm font-medium text-primary-600 hover:text-primary-900">
                {{ invoice.invoice_number }}
              </router-link>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(invoice.created_at) }}</p>
            </td>
            <td class="px-6 py-4">
              <router-link :to="`/customers/${invoice.customer?.id}`" class="text-sm text-gray-900 hover:text-primary-600">
                {{ invoice.customer?.name }}
              </router-link>
              <p class="text-xs text-gray-500">{{ invoice.customer?.code }}</p>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ formatDateShort(invoice.period_start) }} - {{ formatDateShort(invoice.period_end) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">Rp {{ formatCurrency(invoice.total) }}</div>
              <div v-if="invoice.payments?.length" class="text-xs text-green-600">
                Paid: Rp {{ formatCurrency(getTotalPaid(invoice)) }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="getStatusClass(invoice.status)">
                {{ invoice.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ formatDate(invoice.due_date) }}</div>
              <div v-if="invoice.status === 'overdue'" class="text-xs text-red-600">
                {{ getDaysOverdue(invoice.due_date) }} days overdue
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <router-link
                :to="`/invoices/${invoice.id}`"
                class="text-primary-600 hover:text-primary-900 mr-3"
              >
                View
              </router-link>
              <button
                v-if="invoice.status === 'unpaid' || invoice.status === 'overdue'"
                @click="openPaymentModal(invoice)"
                class="text-green-600 hover:text-green-900 mr-3"
              >
                Pay
              </button>
              <button
                v-if="invoice.status === 'unpaid' || invoice.status === 'overdue'"
                @click="cancelInvoice(invoice)"
                class="text-red-600 hover:text-red-900"
              >
                Cancel
              </button>
            </td>
          </tr>
          <tr v-if="invoices.length === 0">
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
              No invoices found
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
      :invoice="selectedInvoice"
      @close="showPaymentModal = false"
      @saved="onPaymentSaved"
    />

    <!-- Generate Invoice Modal -->
    <GenerateInvoiceModal
      v-if="showGenerateModal"
      @close="showGenerateModal = false"
      @generated="onInvoicesGenerated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { invoiceAPI } from '@/services/api'
import PaymentForm from './PaymentForm.vue'
import GenerateInvoiceModal from './GenerateInvoiceModal.vue'

const invoices = ref([])
const loading = ref(false)
const showPaymentModal = ref(false)
const showGenerateModal = ref(false)
const selectedInvoice = ref(null)

const filters = ref({
  search: '',
  status: '',
  start_date: '',
  end_date: '',
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
  unpaid: 0,
  overdue: 0,
  unpaid_amount: 0,
})

let searchTimeout = null

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadInvoices()
  }, 500)
}

const loadInvoices = async () => {
  loading.value = true

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: filters.value.per_page,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.start_date) params.start_date = filters.value.start_date
    if (filters.value.end_date) params.end_date = filters.value.end_date

    const response = await invoiceAPI.list(params)
    
    invoices.value = response.data.data
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      from: response.data.from,
      to: response.data.to,
      total: response.data.total,
    }

    // Calculate summary
    summary.value = {
      total: response.data.total,
      unpaid: invoices.value.filter(i => i.status === 'unpaid').length,
      overdue: invoices.value.filter(i => i.status === 'overdue').length,
      unpaid_amount: invoices.value
        .filter(i => i.status === 'unpaid' || i.status === 'overdue')
        .reduce((sum, i) => sum + parseFloat(i.total), 0),
    }
  } catch (err) {
    console.error('Load invoices error:', err)
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  loadInvoices()
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

const formatDateShort = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    month: 'short',
    day: 'numeric'
  })
}

const getTotalPaid = (invoice) => {
  return invoice.payments?.reduce((sum, p) => sum + parseFloat(p.amount), 0) || 0
}

const getDaysOverdue = (dueDate) => {
  const due = new Date(dueDate)
  const now = new Date()
  const diff = Math.floor((now - due) / (1000 * 60 * 60 * 24))
  return diff
}

const openPaymentModal = (invoice) => {
  selectedInvoice.value = invoice
  showPaymentModal.value = true
}

const cancelInvoice = async (invoice) => {
  if (!confirm(`Cancel invoice ${invoice.invoice_number}?`)) return

  try {
    await invoiceAPI.cancel(invoice.id)
    invoice.status = 'cancelled'
    alert('Invoice cancelled successfully!')
  } catch (err) {
    alert('Failed to cancel invoice: ' + (err.response?.data?.message || err.message))
  }
}

const onPaymentSaved = () => {
  showPaymentModal.value = false
  selectedInvoice.value = null
  loadInvoices()
}

const onInvoicesGenerated = () => {
  showGenerateModal.value = false
  loadInvoices()
}

onMounted(() => {
  loadInvoices()
})
</script>
