<template>
  <Teleport to="body">
    <div v-if="visible" class="dialog-overlay" @click.self="cancel">
      <div class="dialog-card">
        <div class="dialog-header">
          <h3>{{ title }}</h3>
        </div>
        <div class="dialog-body">
          <p>{{ message }}</p>
        </div>
        <div class="dialog-footer">
          <button class="btn btn-secondary" @click="cancel">Cancel</button>
          <button :class="['btn', `btn-${confirmVariant}`]" @click="confirm">
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Confirm action',
  },
  message: {
    type: String,
    default: 'Are you sure?',
  },
  confirmText: {
    type: String,
    default: 'Confirm',
  },
  confirmVariant: {
    type: String,
    default: 'danger',
  },
})

const emit = defineEmits(['confirm', 'cancel'])

const confirm = () => emit('confirm')
const cancel = () => emit('cancel')
</script>

<style scoped>
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.dialog-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  max-width: 420px;
  width: 90%;
  overflow: hidden;
}

.dialog-header {
  padding: 20px 24px 0;
}

.dialog-header h3 {
  margin: 0;
  font-size: 18px;
  color: #333;
}

.dialog-body {
  padding: 16px 24px;
}

.dialog-body p {
  margin: 0;
  color: #666;
  font-size: 14px;
  line-height: 1.5;
}

.dialog-footer {
  padding: 12px 24px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>
