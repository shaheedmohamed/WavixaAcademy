<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-ink">لوحة المتابعة</h1>
      <p class="mt-1 text-sm text-ink-muted">نظرة عامة على كورساتك والطلاب.</p>
    </div>

    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="i in 4" :key="i" class="card skeleton h-24" />
    </div>

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="s in statCards" :key="s.label" class="card p-5">
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold text-ink-muted">{{ s.label }}</p>
            <component :is="s.icon" :size="18" :class="s.color" />
          </div>
          <p class="mt-2 text-2xl font-bold text-ink">{{ s.value }}</p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <section class="card overflow-hidden">
          <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-bold text-ink">أحدث الاشتراكات</h2>
            <RouterLink to="/instructor/courses" class="link text-xs">كل الكورسات</RouterLink>
          </div>
          <table class="table-base">
            <tbody>
              <tr v-for="e in stats.recent_enrollments" :key="e.id">
                <td>
                  <p class="font-semibold text-ink">{{ e.user?.name || '—' }}</p>
                  <p class="text-xs text-ink-faint">{{ e.user?.email || '—' }}</p>
                </td>
                <td class="text-xs text-ink-muted">{{ e.course?.title || '—' }}</td>
                <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(e.created_at).toLocaleDateString('ar-EG') }}</td>
              </tr>
              <tr v-if="!stats.recent_enrollments?.length"><td colspan="3" class="py-8 text-center text-ink-faint">لا توجد اشتراكات حديثة.</td></tr>
            </tbody>
          </table>
        </section>

        <section class="card overflow-hidden">
          <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-bold text-ink">تسليمات بانتظار المراجعة</h2>
            <RouterLink to="/instructor/courses" class="link text-xs">الكورسات</RouterLink>
          </div>
          <table class="table-base">
            <tbody>
              <tr v-for="s in stats.recent_submissions" :key="s.id">
                <td class="text-sm text-ink">{{ s.user?.name || '—' }}</td>
                <td class="text-xs text-ink-muted">{{ s.project?.title || '—' }}</td>
                <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(s.created_at).toLocaleDateString('ar-EG') }}</td>
              </tr>
              <tr v-if="!stats.recent_submissions?.length"><td colspan="3" class="py-8 text-center text-ink-faint">لا توجد تسليمات جديدة.</td></tr>
            </tbody>
          </table>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../api/client';
import { BookOpen, GraduationCap, Users, FileQuestion } from '@lucide/vue';

const stats = ref({});
const loading = ref(true);

const statCards = computed(() => [
  { label: 'الكورسات', value: stats.value.courses ?? '—', icon: BookOpen, color: 'text-primary' },
  { label: 'منشور', value: stats.value.published_courses ?? '—', icon: GraduationCap, color: 'text-emerald' },
  { label: 'اشتراكات نشطة', value: stats.value.active_enrollments ?? '—', icon: Users, color: 'text-primary' },
  { label: 'تسليمات معلقة', value: stats.value.pending_submissions ?? '—', icon: FileQuestion, color: 'text-amber' },
]);

onMounted(async () => {
  try {
    const { data } = await api.get('/instructor/stats');
    stats.value = data;
  } finally {
    loading.value = false;
  }
});
</script>
