<template>
  <div class="config-wrapper">
    <LoadingSpinner v-if="chatsStore.loading" />

    <div v-else-if="chatsStore.chatConfig" class="config-content">
      <PageHeader
        title="Chat Configuration"
        :subtitle="`Chat #${chatId}${chatsStore.chatConfig.title ? ' -- ' + chatsStore.chatConfig.title : ''}`"
        show-back
        back-title="Go back to chat details"
        @back="goBack"
      >
        <template #actions>
          <span v-if="autosaveStatus" :class="['status-text', autosaveStatus]">{{ autosaveStatusText }}</span>
        </template>
      </PageHeader>

      <ErrorAlert :message="chatsStore.error" @dismiss="chatsStore.clearError()" />

      <div class="config-grid">
        <div class="config-card main-form">
          <h2>Settings</h2>
          <form>
            <div class="form-group">
              <label class="form-label">Ban Emoji</label>
              <input
                v-model="form.banEmoji"
                type="text"
                class="form-control"
                maxlength="2"
                placeholder="Enter emoji"
                :disabled="!form.enableReactions"
                @input="scheduleAutosave"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Forgive Emoji</label>
              <input
                v-model="form.forgiveEmoji"
                type="text"
                class="form-control"
                maxlength="2"
                placeholder="Enter emoji"
                :disabled="!form.enableReactions"
                @input="scheduleAutosave"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Votes Required to Ban</label>
              <input
                v-model.number="form.votesRequired"
                type="number"
                class="form-control"
                min="1"
                @input="scheduleAutosave"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Min Messages for Trust</label>
              <input
                v-model.number="form.minMessagesForTrust"
                type="number"
                class="form-control"
                min="0"
                @input="scheduleAutosave"
              />
            </div>
          </form>
        </div>

        <div class="config-card toggles-card">
          <h2>Status</h2>
          <div class="toggles-container">
            <div class="toggle-item">
              <label class="toggle-label">Enabled</label>
              <div class="toggle-switch">
                <input
                  id="enabled-toggle"
                  v-model="form.enabled"
                  type="checkbox"
                  class="toggle-input"
                  @change="scheduleAutosave"
                />
                <label for="enabled-toggle" class="toggle-slider"></label>
              </div>
            </div>

            <div class="toggle-item">
              <label class="toggle-label">Delete Messages</label>
              <div class="toggle-switch">
                <input
                  id="delete-toggle"
                  v-model="form.toggleDeleteMessage"
                  type="checkbox"
                  class="toggle-input"
                  @change="scheduleAutosave"
                />
                <label for="delete-toggle" class="toggle-slider"></label>
              </div>
            </div>

            <div class="toggle-item" :class="{ disabled: !form.toggleDeleteMessage }">
              <label class="toggle-label">Only Delete</label>
              <div class="toggle-switch">
                <input
                  id="delete-only-toggle"
                  v-model="form.deleteOnly"
                  type="checkbox"
                  class="toggle-input"
                  :disabled="!form.toggleDeleteMessage"
                  @change="scheduleAutosave"
                />
                <label for="delete-only-toggle" class="toggle-slider"></label>
              </div>
            </div>

            <div class="toggle-item">
              <label class="toggle-label">Enable Reactions</label>
              <div class="toggle-switch">
                <input
                  id="reactions-toggle"
                  v-model="form.enableReactions"
                  type="checkbox"
                  class="toggle-input"
                  @change="scheduleAutosave"
                />
                <label for="reactions-toggle" class="toggle-slider"></label>
              </div>
            </div>

            <button type="button" class="btn btn-secondary btn-reset" @click="resetForm">Reset</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useChatsStore } from '@/stores/chats'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ErrorAlert from '@/components/ErrorAlert.vue'

const route = useRoute()
const router = useRouter()
const chatsStore = useChatsStore()

const autosaveStatus = ref(null)
const autosaveTimeout = ref(null)
const statusTimeout = ref(null)

const AUTOSAVE_DELAY = 1500

const form = reactive({
  banEmoji: '',
  forgiveEmoji: '',
  votesRequired: 0,
  minMessagesForTrust: 0,
  enabled: true,
  toggleDeleteMessage: true,
  deleteOnly: false,
  enableReactions: false,
})

