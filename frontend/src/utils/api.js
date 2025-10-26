import axios from 'axios'

console.log('🌐 API module loading...')

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost'

console.log('📍 API Base URL:', API_BASE_URL)

const api = axios.create({
	baseURL: API_BASE_URL,
	withCredentials: true,
	headers: {
		'Content-Type': 'application/json',
	}
})

console.log('✅ Axios instance created')

api.interceptors.request.use(
	config => {
		console.log('📤 API Request:', {
			method: config.method.toUpperCase(),
			url: config.url
		})
		console.log('🔑 Cookies will be sent automatically (withCredentials: true)')
		return config
	},
	error => {
		console.error('❌ Request interceptor error:', error)
		return Promise.reject(error)
	}
)

api.interceptors.response.use(
	response => {
		console.log('📥 API Response:', {
			status: response.status,
			url: response.config.url,
			dataKeys: Object.keys(response.data || {})
		})
		return response
	},
	error => {
		console.error('❌ API Error:', {
			status: error.response?.status,
			url: error.config?.url,
			message: error.message,
			data: error.response?.data
		})
		if (error.response?.status === 401) {
			console.log('🔐 401 Unauthorized - clearing token and redirecting')
			localStorage.removeItem('auth_token')
			window.location.href = '/'
		}
		return Promise.reject(error)
	}
)

console.log('✅ API interceptors configured')

export default api
