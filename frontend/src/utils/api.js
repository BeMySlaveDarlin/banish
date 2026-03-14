import axios from 'axios'
import logger from '@/utils/logger'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost'

const api = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use(
  (config) => {
    logger.log('API Request:', config.method.toUpperCase(), config.url)
    return config
  },
  (error) => {
    logger.error('Request interceptor error:', error)
    return Promise.reject(error)
  },
)

api.interceptors.response.use(
  (response) => {
    logger.log('API Response:', response.status, response.config.url)
    return response
  },
  (error) => {
    logger.error('API Error:', error.response?.status, error.config?.url, error.message)
    const isAuthEndpoint = error.config?.url?.includes('/auth/')
    if (error.response?.status === 401 && !isAuthEndpoint) {
      window.location.href = '/admin/auth/expired'
    }
    return Promise.reject(error)
  },
)

export default api
