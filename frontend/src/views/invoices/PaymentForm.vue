<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Record Payment</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Invoice Info (if provided) -->
        <div v-if="invoice" class="bg-blue-50 rounded-lg p-4">
          <h3 class="font-semibold text-blue-900 mb-2">Invoice Information</h3>
          <p class="text-sm text-blue-800">{{ invoice.invoice_number }}</p>
          <p class="text-sm text-blue-800">Customer: {{ invoice.customer?.name }}</p>
          <p class="text-lg font-bold text-blue-900 mt-2">Amount Due: Rp {{ formatCurrency(invoice.total) }}</p>
          <p v-if="invoice.payments?.length" class="text-sm text-blue-800">
            Already Paid: Rp {{ formatCurrency(getTotalPaid(invoice)) }}
          </p>
        </div>

        <!-- Invoice Selection (if no invoice provided) -->
        <div v-else>
          <label class="block text-sm font-medium text-gray-700 mb-1">Select Invoice *</label>
          <select
            v-model="form.invoice_id"
            required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="onInvoiceChange"
          >
            <option value="">Select Invoice</option>
            <option v-for="inv in unpaidInvoices" :key="inv.id" :value="inv.id">
              {{ inv.invoice_number }} - {{ inv.customer?.name }} - Rp {{ formatCurrency(inv.total) }}
            </option>
          </select>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount *</label>
          <div class="relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500 sm:text-sm">Rp</span>
            </div>
            <input
              v-model.number="form.amount"
              type="number"
              required
              min="1"
              step="1"
              class="pl-12 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              placeholder="0"
            />
          </div>
          <p v-if="suggestedAmount" class="mt-1 text-sm text-gray-500">
            Suggested: Rp {{ formatCurrency(suggestedAmount) }}
            <button type="button" @click="form.amount = suggestedAmount" class="text-primary-600 hover:text-primary-800 ml-2">
              Use this
            </button>
          </p>
        </div>

        <!-- Payment Method -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
          <select
            v-model="form.method"
            required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="cash">Cash (Tunai)</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="credit_card">Credit Card</option>
            <option value="e_wallet">E-Wallet</option>
            <option value="gateway">Payment Gateway</option>
          </select>
        </div>

        <!-- Reference -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
          <input
            v-model="form.reference"
            type="text"
            placeholder="Transaction ID, Receipt number..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />
        </div>

        <!-- Payment Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date & Time *</label>
          <input
            v-model="form.paid_at"
            type="datetime-local"
            required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea
            v-model="form.notes"
            rows="3"
            placeholder="Additional payment notes..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          ></textarea>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading || !form.invoice_id"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
          >
            {{ loading ? 'Recording...' : 'Record Payment' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { paymentAPI, invoiceAPI } from '@/services/api'

const props = defineProps({
  invoice: {
    type: Object,
    default: null,
  }
})

const emit = defineEmits(['close', 'saved'])

const form = ref({
  invoice_id: props.invoice?.id || '',
  amount: props.invoice?.total || 0,
  method: 'cash',
  reference: '',
  paid_at: new Date().toISOString().slice(0, 16),
  notes: '',
})

const unpaidInvoices = ref([])
const loading = ref(false)
const error = ref(null)

const suggestedAmount = computed(() => {
  if (props.invoice) {
    return parseFloat(props.invoice.total) - getTotalPaid(props.invoice)
  }
  const selected = unpaidInvoices.value.find(i => i.id === form.value.invoice_id)
  return selected ? parseFloat(selected.total) : 0
})

const loadUnpaidInvoices = async () => {
  if (props.invoice) return

  try {
    const response = await invoiceAPI.list({ status: 'unpaid,overdue', per_page: 100 })
    unpaidInvoices.value = response.data.data
  } catch (err) {
    console.error('Load invoices error:', err)
  }
}

const onInvoiceChange = () => {
  if (suggestedAmount.value) {
    form.value.amount = suggestedAmount.value
  }
}

const getTotalPaid = (inv) => {
  return inv.payments?.reduce((sum, p) => sum + parseFloat(p.amount), 0) || 0
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value)
}

const handleSubmit = async () => {
  loading.value = true
  error.value = null

  try {
    await paymentAPI.create(form.value)
    emit('saved')
    alert('Payment recorded successfully!')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to record payment'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadUnpaidInvoices()
  if (props.invoice) {
    form.value.invoice_id = props.invoice.id
  }
})
</script>
