<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center">
        <router-link to="/invoices" class="text-gray-600 hover:text-gray-900 mr-4">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">Invoice Details</h1>
      </div>
      
      <div class="flex space-x-3">
        <button
          v-if="invoice.status === 'unpaid' || invoice.status === 'overdue'"
          @click="showPaymentModal = true"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        >
          💳 Record Payment
        </button>
        <button
          @click="downloadPDF"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          📄 Download PDF
        </button>
      </div>
    </div>

    <div v-if="loading" class="bg-white rounded-lg shadow p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="invoice.id" class="space-y-6">
      <!-- Invoice Header -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ invoice.invoice_number }}</h2>
            <p class="text-sm text-gray-500 mt-1">Created: {{ formatDate(invoice.created_at) }}</p>
          </div>
          <span class="px-4 py-2 rounded-full text-sm font-medium" :class="getStatusClass(invoice.status)">
            {{ invoice.status?.toUpperCase() }}
          </span>
        </div>

        <!-- Customer & Subscription Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="text-sm font-medium text-gray-500 mb-3">BILL TO</h3>
            <router-link :to="`/customers/${invoice.customer?.id}`" class="text-lg font-semibold text-gray-900 hover:text-primary-600">
              {{ invoice.customer?.name }}
            </router-link>
            <p class="text-sm text-gray-600 mt-1">{{ invoice.customer?.address }}</p>
            <p class="text-sm text-gray-600">Phone: {{ invoice.customer?.phone }}</p>
            <p class="text-sm text-gray-600">Email: {{ invoice.customer?.email || '-' }}</p>
          </div>
          <div>
            <h3 class="text-sm font-medium text-gray-500 mb-3">SERVICE</h3>
            <p class="text-lg font-semibold text-gray-900">{{ invoice.subscription?.service?.name }}</p>
            <p class="text-sm text-gray-600 mt-1">PPPoE: {{ invoice.subscription?.username_pppoe }}</p>
            <p class="text-sm text-gray-600">Period: {{ formatDate(invoice.period_start) }} - {{ formatDate(invoice.period_end) }}</p>
          </div>
        </div>
      </div>

      <!-- Invoice Breakdown -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice Breakdown</h2>
        
        <div class="space-y-3 mb-6">
          <div class="flex justify-between py-2">
            <span class="text-sm text-gray-600">Subtotal</span>
            <span class="text-sm font-medium text-gray-900">Rp {{ formatCurrency(invoice.subtotal) }}</span>
          </div>
          <div v-if="invoice.tax > 0" class="flex justify-between py-2">
            <span class="text-sm text-gray-600">Tax (11% PPN)</span>
            <span class="text-sm font-medium text-gray-900">Rp {{ formatCurrency(invoice.tax) }}</span>
          </div>
          <div v-if="invoice.discount > 0" class="flex justify-between py-2">
            <span class="text-sm text-gray-600">Discount</span>
            <span class="text-sm font-medium text-red-600">- Rp {{ formatCurrency(invoice.discount) }}</span>
          </div>
          <div class="flex justify-between py-3 border-t-2 border-gray-200">
            <span class="text-base font-semibold text-gray-900">Total</span>
            <span class="text-lg font-bold text-gray-900">Rp {{ formatCurrency(invoice.total) }}</span>
          </div>
          <div v-if="invoice.payments?.length" class="flex justify-between py-2 bg-green-50 px-3 rounded">
            <span class="text-sm font-medium text-green-800">Total Paid</span>
            <span class="text-sm font-bold text-green-800">Rp {{ formatCurrency(getTotalPaid(invoice)) }}</span>
          </div>
          <div v-if="getRemaining(invoice) > 0" class="flex justify-between py-2 bg-yellow-50 px-3 rounded">
            <span class="text-sm font-medium text-yellow-800">Remaining</span>
            <span class="text-sm font-bold text-yellow-800">Rp {{ formatCurrency(getRemaining(invoice)) }}</span>
          </div>
        </div>

        <div class="border-t pt-4">
          <p class="text-sm text-gray-600"><strong>Due Date:</strong> {{ formatDate(invoice.due_date) }}</p>
          <p v-if="invoice.paid_at" class="text-sm text-green-600 mt-1">
            <strong>Paid On:</strong> {{ formatDate(invoice.paid_at) }}
          </p>
        </div>
      </div>

      <!-- Payment History -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Payment History ({{ invoice.payments?.length || 0 }})</h2>
        </div>
        <div v-if="invoice.payments?.length" class="divide-y divide-gray-200">
          <div v-for="payment in invoice.payments" :key="payment.id" class="p-6">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Rp {{ formatCurrency(payment.amount) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(payment.paid_at) }}</p>
                <p class="text-xs text-gray-500 mt-1">
                  Method: <span class="font-medium">{{ getMethodName(payment.method) }}</span>
                </p>
                <p v-if="payment.reference" class="text-xs text-gray-500 mt-1">
                  Ref: {{ payment.reference }}
                </p>
              </div>
              <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                Paid
              </span>
            </div>
          </div>
        </div>
        <div v-else class="p-6 text-center text-gray-500">
          No payments recorded yet
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <PaymentForm
      v-if="showPaymentModal"
      :invoice="invoice"
      @close="showPaymentModal = false"
      @saved="onPaymentSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { invoiceAPI } from '@/services/api'
import PaymentForm from './PaymentForm.vue'
import GenerateInvoiceModal from './GenerateInvoiceModal.vue'

const route = useRoute()
const invoice = ref({})
const loading = ref(false)
const showPaymentModal = ref(false)

const loadInvoice = async () => {
  loading.value = true
  try {
    const response = await invoiceAPI.get(route.params.id)
    invoice.value = response.data
  } catch (err) {
    console.error('Load invoice error:', err)
  } finally {
    loading.value = false
  }
}

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
    month: 'long',
    day: 'numeric'
  })
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

const getTotalPaid = (inv) => {
  return inv.payments?.reduce((sum, p) => sum + parseFloat(p.amount), 0) || 0
}

const getRemaining = (inv) => {
  return parseFloat(inv.total) - getTotalPaid(inv)
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

const downloadPDF = async () => {
  try {
    const response = await invoiceAPI.downloadPdf(invoice.value.id)
    const url = window.URL.createObjectURL(new Blob([response]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${invoice.value.invoice_number}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (err) {
    alert('PDF download not implemented yet')
  }
}

const onPaymentSaved = () => {
  showPaymentModal.value = false
  loadInvoice()
}

onMounted(() => {
  loadInvoice()
})
</script>
