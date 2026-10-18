<template>
  <div class="container-site py-10 sm:py-14">
    <div class="max-w-2xl">
      <h1 class="text-2xl sm:text-3xl font-bold text-ink">كل الكورسات</h1>
      <p class="mt-2 text-ink-muted">مسارات تعليمية مكتملة — من الأساسيات إلى بناء مشاريع حقيقية.</p>
    </div>

    <div class="mt-8 flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <Search :size="17" class="absolute start-3.5 top-1/2 -translate-y-1/2 text-ink-faint" />
        <input v-model="q" class="input ps-10" placeholder="ابحث عن كورس…" @input="debouncedSearch" />
      </div>
      <select v-model="level" class="input sm:w-48" @change="load(1)">
        <option value="">كل المستويات</option>
        <option value="beginner">مبتدئ</option>
        <option value="intermediate">متوسط</option>
        <option value="advanced">متقدم</option>
      </select>
    </div>

    <div v-if="loading" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 6" :key="i" class="card overflow-hidden">
        <div class="skeleton aspect-[16/9] !rounded-none" />
        <div class="p-5 space-y-3"><div class="skeleton h-4 w-3/4" /><div class="skeleton h-3 w-full" /></div>
      </div>
    </div>

    <template v-else>
      <div v-if="courses.data?.length" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <CourseCard v-for="c in courses.data" :key="c.id" :course="c" />
      </div>
      <EmptyState v-else title="لا توجد نتائج" subtitle="جرّب كلمة بحث مختلفة أو تصفح كل المستويات." class="mt-8" :icon="Search" />
      <Pagination :data="courses" @change="load" />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import CourseCard from '../../components/CourseCard.vue';
import EmptyState from '../../components/EmptyState.vue';
import Pagination from '../../components/Pagination.vue';
import { Search } from '@lucide/vue';

const courses = ref({});
const loading = ref(true);
const q = ref('');
const level = ref('');
let timer = null;

function debouncedSearch() {
  clearTimeout(timer);
  timer = setTimeout(() => load(1), 350);
}

async function load(page = 1) {
  loading.value = true;
  try {
    const { data } = await api.get('/courses', { params: { page, q: q.value || undefined, level: level.value || undefined } });
    courses.value = data;
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>
