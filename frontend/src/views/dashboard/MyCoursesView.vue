<template>
  <div>
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-ink">كورساتي</h1>
        <p class="mt-1 text-sm text-ink-muted">كل الاشتراكات المرتبطة بحسابك.</p>
      </div>
      <RouterLink to="/courses" class="btn-outline inline-flex items-center gap-2">
        <Compass :size="16" />
        تصفح الكورسات
      </RouterLink>
    </div>

    <div v-if="loading" class="mt-6 grid gap-4 sm:grid-cols-2">
      <div v-for="i in 2" :key="i" class="card skeleton h-44" />
    </div>

    <div v-else-if="enrollments.length" class="mt-6 grid gap-4 sm:grid-cols-2">
      <div v-for="item in enrollments" :key="item.enrollment.id" class="card overflow-hidden">
        <div class="flex gap-4 p-5">
          <img v-if="item.course.thumbnail" :src="item.course.thumbnail" class="h-20 w-32 shrink-0 rounded-lg object-cover" :alt="item.course.title" />
          <div v-else class="flex h-20 w-32 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-faint"><BookOpen :size="24" /></div>
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-bold text-ink line-clamp-2 text-sm">{{ item.course.title }}</h3>
              <StatusBadge :status="item.enrollment.status" />
            </div>
            <p class="mt-1 text-xs text-ink-faint">{{ item.course.instructor?.name }}</p>
          </div>
        </div>
        <div class="border-t border-line/60 px-5 py-3.5">
          <div v-if="item.enrollment.status === 'active' || item.enrollment.status === 'completed'" class="flex items-center gap-3">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-stone-100">
              <div class="h-full rounded-full bg-emerald" :style="{ width: (item.progress?.percent || 0) + '%' }" />
            </div>
            <span class="text-xs font-semibold text-ink-muted">{{ item.progress?.percent || 0 }}%</span>
            <RouterLink :to="`/dashboard/courses/${item.course.slug}`" class="btn-primary !px-3 !py-1.5 !text-xs ms-auto">فتح</RouterLink>
          </div>
          <div v-else class="flex items-center justify-between text-xs text-ink-faint">
            <span>غير مفعل — تواصل معنا للتفعيل</span>
            <a v-if="wa(item.course.title)" :href="wa(item.course.title)" target="_blank" rel="noopener" class="btn-emerald !px-3 !py-1.5 !text-xs">تفعيل عبر WhatsApp</a>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-else title="لا توجد كورسات بعد" subtitle="لم يتم تفعيل أي كورس على حسابك حتى الآن." class="mt-6" :icon="BookOpen">
      <RouterLink to="/courses" class="btn-primary mt-4">تصفح الكورسات</RouterLink>
    </EmptyState>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import { whatsappUrl } from '../../stores/settings';
import StatusBadge from '../../components/StatusBadge.vue';
import EmptyState from '../../components/EmptyState.vue';
import { BookOpen, Compass } from '@lucide/vue';

const enrollments = ref([]);
const loading = ref(true);

const wa = (title) => whatsappUrl(title);

onMounted(async () => {
  try {
    const { data } = await api.get('/me/courses');
    enrollments.value = data.enrollments || [];
  } finally {
    loading.value = false;
  }
});
</script>
