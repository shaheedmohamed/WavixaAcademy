<template>
  <nav v-if="data && data.last_page > 1" class="mt-6 flex items-center justify-center gap-1" aria-label="pagination">
    <button
      class="btn-ghost !px-3 !py-1.5"
      :disabled="data.current_page <= 1"
      @click="$emit('change', data.current_page - 1)"
    >
      <ChevronRight :size="16" />
    </button>
    <button
      v-for="page in pages" :key="page"
      class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
      :class="page === data.current_page ? 'bg-ink text-white' : 'text-ink-muted hover:bg-stone-100'"
      @click="$emit('change', page)"
    >
      {{ page }}
    </button>
    <button
      class="btn-ghost !px-3 !py-1.5"
      :disabled="data.current_page >= data.last_page"
      @click="$emit('change', data.current_page + 1)"
    >
      <ChevronLeft :size="16" />
    </button>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

const props = defineProps({ data: Object });
defineEmits(['change']);

const pages = computed(() => {
  if (!props.data) return [];
  const { current_page: cur, last_page: last } = props.data;
  const window = 2;
  const set = new Set([1, last, cur - 1, cur, cur + 1]);
  for (let i = cur - window; i <= cur + window; i++) set.add(i);
  return [...set].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);
});
</script>
