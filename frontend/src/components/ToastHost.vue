<template>
  <div class="fixed bottom-4 inset-x-0 z-[100] flex flex-col items-center gap-2 px-4 pointer-events-none">
    <TransitionGroup name="toast">
      <div
        v-for="t in toasts" :key="t.id"
        class="pointer-events-auto flex items-center gap-3 rounded-lg border px-4 py-3 text-sm font-medium shadow-pop max-w-md w-full sm:w-auto"
        :class="{
          'bg-white border-emerald-soft text-emerald-800': t.type === 'success',
          'bg-white border-danger-soft text-red-800': t.type === 'error',
          'bg-white border-line text-ink': t.type === 'info',
        }"
      >
        <CheckCircle2 v-if="t.type === 'success'" :size="18" class="text-emerald shrink-0" />
        <AlertCircle v-else-if="t.type === 'error'" :size="18" class="text-danger shrink-0" />
        <Info v-else :size="18" class="text-primary shrink-0" />
        <span>{{ t.message }}</span>
        <button class="ms-auto text-ink-faint hover:text-ink" @click="dismiss(t.id)">
          <X :size="15" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { toasts, dismiss } from '../stores/toast';
import { CheckCircle2, AlertCircle, Info, X } from '@lucide/vue';
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all .25s ease; }
.toast-enter-from { opacity: 0; transform: translateY(12px); }
.toast-leave-to { opacity: 0; transform: translateY(6px); }
</style>
