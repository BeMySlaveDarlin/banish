<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <img src="@/img/logo.webp" alt="Banish Admin" class="login-logo" />
        <p>Authenticate with Telegram</p>
      </div>

      <ErrorAlert :message="error" :dismissible="false" />

      <div v-if="message" :class="['alert', 'alert-' + messageType]">
        {{ message }}
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Authenticating...</p>
      </div>

      <div v-else-if="noToken" class="login-content">
        <p class="description">Session expired or not authenticated. Use a login link from the Telegram bot.</p>
      </div>

      <div v-else class="login-content">
        <p class="description">You're being authenticated with token from Telegram link. Please wait...</p>
        <button class="btn btn-primary" :disabled="isAuthenticating" @click="authenticate">
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
import ErrorAlert from '@/components/ErrorAlert.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const loading = ref(false)
const isAuthenticating = ref(false)
const message = ref('')
const messageType = ref('info')
const error = ref('')
const noToken = ref(false)

const authenticate = async () => {
  isAuthenticating.value = true
  error.value = ''
  const token = route.params.token

  try {
    const success = await authStore.login(token)

    if (success) {
      message.value = 'Authenticated successfully! Redirecting...'
      messageType.value = 'success'
      setTimeout(() => {
        router.push('/chats')
      }, 1500)
    } else {
      error.value = 'Authentication failed. Invalid or expired token.'
    }
  } catch (err) {
    error.value = 'Authentication error: ' + err.message
  } finally {
    loading.value = false
    isAuthenticating.value = false
  }
}

onMounted(() => {
  const token = route.params.token
  if (token && token !== 'expired' && token !== 'invalid') {
    loading.value = true
    authenticate()
  } else {
    noToken.value = true
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
