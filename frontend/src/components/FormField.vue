<template>
  <div>
    <label v-if="label" :for="id" class="label">{{ label }} <span v-if="required" class="text-danger">*</span></label>
    <slot />
    <p v-if="error" class="mt-1 text-xs text-danger">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-xs text-ink-faint">{{ hint }}</p>
  </div>
</template>

<script setup>
defineProps({ label: String, error: String, hint: String, required: Boolean, id: String });
</script>
