<template>
  <router-view/>
</template>

<script setup>
import {onMounted, onBeforeMount, onUpdated} from 'vue'
import {useAuthStore} from '@/stores/auth'

console.log('📄 App.vue script setup executed')

const authStore = useAuthStore()

console.log('🔐 Auth store initialized, token:', !!authStore.token)

onBeforeMount(() => {
  console.log('🔄 App.vue before mount')
})

onMounted(async () => {
  console.log('📌 App.vue mounted')
  if (authStore.token) {
    console.log('🔑 Token found, validating session...')
    try {
      await authStore.validateSession()
      console.log('✅ Session validated')
    } catch (err) {
      console.error('❌ Session validation failed:', err)
    }
  } else {
    console.log('⚠️ No token found')
  }
})

onUpdated(() => {
  console.log('🔄 App.vue updated')
})
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  font-size: 16px;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
  background-color: #f5f5f5;
  color: #333;
}

#app {
  width: 100%;
  min-height: 100vh;
}
</style>
