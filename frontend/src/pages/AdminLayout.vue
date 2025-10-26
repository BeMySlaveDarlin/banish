<template>
  <div class="admin-layout">
    <nav class="sidebar" :class="{ open: mobileMenuOpen }">
      <div class="sidebar-brand">
        <h1>Navbar</h1>
      </div>
      <ul class="nav-menu">
        <li>
          <router-link to="/chats" class="nav-link" :class="{ active: isActive('/chats') }" @click="closeMenuOnNavigation">
            Chats
          </router-link>
        </li>
        <li v-if="currentChatId">
          <router-link :to="`/chat/${currentChatId}`" class="nav-link" :class="{ active: isActive(`/chat/${currentChatId}`) }" @click="closeMenuOnNavigation">
            &nbsp;&nbsp;• Info
          </router-link>
        </li>
        <li v-if="currentChatId">
          <router-link :to="`/chat/${currentChatId}/users`" class="nav-link" :class="{ active: isActive(`/chat/${currentChatId}/users`) }" @click="closeMenuOnNavigation">
            &nbsp;&nbsp;• Users
          </router-link>
        </li>
        <li v-if="currentChatId">
          <router-link :to="`/chat/${currentChatId}/config`" class="nav-link" :class="{ active: isActive(`/chat/${currentChatId}/config`) }" @click="closeMenuOnNavigation">
            &nbsp;&nbsp;• Config
          </router-link>
        </li>
        <li v-if="currentChatId">
          <router-link :to="`/chat/${currentChatId}/logs`" class="nav-link" :class="{ active: isActive(`/chat/${currentChatId}/logs`) }" @click="closeMenuOnNavigation">
            &nbsp;&nbsp;• Logs
          </router-link>
        </li>
      </ul>
      <div class="sidebar-footer">
        <div class="user-profile">
          <div v-if="user" class="profile-info">
            <div class="profile-name">{{ user.userName || 'User' }}</div>
            <div class="profile-id">#{{ user.userId }}</div>
          </div>
        </div>
        <button class="btn btn-danger" @click="logout">Logout</button>
      </div>
    </nav>
    <main class="main-content">
      <header class="top-bar">
        <div class="logo-section">
          <img src="@/img/logo.webp" alt="Banish Admin" class="logo">
        </div>
        <button class="mobile-menu-toggle" @click="toggleMobileMenu">☰</button>
      </header>
      <div class="content">
        <router-view/>
      </div>
    </main>
  </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import {useRoute, useRouter} from 'vue-router'
import {useAuthStore} from '@/stores/auth'

console.log('🏗️ AdminLayout.vue script setup executed')

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const mobileMenuOpen = ref(false)

const user = computed(() => authStore.user)
const currentChatId = computed(() => route.params.chatId)

console.log('👤 User:', user.value)

const isActive = (path) => {
  return route.path === path
}

const toggleMobileMenu = () => {
  console.log('📱 Toggle mobile menu, now:', !mobileMenuOpen.value)
  mobileMenuOpen.value = !mobileMenuOpen.value
}

// Закрыть меню при клике на навигацию
const closeMenuOnNavigation = () => {
  mobileMenuOpen.value = false
}

const logout = async () => {
  console.log('🚪 Logging out...')
  try {
    await authStore.logout()
    console.log('✅ Logged out successfully')
    router.push('/')
  } catch (err) {
    console.error('❌ Logout failed:', err)
  }
}

onMounted(() => {
  console.log('📌 AdminLayout.vue mounted')
})
</script>

<style scoped>
.admin-layout {
  display: flex;
  height: 100vh;
  background-color: #f5f5f5;
}

.sidebar {
  width: 250px;
  background-color: #2c3e50;
  color: white;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: fixed;
  height: 100vh;
  overflow-y: auto;
  z-index: 1000;
}

.sidebar-brand {
  padding: 20px;
  border-bottom: 1px solid #34495e;
}

.sidebar-brand h1 {
  font-size: 20px;
  margin: 0;
}

.nav-menu {
  list-style: none;
  flex: 1;
  padding: 0;
}

.nav-link {
  display: block;
  padding: 15px 20px;
  color: #bdc3c7;
  text-decoration: none;
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.nav-link:hover {
  background-color: #34495e;
  color: white;
}

.nav-link.active {
  background-color: #3498db;
  color: white;
  border-left-color: #2980b9;
}

.sidebar-footer {
  padding: 20px;
  border-top: 1px solid #34495e;
}

.user-profile {
  margin-bottom: 15px;
}

.profile-info {
  background-color: #34495e;
  padding: 10px;
  border-radius: 4px;
  text-align: center;
}

.profile-name {
  font-weight: 600;
  color: #ecf0f1;
  font-size: 14px;
  word-break: break-word;
}

.profile-id {
  font-size: 12px;
  color: #95a5a6;
  margin-top: 4px;
}

.main-content {
  flex: 1;
  margin-left: 250px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

.top-bar {
  background-color: white;
  padding: 15px 30px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo-section {
  display: flex;
  align-items: center;
}

.logo {
  max-height: 50px;
  width: auto;
  object-fit: contain;
}

.mobile-menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #333;
}

.content {
  flex: 1;
  padding: 30px;
  overflow-y: auto;
}

/* Responsive Design */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    width: 100%;
    max-width: 250px;
  }

  .sidebar.open {
    transform: translateX(0);
  }

  .main-content {
    margin-left: 0;
  }

  .mobile-menu-toggle {
    display: block;
  }

  .content {
    padding: 15px;
  }

  .top-bar {
    padding: 15px;
  }
}
</style>
