<template>
  <div class="config-wrapper">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else-if="config" class="config-content">
      <div class="config-header">
        <button class="btn-back" @click="goBack" title="Go back to chat details">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="header-left">
          <h1>Chat Configuration</h1>
          <div class="chat-context">
            <span class="chat-id">Chat #{{ chatId }}</span>
            <span v-if="config.title" class="chat-title">— {{ config.title }}</span>
          </div>
        </div>
        <div class="header-info" v-if="autosaveStatus">
          <span :class="['status-text', autosaveStatus]">{{ autosaveStatusText }}</span>
        </div>
      </div>

      <div v-if="message" :class="['alert', 'alert-' + messageType]">
        {{ message }}
      </div>
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
              >
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
              >
            </div>

            <div class="form-group">
              <label class="form-label">Votes Required to Ban</label>
              <input
                  v-model.number="form.votesRequired"
                  type="number"
                  class="form-control"
                  min="1"
                  @input="scheduleAutosave"
              >
            </div>

            <div class="form-group">
              <label class="form-label">Min Messages for Trust</label>
              <input
                  v-model.number="form.minMessagesForTrust"
                  type="number"
                  class="form-control"
                  min="0"
                  @input="scheduleAutosave"
              >
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
                    v-model="form.enabled"
                    type="checkbox"
                    class="toggle-input"
                    id="enabled-toggle"
                    @change="scheduleAutosave"
                >
                <label for="enabled-toggle" class="toggle-slider"></label>
              </div>
            </div>

            <div class="toggle-item">
              <label class="toggle-label">Delete Messages</label>
              <div class="toggle-switch">
                <input
                    v-model="form.toggleDeleteMessage"
                    type="checkbox"
                    class="toggle-input"
                    id="delete-toggle"
                    @change="scheduleAutosave"
                >
                <label for="delete-toggle" class="toggle-slider"></label>
              </div>
            </div>

            <div class="toggle-item" :class="{ disabled: !form.toggleDeleteMessage }">
              <label class="toggle-label">Only Delete</label>
              <div class="toggle-switch">
                <input
                    v-model="form.deleteOnly"
                    type="checkbox"
                    class="toggle-input"
                    id="delete-only-toggle"
                    :disabled="!form.toggleDeleteMessage"
                    @change="scheduleAutosave"
                >
                <label for="delete-only-toggle" class="toggle-slider"></label>
              </div>
            </div>

            <div class="toggle-item">
              <label class="toggle-label">Enable Reactions</label>
              <div class="toggle-switch">
                <input
                    v-model="form.enableReactions"
                    type="checkbox"
                    class="toggle-input"
                    id="reactions-toggle"
                    @change="scheduleAutosave"
                >
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
import {computed, onMounted, reactive, ref} from 'vue'
import {useRoute, useRouter} from 'vue-router'
import api from '@/utils/api'

const route = useRoute()
const router = useRouter()
const chatId = route.params.chatId
const config = ref(null)
const loading = ref(false)
const message = ref('')
const messageType = ref('info')
const autosaveStatus = ref(null)
const autosaveTimeout = ref(null)

const AUTOSAVE_DELAY = 1500

const form = reactive({
  banEmoji: '',
  forgiveEmoji: '',
  votesRequired: 0,
  minMessagesForTrust: 0,
  enabled: true,
  toggleDeleteMessage: true,
  deleteOnly: false,
  enableReactions: false
})

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
  loading.value = true
  try {
    const response = await api.get(`/api/admin/chat/${chatId}/config`)
    config.value = response.data
    resetForm()
  } catch (error) {
    console.error('Failed to load config:', error)
    message.value = 'Failed to load configuration'
    messageType.value = 'danger'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  if (config.value?.config) {
    form.banEmoji = config.value.config.banEmoji || ''
    form.forgiveEmoji = config.value.config.forgiveEmoji || ''
    form.votesRequired = config.value.config.votesRequired || 0
    form.minMessagesForTrust = config.value.config.minMessagesForTrust || 0
    form.enabled = config.value.config.enabled ?? true
    form.toggleDeleteMessage = config.value.config.toggleDeleteMessage ?? true
    form.deleteOnly = config.value.config.deleteOnly ?? false
    form.enableReactions = config.value.config.enableReactions ?? false
    message.value = ''
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
    const data = {
      banEmoji: form.banEmoji,
      forgiveEmoji: form.forgiveEmoji,
      votesRequired: form.votesRequired,
      minMessagesForTrust: form.minMessagesForTrust,
      enabled: form.enabled,
      toggleDeleteMessage: form.toggleDeleteMessage,
      deleteOnly: form.deleteOnly,
      enableReactions: form.enableReactions
    }

    await api.post(`/api/admin/chat/${chatId}/config`, data)
    autosaveStatus.value = 'success'
    setTimeout(() => {
      autosaveStatus.value = null
    }, 2000)
  } catch (error) {
    console.error('Failed to save config:', error)
    autosaveStatus.value = 'error'
    message.value = 'Failed to save config: ' + error.message
    messageType.value = 'danger'
    setTimeout(() => {
      autosaveStatus.value = null
    }, 3000)
  }
}

const goBack = () => {
  router.push(`/chat/${chatId}`)
}

onMounted(() => loadConfig())
</script>

<style scoped>
.config-wrapper {
  padding: 30px;
}

.config-header {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 2px solid #e9ecef;
}

.btn-back {
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.3s ease;
  flex-shrink: 0;
  margin-top: 4px;
}

.btn-back:hover {
  background-color: #f0f0f0;
  color: #333;
}

.btn-back svg {
  width: 20px;
  height: 20px;
}

.header-left {
  flex: 1;
}

.header-left h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  color: #333;
}

.chat-context {
  font-size: 14px;
  color: #666;
}

.chat-id {
  font-weight: 600;
}

.chat-title {
  margin-left: 5px;
  color: #999;
}

.header-info {
  display: flex;
  align-items: center;
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

.alert {
  margin: 0 30px 20px 30px;
  padding: 15px;
  border-radius: 6px;
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
  border-color: #f5c6cb;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 60px 30px;
}

.config-content {
  max-width: 1400px;
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

/* Responsive Design */
@media (max-width: 1024px) {
  .config-grid {
    grid-template-columns: 1fr;
  }

  .config-header {
    flex-direction: column;
    gap: 15px;
  }

  .header-left {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .config-wrapper {
    padding: 15px;
  }

  .config-header {
    padding-bottom: 15px;
    margin-bottom: 20px;
  }

  .config-card {
    padding: 20px;
  }

  .config-grid {
    gap: 20px;
  }

  .header-left h1 {
    font-size: 22px;
  }

  .chat-context {
    font-size: 12px;
  }
}
</style>
