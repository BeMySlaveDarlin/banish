import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import logger from '@/utils/logger'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.config.errorHandler = (err, instance, info) => {
  logger.error('Vue Error:', err, info)
}

app.config.warnHandler = (msg, instance, trace) => {
  logger.warn('Vue Warning:', msg, trace)
}

const appElement = document.getElementById('app')

if (appElement) {
  app.mount('#app')
} else {
  logger.error('App container (#app) not found in DOM')
}
