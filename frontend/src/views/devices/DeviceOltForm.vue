<template>
  <div>
    <div class="mb-6 flex items-center">
      <router-link to="/devices/olt" class="text-gray-600 hover:text-gray-900 mr-4">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit OLT' : 'Add OLT' }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Basic Info -->
        <div class="border-b pb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">OLT Name *</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="OLT-POP1"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
              <select
                v-model="form.vendor"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option value="">Select Vendor</option>
                <option value="huawei">Huawei</option>
                <option value="zte">ZTE</option>
                <option value="fiberhome">FiberHome</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
              <input
                v-model="form.model"
                type="text"
                placeholder="MA5608T, C320, etc."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Port Count</label>
              <input
                v-model.number="form.port_count"
                type="number"
                placeholder="16"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              />
            </div>
          </div>
        </div>

        <!-- Network Configuration -->
        <div class="border-b pb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Network Configuration</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">IP Address *</label>
              <input
                v-model="form.ip_address"
                type="text"
                required
                placeholder="192.168.1.10"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Management Type *</label>
              <select
                v-model="form.mgmt_type"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option value="ssh">SSH</option>
                <option value="telnet">Telnet</option>
                <option value="snmp">SNMP</option>
                <option value="api">API</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
              <input
                v-model.number="form.mgmt_port"
                type="number"
                :placeholder="form.mgmt_type === 'ssh' ? '22' : form.mgmt_type === 'telnet' ? '23' : '161'"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              />
            </div>
          </div>
        </div>

        <!-- Credentials -->
        <div class="border-b pb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Credentials</h3>
          
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <p class="text-sm text-yellow-800">
              🔒 Credentials will be encrypted before storage using AES-256 encryption.
            </p>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
              <input
                v-model="form.username"
                type="text"
                required
                placeholder="admin"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
              <div class="relative">
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center"
                >
                  <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <div v-if="form.mgmt_type === 'snmp'" class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">SNMP Community</label>
            <input
              v-model="form.snmp_community"
              type="text"
              placeholder="public"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>
        </div>

        <!-- Additional Info -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select
                v-model="form.status"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="maintenance">Maintenance</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
              <input
                v-model="form.location"
                type="text"
                placeholder="POP 1 - Jakarta"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              />
            </div>
          </div>
          
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Additional notes about this OLT..."
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            ></textarea>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <router-link
            to="/devices/olt"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </router-link>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50"
          >
            {{ loading ? 'Saving...' : isEdit ? 'Update OLT' : 'Create OLT' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { deviceAPI } from '@/services/api'

const route = useRoute()
const router = useRouter()

const isEdit = ref(!!route.params.id)
const showPassword = ref(false)

const form = ref({
  name: '',
  vendor: 'huawei',
  model: '',
  port_count: 16,
  ip_address: '',
  mgmt_type: 'ssh',
  mgmt_port: 22,
  username: '',
  password: '',
  snmp_community: 'public',
  status: 'active',
  location: '',
  description: '',
})

const loading = ref(false)
const error = ref(null)

const loadOlt = async () => {
  if (!isEdit.value) return

  try {
    const response = await deviceAPI.getOlt(route.params.id)
    const olt = response.data
    
    form.value = {
      name: olt.name,
      vendor: olt.vendor,
      model: olt.model || '',
      port_count: olt.port_count || 16,
      ip_address: olt.ip_address,
      mgmt_type: olt.mgmt_type || 'ssh',
      mgmt_port: olt.mgmt_port || 22,
      username: olt.username,
      password: '',
      snmp_community: olt.snmp_community || 'public',
      status: olt.status,
      location: olt.location || '',
      description: olt.description || '',
    }
  } catch (err) {
    error.value = 'Failed to load OLT'
    console.error(err)
  }
}

const handleSubmit = async () => {
  loading.value = true
  error.value = null

  try {
    const payload = { ...form.value }
    
    // Remove empty fields
    if (!payload.model) delete payload.model
    if (!payload.location) delete payload.location
    if (!payload.description) delete payload.description
    if (payload.mgmt_type !== 'snmp') delete payload.snmp_community
    
    // If editing and password is empty, don't send it
    if (isEdit.value && !payload.password) {
      delete payload.password
    }

    if (isEdit.value) {
      await deviceAPI.updateOlt(route.params.id, payload)
      alert('OLT updated successfully!')
    } else {
      await deviceAPI.createOlt(payload)
      alert('OLT created successfully!')
    }
    
    router.push('/devices/olt')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save OLT'
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadOlt()
})
</script>
