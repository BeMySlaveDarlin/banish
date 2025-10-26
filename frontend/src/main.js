import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

console.log('🚀 App initialization starting')

const app = createApp(App)

console.log('✅ App created')

app.use(createPinia())
console.log('✅ Pinia initialized')

app.use(router)
console.log('✅ Router initialized')

// Global error handler
app.config.errorHandler = (err, instance, info) => {
  console.error('❌ Vue Error:', {
    error: err,
    componentInfo: info,
    component: instance?.$options.name
  })
}

// Global warning handler
app.config.warnHandler = (msg, instance, trace) => {
  console.warn('⚠️ Vue Warning:', msg, trace)
}

const appElement = document.getElementById('app')
console.log('📍 App container found:', !!appElement)

if (appElement) {
  app.mount('#app')
  console.log('✅ App mounted successfully')
} else {
  console.error('❌ App container (#app) not found in DOM')
}
