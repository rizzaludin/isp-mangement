<template>
  <div>
    <div class="mb-6 flex items-center">
      <router-link to="/customers" class="text-gray-600 hover:text-gray-900 mr-4">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit Customer' : 'Add Customer' }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
            <input
              v-model="form.phone"
              type="tel"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ID Number (KTP/NIK)</label>
            <input
              v-model="form.id_number"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <select
              v-model="form.type"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="residential">Residential</option>
              <option value="business">Business</option>
            </select>
          </div>

          <div v-if="isEdit">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="form.status"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
          <textarea
            v-model="form.address"
            rows="3"
            required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          ></textarea>
        </div>

        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>

        <div class="flex justify-end space-x-3">
          <router-link
            to="/customers"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </router-link>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50"
          >
            {{ loading ? 'Saving...' : (isEdit ? 'Update Customer' : 'Create Customer') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { customerAPI } from '@/services/api'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => route.name === 'CustomerEdit')
const customerId = computed(() => route.params.id)

const form = ref({
  name: '',
  phone: '',
  email: '',
  id_number: '',
  address: '',
  type: 'residential',
  status: 'active',
  notes: '',
})

const loading = ref(false)
const error = ref(null)

const loadCustomer = async () => {
  if (!isEdit.value) return

  try {
    const response = await customerAPI.get(customerId.value)
    Object.assign(form.value, response.data)
  } catch (err) {
    error.value = 'Failed to load customer'
    console.error(err)
  }
}

const handleSubmit = async () => {
  loading.value = true
  error.value = null

  try {
    if (isEdit.value) {
      await customerAPI.update(customerId.value, form.value)
    } else {
      await customerAPI.create(form.value)
    }
    router.push('/customers')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save customer'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCustomer()
})
</script>