const chatId = computed(() => route.params.chatId)

const autosaveStatusText = computed(() => {
  switch (autosaveStatus.value) {
    case 'saving':
      return 'Saving...'
    case 'success':
      return 'Saved'
    case 'error':
      return 'Save failed'
    default:
      return ''
  }
})

const loadConfig = async () => {
  try {
    await chatsStore.fetchChatConfig(chatId.value)
    resetForm()
  } catch {
    // error handled in store
  }
}

const resetForm = () => {
  if (chatsStore.chatConfig?.config) {
    const c = chatsStore.chatConfig.config
    form.banEmoji = c.banEmoji || ''
    form.forgiveEmoji = c.forgiveEmoji || ''
    form.votesRequired = c.votesRequired || 0
    form.minMessagesForTrust = c.minMessagesForTrust || 0
    form.enabled = c.enabled ?? true
    form.toggleDeleteMessage = c.toggleDeleteMessage ?? true
    form.deleteOnly = c.deleteOnly ?? false
    form.enableReactions = c.enableReactions ?? false
  }
}

const scheduleAutosave = () => {
  if (autosaveTimeout.value) {
    clearTimeout(autosaveTimeout.value)
  }
  autosaveStatus.value = null

  autosaveTimeout.value = setTimeout(() => {
    saveConfig()
  }, AUTOSAVE_DELAY)
}

const saveConfig = async () => {
  try {
    autosaveStatus.value = 'saving'
    await chatsStore.saveChatConfig(chatId.value, { ...form })
    autosaveStatus.value = 'success'
    statusTimeout.value = setTimeout(() => {
      autosaveStatus.value = null
    }, 2000)
  } catch {
    autosaveStatus.value = 'error'
    statusTimeout.value = setTimeout(() => {
      autosaveStatus.value = null
    }, 3000)
  }
}

const goBack = () => {
  router.push(`/chat/${chatId.value}`)
}

watch(() => route.params.chatId, () => {
  loadConfig()
}, { immediate: true })

onUnmounted(() => {
  if (autosaveTimeout.value) {
    clearTimeout(autosaveTimeout.value)
  }
  if (statusTimeout.value) {
    clearTimeout(statusTimeout.value)
  }
})
</script>

<style scoped>
.config-wrapper {
  max-width: 1400px;
}

.status-text {
  font-size: 13px;
  padding: 6px 12px;
  border-radius: 4px;
  font-weight: 500;
}

.status-text.saving {
  color: #0c5ed5;
  background-color: #e7f1ff;
}

.status-text.success {
  color: #155724;
  background-color: #d4edda;
}

.status-text.error {
  color: #721c24;
  background-color: #f8d7da;
}

.config-grid {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 30px;
  max-width: 1200px;
}

.config-card {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.config-card h2 {
  margin-top: 0;
  margin-bottom: 25px;
  font-size: 18px;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  font-size: 14px;
  color: #333;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-control:disabled {
  background-color: #e9ecef;
  color: #999;
  cursor: not-allowed;
}

.toggles-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.toggle-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px;
  background-color: #f9f9f9;
  border-radius: 8px;
  border: 1px solid #e9ecef;
  transition: background-color 0.3s ease;
}

.toggle-item:hover {
  background-color: #f0f0f0;
}

.toggle-item.disabled {
  opacity: 0.5;
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.toggle-item.disabled:hover {
  background-color: #f5f5f5;
}

.toggle-label {
  font-weight: 500;
  font-size: 14px;
  color: #333;
  margin: 0;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 26px;
}

.toggle-input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: 0.3s;
  border-radius: 26px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

.toggle-input:checked + .toggle-slider {
  background-color: #28a745;
}

.toggle-input:checked + .toggle-slider:before {
  transform: translateX(24px);
}

.btn-reset {
  width: 100%;
  padding: 10px 20px;
  margin-top: 10px;
}

@media (max-width: 1024px) {
  .config-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .config-card {
    padding: 20px;
  }

  .config-grid {
    gap: 20px;
  }
}
</style>
