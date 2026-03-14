<template>
  <div class="users-wrapper">
    <LoadingSpinner v-if="usersStore.loading && usersStore.usersList.length === 0" />

    <div v-else class="users-content">
      <PageHeader
        title="Users in Chat"
        :subtitle="`Chat #${chatId}${usersStore.chatTitle ? ' -- ' + usersStore.chatTitle : ''}`"
        show-back
        back-title="Go back to chat details"
        @back="goBack"
      />

      <ErrorAlert :message="usersStore.error" @dismiss="usersStore.clearError()" />

      <ConfirmDialog
        :visible="confirmUnban.visible"
        title="Confirm Unban"
        message="Are you sure you want to unban this user?"
        confirm-text="Unban"
        confirm-variant="success"
        @confirm="performUnban"
        @cancel="confirmUnban.visible = false"
      />

      <div v-if="usersStore.usersList.length === 0" class="empty-state">
        <p>No users found</p>
      </div>

      <div v-else class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Username</th>
              <th>Messages</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in usersStore.usersList" :key="user.id">
              <td>
                <strong>{{ user.username || user.name }}</strong>
                <br />
                <small>#{{ user.id }}</small>
              </td>
              <td>{{ user.messagesCount }}</td>
              <td>
                <span v-if="user.isBanned" class="badge badge-danger">Banned ({{ user.bansCount }})</span>
                <span v-else-if="user.isAdmin" class="badge badge-primary">Admin</span>
                <span v-else-if="user.isBot" class="badge badge-secondary">Bot</span>
                <span v-else class="badge badge-success">Active</span>
              </td>
              <td>
                <div class="action-buttons">
                  <router-link :to="`/chat/${chatId}/users/${user.id}`" class="btn btn-primary btn-small">
                    Details
                  </router-link>
                  <button v-if="user.isBanned" class="btn btn-success btn-small" @click="requestUnban(user.id)">
                    Unban
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <Pagination
          :current-page="page"
          :total-pages="totalPages"
          :total-items="usersStore.totalUsers"
          :has-more="usersStore.hasMore"
          @prev="prevPage"
          @next="nextPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUsersStore } from '@/stores/users'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ErrorAlert from '@/components/ErrorAlert.vue'
import Pagination from '@/components/Pagination.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const route = useRoute()
const router = useRouter()
const usersStore = useUsersStore()

const page = ref(0)
const pageSize = 50

const chatId = computed(() => route.params.chatId)
const totalPages = computed(() => Math.ceil(usersStore.totalUsers / pageSize))

const confirmUnban = reactive({
  visible: false,
  userId: null,
})

const loadUsers = async () => {
  const offset = page.value * pageSize
  try {
    await usersStore.fetchUsers(chatId.value, { limit: pageSize, offset })
  } catch {
    // error handled in store
  }
}

const nextPage = async () => {
  if (usersStore.hasMore) {
    page.value++
    await loadUsers()
  }
}

const prevPage = async () => {
  if (page.value > 0) {
    page.value--
    await loadUsers()
  }
}

const requestUnban = (userId) => {
  confirmUnban.userId = userId
  confirmUnban.visible = true
}

const performUnban = async () => {
  confirmUnban.visible = false
  try {
    await usersStore.unbanUser(chatId.value, confirmUnban.userId)
    await loadUsers()
  } catch {
    // error handled in store
  }
}

const goBack = () => {
  router.push(`/chat/${chatId.value}`)
}

watch(() => route.params.chatId, () => {
  page.value = 0
  loadUsers()
}, { immediate: true })
</script>

<style scoped>
.users-wrapper {
  max-width: 1200px;
  margin: 0 auto;
}

.table-responsive {
  overflow-x: auto;
}

.action-buttons {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
}

@media (max-width: 768px) {
  .table {
    font-size: 13px;
  }

  .action-buttons {
    flex-direction: column;
  }

  .btn-small {
    padding: 4px 8px;
    font-size: 11px;
  }
}
</style>
