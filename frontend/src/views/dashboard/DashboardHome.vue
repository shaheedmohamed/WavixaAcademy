<template>
  <div class="space-y-8">
    <!-- Welcome -->
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-ink">أهلًا بك، {{ firstName }}</h1>
      <p class="mt-1 text-sm text-ink-muted">{{ greeting }}</p>
    </div>

    <div v-if="loading" class="grid gap-4 sm:grid-cols-2">
      <div class="card skeleton h-36" /><div class="card skeleton h-36" />
    </div>

    <template v-else>
      <!-- Continue learning -->
      <section v-if="activeEnrollments.length">
        <h2 class="mb-4 text-base font-bold text-ink">متابعة التعلم</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <div v-for="item in activeEnrollments" :key="item.enrollment.id" class="card p-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <RouterLink :to="`/dashboard/courses/${item.course.slug}`" class="font-bold text-ink hover:text-primary line-clamp-1">
                  {{ item.course.title }}
                </RouterLink>
                <p class="mt-1 text-xs text-ink-muted">
                  أنجزت {{ item.percent }}% · {{ item.completed_lessons }}/{{ item.total_lessons }} درسًا
                </p>
              </div>
              <StatusBadge :status="item.enrollment.status" />
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-100">
              <div class="h-full rounded-full bg-emerald transition-all" :style="{ width: item.percent + '%' }" />
            </div>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-xs text-ink-faint truncate">
                {{ item.last_lesson ? 'آخر درس: ' + item.last_lesson.title : 'لم تبدأ بعد' }}
              </span>
              <RouterLink :to="`/dashboard/courses/${item.course.slug}`" class="btn-primary !px-3.5 !py-1.5 !text-xs shrink-0">
                متابعة
              </RouterLink>
            </div>
          </div>
        </div>
      </section>

      <EmptyState v-else title="لا توجد كورسات مفعلة بعد"
        subtitle="تصفح الكورسات وفعّل اشتراكك عبر التواصل معنا على WhatsApp."
        :icon="BookOpen">
        <RouterLink to="/courses" class="btn-primary mt-4">استكشف الكورسات</RouterLink>
      </EmptyState>

      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Upcoming events -->
        <section class="card p-5">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-bold text-ink">فعاليات قادمة</h2>
            <RouterLink to="/dashboard/events" class="link text-xs">الكل</RouterLink>
          </div>
          <ul v-if="data.upcoming_events?.length" class="space-y-3">
            <li v-for="e in data.upcoming_events" :key="e.id" class="flex items-center gap-3">
              <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-primary-faint text-primary">
                <span class="text-sm font-bold leading-none">{{ new Date(e.start_at).getDate() }}</span>
                <span class="text-[10px]">{{ new Date(e.start_at).toLocaleDateString('ar-EG', { month: 'short' }) }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <RouterLink :to="`/events/${e.slug}`" class="block truncate text-sm font-semibold text-ink hover:text-primary">{{ e.title }}</RouterLink>
                <p class="text-xs text-ink-faint">{{ new Date(e.start_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) }} · {{ e.location || 'أونلاين' }}</p>
              </div>
            </li>
          </ul>
          <p v-else class="py-6 text-center text-sm text-ink-faint">لا توجد فعاليات قادمة.</p>
        </section>

        <!-- Notifications -->
        <section class="card p-5">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-bold text-ink">آخر الإشعارات</h2>
            <RouterLink to="/dashboard/notifications" class="link text-xs">الكل</RouterLink>
          </div>
          <ul v-if="data.notifications?.length" class="space-y-3">
            <li v-for="n in data.notifications" :key="n.id" class="flex items-start gap-3">
              <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="n.read_at ? 'bg-line' : 'bg-primary'" />
              <div class="min-w-0">
                <p class="text-sm font-semibold text-ink">{{ n.data.title }}</p>
                <p class="text-xs text-ink-muted line-clamp-1">{{ n.data.body }}</p>
                <p class="mt-0.5 text-[11px] text-ink-faint">{{ timeAgo(n.created_at) }}</p>
              </div>
            </li>
          </ul>
          <p v-else class="py-6 text-center text-sm text-ink-faint">لا إشعارات جديدة.</p>
        </section>
      </div>

      <!-- Certificates -->
      <section v-if="data.certificates?.length" class="card p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-base font-bold text-ink">شهاداتك</h2>
          <RouterLink to="/dashboard/certificates" class="link text-xs">الكل</RouterLink>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <div v-for="c in data.certificates" :key="c.id" class="flex items-center gap-3 rounded-lg border border-line p-3.5">
            <Award :size="22" class="shrink-0 text-amber" />
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-ink">{{ c.course?.title }}</p>
              <p class="text-xs text-ink-faint" dir="ltr">{{ c.certificate_number }}</p>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../api/client';
import { auth } from '../../stores/auth';
import StatusBadge from '../../components/StatusBadge.vue';
import EmptyState from '../../components/EmptyState.vue';
import { BookOpen, Award } from '@lucide/vue';

const data = ref({});
const loading = ref(true);

const firstName = computed(() => (auth.user?.name || '').split(' ')[0]);
const greeting = computed(() => {
  const h = new Date().getHours();
  return h < 12 ? 'صباح الخير — يوم جيد للتعلم.' : h < 18 ? 'مساء الخير — لنكمل من حيث توقفت.' : 'مساء الخير — جلسة هادئة مثالية للتعلم.';
});
const activeEnrollments = computed(() => (data.value.enrollments || []).filter((e) => e.enrollment.status === 'active'));

function timeAgo(ts) {
  const diff = Date.now() - new Date(ts);
  const m = Math.floor(diff / 60000);
  if (m < 60) return `منذ ${m} د`;
  const h = Math.floor(m / 60);
  if (h < 24) return `منذ ${h} س`;
  return `منذ ${Math.floor(h / 24)} يوم`;
}

onMounted(async () => {
  try {
    const { data: res } = await api.get('/me/dashboard');
    data.value = res;
  } finally {
    loading.value = false;
  }
});
</script>
