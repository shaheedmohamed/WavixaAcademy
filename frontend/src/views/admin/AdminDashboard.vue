<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-ink">لوحة المتابعة</h1>
      <p class="mt-1 text-sm text-ink-muted">نظرة عامة على المنصة الآن.</p>
    </div>

    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="i in 8" :key="i" class="card skeleton h-24" />
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
        <!-- Recent students -->
        <section class="card overflow-hidden">
          <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-bold text-ink">أحدث الطلاب</h2>
            <RouterLink to="/admin/students" class="link text-xs">الكل</RouterLink>
          </div>
          <table class="table-base">
            <tbody>
              <tr v-for="s in stats.recent_students" :key="s.id">
                <td>
                  <RouterLink :to="`/admin/students/${s.id}`" class="font-semibold text-ink hover:text-primary">{{ s.name }}</RouterLink>
                  <p class="text-xs text-ink-faint" dir="ltr">{{ s.student_number }}</p>
                </td>
                <td class="text-xs text-ink-muted">{{ s.email }}</td>
                <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(s.created_at).toLocaleDateString('ar-EG') }}</td>
              </tr>
              <tr v-if="!stats.recent_students?.length"><td class="py-8 text-center text-ink-faint">لا يوجد طلاب بعد</td></tr>
            </tbody>
          </table>
        </section>

        <!-- Recent imports -->
        <section class="card overflow-hidden">
          <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-bold text-ink">أحدث استيرادات الفيديو</h2>
            <RouterLink to="/admin/video-imports" class="link text-xs">الكل</RouterLink>
          </div>
          <table class="table-base">
            <tbody>
              <tr v-for="a in stats.recent_imports" :key="a.id">
                <td class="text-sm text-ink">{{ a.lesson?.title || '—' }}</td>
                <td><StatusBadge :status="a.status" /></td>
                <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(a.created_at).toLocaleDateString('ar-EG') }}</td>
              </tr>
              <tr v-if="!stats.recent_imports?.length"><td class="py-8 text-center text-ink-faint">لا توجد استيرادات</td></tr>
            </tbody>
          </table>
        </section>

        <!-- Security alerts -->
        <section class="card overflow-hidden lg:col-span-2">
          <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-bold text-ink">تنبيهات الأمان الأخيرة</h2>
            <RouterLink to="/admin/security" class="link text-xs">مركز الأمان</RouterLink>
          </div>
          <table class="table-base">
            <tbody>
              <tr v-for="e in stats.recent_security" :key="e.id">
                <td><StatusBadge :status="e.severity" /></td>
                <td class="text-sm text-ink">{{ e.description || e.title }}</td>
                <td class="text-xs text-ink-muted">{{ e.user?.name || '—' }}</td>
                <td><StatusBadge :status="e.status" /></td>
              </tr>
              <tr v-if="!stats.recent_security?.length"><td class="py-8 text-center text-ink-faint">لا توجد تنبيهات — كل شيء هادئ.</td></tr>
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
import StatusBadge from '../../components/StatusBadge.vue';
import { Users, KeyRound, BookOpen, CalendarDays, Award, ShieldAlert, Video, GraduationCap } from '@lucide/vue';

const stats = ref({});
const loading = ref(true);

const statCards = computed(() => [
  { label: 'الطلاب', value: stats.value.students ?? '—', icon: Users, color: 'text-primary' },
  { label: 'اشتراكات نشطة', value: stats.value.active_enrollments ?? '—', icon: KeyRound, color: 'text-emerald' },
  { label: 'الكورسات', value: stats.value.courses ?? '—', icon: BookOpen, color: 'text-primary' },
  { label: 'فعاليات قادمة', value: stats.value.upcoming_events ?? '—', icon: CalendarDays, color: 'text-amber' },
  { label: 'اشتراكات مكتملة', value: stats.value.completed_enrollments ?? '—', icon: GraduationCap, color: 'text-emerald' },
  { label: 'الشهادات', value: stats.value.certificates ?? '—', icon: Award, color: 'text-amber' },
  { label: 'جلسات فيديو نشطة', value: stats.value.active_video_sessions ?? '—', icon: Video, color: 'text-primary' },
  { label: 'تنبيهات أمنية مفتوحة', value: stats.value.open_security_events ?? '—', icon: ShieldAlert, color: 'text-danger' },
]);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/stats');
    stats.value = data;
  } finally {
    loading.value = false;
  }
});
</script>
