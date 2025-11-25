<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
      <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Generate Invoices</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <p class="text-sm text-gray-600">
          Generate invoices for all active subscriptions for the specified period.
        </p>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Period Start *</label>
            <input
              v-model="form.period_start"
              type="date"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Period End *</label>
            <input
              v-model="form.period_end"
              type="date"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Due Date (Days after period end) *</label>
          <input
            v-model.number="form.due_days"
            type="number"
            required
            min="1"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />
        </div>

        <div class="bg-yellow-50 rounded-lg p-4">
          <p class="text-sm text-yellow-800">
            ⚠️ This will generate invoices for all active subscriptions. Make sure the period is correct.
          </p>
        </div>

        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>

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
            :disabled="loading"
            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50"
          >
            {{ loading ? 'Generating...' : 'Generate Invoices' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { invoiceAPI } from '@/services/api'

const emit = defineEmits(['close', 'generated'])

const form = ref({
  period_start: '',
  period_end: '',
  due_days: 7,
})

const loading = ref(false)
const error = ref(null)

const setCurrentMonth = () => {
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  
  form.value.period_start = firstDay.toISOString().split('T')[0]
  form.value.period_end = lastDay.toISOString().split('T')[0]
}

const handleSubmit = async () => {
  if (!confirm('Generate invoices for all active subscriptions?')) return

  loading.value = true
  error.value = null

  try {
    const response = await invoiceAPI.generate(form.value)
    alert(`Successfully generated ${response.data?.length || 0} invoices!`)
    emit('generated')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to generate invoices'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  setCurrentMonth()
})
</script>
