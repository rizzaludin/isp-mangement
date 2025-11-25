import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor - add token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// Authentication
export const authAPI = {
  login: (credentials) => api.post('/auth/login', credentials),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  updateProfile: (data) => api.put('/auth/profile', data),
  changePassword: (data) => api.put('/auth/password', data),
}

// Customers
export const customerAPI = {
  list: (params) => api.get('/customers', { params }),
  get: (id) => api.get(`/customers/${id}`),
  create: (data) => api.post('/customers', data),
  update: (id, data) => api.put(`/customers/${id}`, data),
  delete: (id) => api.delete(`/customers/${id}`),
  suspend: (id) => api.post(`/customers/${id}/suspend`),
  activate: (id) => api.post(`/customers/${id}/activate`),
}

// Services
export const serviceAPI = {
  list: (params) => api.get('/services', { params }),
  get: (id) => api.get(`/services/${id}`),
  create: (data) => api.post('/services', data),
  update: (id, data) => api.put(`/services/${id}`, data),
  delete: (id) => api.delete(`/services/${id}`),
}

// Subscriptions
export const subscriptionAPI = {
  list: (params) => api.get('/subscriptions', { params }),
  get: (id) => api.get(`/subscriptions/${id}`),
  create: (data) => api.post('/subscriptions', data),
  update: (id, data) => api.put(`/subscriptions/${id}`, data),
  delete: (id) => api.delete(`/subscriptions/${id}`),
  provision: (id) => api.post(`/subscriptions/${id}/provision`),
  suspend: (id) => api.post(`/subscriptions/${id}/suspend`),
  unsuspend: (id) => api.post(`/subscriptions/${id}/unsuspend`),
  resetSession: (id) => api.post(`/subscriptions/${id}/reset-session`),
  terminate: (id) => api.post(`/subscriptions/${id}/terminate`),
}

// Invoices
export const invoiceAPI = {
  list: (params) => api.get('/invoices', { params }),
  get: (id) => api.get(`/invoices/${id}`),
  create: (data) => api.post('/invoices', data),
  update: (id, data) => api.put(`/invoices/${id}`, data),
  generate: (data) => api.post('/invoices/generate', data),
  cancel: (id) => api.post(`/invoices/${id}/cancel`),
  downloadPdf: (id) => api.get(`/invoices/${id}/pdf`, { responseType: 'blob' }),
}

// Payments
export const paymentAPI = {
  list: (params) => api.get('/payments', { params }),
  get: (id) => api.get(`/payments/${id}`),
  create: (data) => api.post('/payments', data),
  delete: (id) => api.delete(`/payments/${id}`),
}

// Devices
export const deviceAPI = {
  // Routers
  listRouters: (params) => api.get('/devices/routers', { params }),
  getRouter: (id) => api.get(`/devices/routers/${id}`),
  createRouter: (data) => api.post('/devices/routers', data),
  updateRouter: (id, data) => api.put(`/devices/routers/${id}`, data),
  deleteRouter: (id) => api.delete(`/devices/routers/${id}`),
  
  // OLT
  listOlts: (params) => api.get('/devices/olt', { params }),
  getOlt: (id) => api.get(`/devices/olt/${id}`),
  createOlt: (data) => api.post('/devices/olt', data),
  updateOlt: (id, data) => api.put(`/devices/olt/${id}`, data),
  deleteOlt: (id) => api.delete(`/devices/olt/${id}`),
  
  // ONU
  listOnus: (params) => api.get('/devices/onu', { params }),
  getOnu: (id) => api.get(`/devices/onu/${id}`),
}

// Dashboard (deprecated - use monitoring API)
export const dashboardAPI = {
  stats: () => api.get('/dashboard/stats'),
  recentActivities: () => api.get('/dashboard/recent-activities'),
  chartData: (params) => api.get('/dashboard/chart-data', { params }),
}

// Monitoring
export const monitoringAPI = {
  summary: () => api.get('/monitoring/summary'),
  devices: () => api.get('/monitoring/devices'),
  traffic: () => api.get('/monitoring/traffic'),
  alerts: () => api.get('/monitoring/alerts'),
  chartData: (params) => api.get('/monitoring/chart-data', { params }),
  recentActivities: () => api.get('/monitoring/recent-activities'),
}

export default api
