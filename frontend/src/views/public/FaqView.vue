<template>
  <div class="container-site py-12 sm:py-16 max-w-3xl">
    <h1 class="text-2xl sm:text-3xl font-bold text-ink">الأسئلة الشائعة</h1>
    <p class="mt-2 text-ink-muted">إجابات عن أكثر ما يسأل عنه الطلاب.</p>

    <div v-if="loading" class="mt-8 space-y-3"><div v-for="i in 5" :key="i" class="skeleton h-16" /></div>
    <div v-else-if="faqs.length" class="mt-8 space-y-3">
      <details v-for="f in faqs" :key="f.id" class="card group px-5 py-4">
        <summary class="flex cursor-pointer items-center justify-between gap-4 text-sm font-semibold text-ink list-none">
          {{ f.question }}
          <ChevronDown :size="17" class="shrink-0 text-ink-faint transition-transform group-open:rotate-180" />
        </summary>
        <p class="mt-3 text-sm leading-relaxed text-ink-muted whitespace-pre-line">{{ f.answer }}</p>
      </details>
    </div>
    <EmptyState v-else title="لا توجد أسئلة منشورة بعد" class="mt-8" :icon="HelpCircle" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import EmptyState from '../../components/EmptyState.vue';
import { ChevronDown, HelpCircle } from '@lucide/vue';

const faqs = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/faqs');
    faqs.value = data.faqs || [];
  } finally {
    loading.value = false;
  }
});
</script>
