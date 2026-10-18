<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="fixed inset-0 z-[90] flex items-end sm:items-center justify-center p-0 sm:p-6" role="dialog" aria-modal="true" :class="{ 'admin-scope': isAdminArea }">
        <div class="absolute inset-0 bg-ink/40" @click="$emit('close')" />
        <div class="relative w-full card !rounded-b-none sm:!rounded-b-xl2 p-5 sm:p-6 max-h-[90vh] overflow-y-auto" :class="wide ? 'sm:max-w-4xl' : 'sm:max-w-lg'">
          <div class="flex items-start justify-between gap-4 mb-4">
            <h3 class="text-lg font-bold text-ink">{{ title }}</h3>
            <button class="text-ink-faint hover:text-ink" @click="$emit('close')" aria-label="close">
              <X :size="20" />
            </button>
          </div>
          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { X } from '@lucide/vue';
defineProps({ open: Boolean, title: String, wide: Boolean });
defineEmits(['close']);
const route = useRoute();
const isAdminArea = computed(() => route.path.startsWith('/admin'));
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
