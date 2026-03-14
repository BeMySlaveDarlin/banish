<template>
  <div v-if="message" class="alert alert-danger">
    <span class="error-text">{{ message }}</span>
    <button v-if="dismissible" class="error-dismiss" @click="$emit('dismiss')">&times;</button>
  </div>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    default: '',
  },
  dismissible: {
    type: Boolean,
    default: true,
  },
})

defineEmits(['dismiss'])
</script>

<style scoped>
.alert-danger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.error-text {
  flex: 1;
}

.error-dismiss {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #721c24;
  padding: 0 4px;
  line-height: 1;
  opacity: 0.7;
}

.error-dismiss:hover {
  opacity: 1;
}
</style>
