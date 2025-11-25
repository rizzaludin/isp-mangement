import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authAPI } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'superadmin' || user.value?.role === 'admin')
  const isNOC = computed(() => user.value?.role === 'noc')
  const isFinance = computed(() => user.value?.role === 'finance')

  const login = async (credentials) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await authAPI.login(credentials)
      
      token.value = response.data.token
      user.value = response.data.user
      
      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))
      
      return response
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    try {
      await authAPI.logout()
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  const fetchUser = async () => {
    if (!token.value) return
    
    try {
      const response = await authAPI.me()
      user.value = response.data
      localStorage.setItem('user', JSON.stringify(user.value))
    } catch (err) {
      console.error('Fetch user error:', err)
      logout()
    }
  }

  const updateProfile = async (data) => {
    try {
      const response = await authAPI.updateProfile(data)
      user.value = { ...user.value, ...data }
      localStorage.setItem('user', JSON.stringify(user.value))
      return response
    } catch (err) {
      throw err
    }
  }

  const changePassword = async (data) => {
    try {
      return await authAPI.changePassword(data)
    } catch (err) {
      throw err
    }
  }

  // Initialize from localStorage
  const init = () => {
    const storedUser = localStorage.getItem('user')
    if (storedUser) {
      user.value = JSON.parse(storedUser)
    }
    if (token.value && !user.value) {
      fetchUser()
    }
  }

  init()

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    isNOC,
    isFinance,
    login,
    logout,
    fetchUser,
    updateProfile,
    changePassword,
  }
})
