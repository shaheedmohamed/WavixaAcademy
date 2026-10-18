<template>
  <div class="card flex flex-col items-center justify-center py-14 px-6 text-center">
    <div class="mb-3 rounded-full bg-surface p-4 text-ink-faint">
      <component :is="icon || Inbox" :size="28" />
    </div>
    <h3 class="text-base font-semibold text-ink">{{ title }}</h3>
    <p v-if="subtitle" class="mt-1 text-sm text-ink-muted max-w-sm">{{ subtitle }}</p>
    <slot />
  </div>
</template>

<script setup>
import { Inbox } from '@lucide/vue';
defineProps({ title: String, subtitle: String, icon: [Object, Function] });
</script>
