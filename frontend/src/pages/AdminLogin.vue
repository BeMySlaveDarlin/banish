<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <img src="@/img/logo.webp" alt="Banish Admin" class="login-logo">
        <p>Authenticate with Telegram</p>
      </div>

      <div v-if="message" :class="['alert', 'alert-' + messageType]">
        {{ message }}
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Authenticating...</p>
      </div>

      <div v-else class="login-content">
        <p class="description">You're being authenticated with token from Telegram link. Please wait...</p>
        <button class="btn btn-primary" @click="authenticate" :disabled="isAuthenticating">
          Confirm Authentication
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

console.log('🔐 AdminLogin.vue script setup executed')

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const loading = ref(false)
const isAuthenticating = ref(false)
const message = ref('')
const messageType = ref('info')

const authenticate = async () => {
  console.log('🔑 Starting authentication...')
  isAuthenticating.value = true
  const token = route.params.token

  console.log('📝 Token provided:', !!token)

  try {
    const success = await authStore.login(token)
    console.log('📊 Login result:', success)

    if (success) {
      console.log('✅ Authentication successful')
      message.value = 'Authenticated successfully! Redirecting...'
      messageType.value = 'success'
      setTimeout(() => {
        console.log('🔀 Redirecting to /chats')
        router.push('/chats')
      }, 1500)
    } else {
      console.error('❌ Authentication failed')
      message.value = 'Authentication failed. Invalid or expired token.'
      messageType.value = 'danger'
      isAuthenticating.value = false
    }
  } catch (err) {
    console.error('❌ Authentication error:', err)
    message.value = 'Authentication error: ' + err.message
    messageType.value = 'danger'
    isAuthenticating.value = false
  }
}

onMounted(() => {
  console.log('📌 AdminLogin.vue mounted')
  const token = route.params.token
  console.log('🔍 Token in route:', !!token)

  if (token) {
    console.log('⏳ Starting auto-authentication')
    loading.value = true
    authenticate()
  } else {
    console.warn('⚠️ No token in route')
    message.value = 'No authentication token provided'
    messageType.value = 'danger'
  }
})
</script>

<style scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  padding: 40px;
  max-width: 400px;
  width: 100%;
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.login-logo {
  max-width: 150px;
  height: auto;
  margin: 0 auto 20px;
  display: block;
  object-fit: contain;
}

.login-header p {
  color: #777;
  font-size: 14px;
}

.login-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.description {
  text-align: center;
  color: #666;
  font-size: 14px;
  line-height: 1.5;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
  padding: 40px 0;
}

.loading p {
  color: #666;
  font-size: 14px;
}

@media (max-width: 480px) {
  .login-card {
    padding: 25px;
  }

  .login-header h1 {
    font-size: 22px;
  }
}
</style>
