<template>
  <div class="page-header">
    <BackButton v-if="showBack" :title="backTitle" @click="$emit('back')" />
    <div class="header-content">
      <h1>{{ title }}</h1>
      <div v-if="subtitle" class="header-subtitle">{{ subtitle }}</div>
    </div>
    <div v-if="$slots.actions" class="header-actions">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup>
import BackButton from '@/components/BackButton.vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  showBack: {
    type: Boolean,
    default: false,
  },
  backTitle: {
    type: String,
    default: 'Go back',
  },
})

defineEmits(['back'])
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 2px solid #e9ecef;
}

.header-content {
  flex: 1;
}

.header-content h1 {
  margin: 0;
  font-size: 28px;
  color: #333;
}

.header-subtitle {
  margin-top: 8px;
  font-size: 14px;
  color: #666;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

@media (max-width: 768px) {
  .page-header {
    padding-bottom: 15px;
    margin-bottom: 20px;
  }

  .header-content h1 {
    font-size: 22px;
  }

  .header-subtitle {
    font-size: 12px;
  }
}
</style>
