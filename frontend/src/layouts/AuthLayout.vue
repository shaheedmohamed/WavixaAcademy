<template>
  <div class="min-h-screen bg-surface flex flex-col">
    <div class="container-site py-5">
      <RouterLink to="/" class="inline-flex items-center gap-2.5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink text-white font-bold text-sm">W</div>
        <span class="text-lg font-bold text-ink">{{ settings.platform_name }}</span>
      </RouterLink>
    </div>
    <div class="flex-1 flex items-start sm:items-center justify-center px-4 pb-16">
      <div class="w-full max-w-md">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { settings, loadSettings } from '../stores/settings';
onMounted(() => loadSettings());
</script>
