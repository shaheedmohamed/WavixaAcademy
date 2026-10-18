<template>
  <div v-if="loading" class="container-site py-14">
    <div class="skeleton h-8 w-2/3 mb-4" />
    <div class="skeleton h-4 w-1/2" />
  </div>

  <div v-else-if="course" class="pb-16">
    <!-- Header -->
    <section class="bg-ink text-white">
      <div class="container-site grid gap-8 py-10 sm:py-14 lg:grid-cols-3">
        <div class="lg:col-span-2">
          <nav class="mb-4 flex items-center gap-2 text-xs text-stone-400">
            <RouterLink to="/" class="hover:text-white">الرئيسية</RouterLink>
            <span>/</span>
            <RouterLink to="/courses" class="hover:text-white">الكورسات</RouterLink>
            <span>/</span>
            <span class="text-stone-300">{{ course.title }}</span>
          </nav>
          <h1 class="text-2xl sm:text-3xl font-bold leading-snug">{{ course.title }}</h1>
          <p class="mt-4 text-stone-300 leading-relaxed max-w-2xl">{{ course.short_description }}</p>
          <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-stone-300">
            <span class="flex items-center gap-1.5"><UserCircle :size="16" /> {{ course.instructor?.name || 'فريق المنصة' }}</span>
            <span class="flex items-center gap-1.5"><PlayCircle :size="16" /> {{ lessonsCount }} درسًا</span>
            <span class="flex items-center gap-1.5"><Clock :size="16" /> {{ durationLabel }}</span>
            <span class="badge bg-white/10 text-white">{{ levelLabel }}</span>
          </div>
        </div>

        <!-- Pricing card -->
        <div class="card bg-white text-ink p-6 h-fit lg:sticky lg:top-24">
          <img v-if="course.thumbnail" :src="course.thumbnail" :alt="course.title" class="mb-4 aspect-video w-full rounded-lg object-cover" />
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold">{{ priceLabel }}</span>
          </div>
          <p class="mt-1 text-xs text-ink-muted">وصول كامل بعد تفعيل الاشتراك</p>

          <template v-if="canAccess">
            <RouterLink :to="`/dashboard/courses/${course.slug}`" class="btn-emerald mt-5 w-full">
              <PlayCircle :size="17" /> متابعة التعلم
            </RouterLink>
          </template>
          <template v-else-if="auth.user">
            <div class="mt-5 rounded-lg border border-amber-soft bg-amber-soft/40 p-3.5 text-sm text-amber-900">
              <p class="font-semibold">هذا الكورس غير مفعل على حسابك.</p>
              <p class="mt-1 text-xs">تواصل معنا لتفعيل الاشتراك.</p>
            </div>
            <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener" class="btn-emerald mt-3 w-full">
              <MessageCircle :size="17" /> تواصل معنا عبر WhatsApp للتسجيل
            </a>
          </template>
          <template v-else>
            <RouterLink :to="{ name: 'register' }" class="btn-primary mt-5 w-full">إنشاء حساب للتسجيل</RouterLink>
            <RouterLink :to="{ name: 'login' }" class="btn-outline mt-2.5 w-full">لديك حساب؟ سجّل الدخول</RouterLink>
          </template>

          <ul class="mt-5 space-y-2.5 border-t border-line pt-5 text-xs text-ink-muted">
            <li class="flex items-center gap-2"><CheckCircle2 :size="15" class="text-emerald" /> وصول كامل لكل الدروس والموارد</li>
            <li class="flex items-center gap-2"><CheckCircle2 :size="15" class="text-emerald" /> مشاريع تُراجع من المدرب</li>
            <li class="flex items-center gap-2"><CheckCircle2 :size="15" class="text-emerald" /> فعاليات وجلسات مباشرة</li>
            <li class="flex items-center gap-2"><CheckCircle2 :size="15" class="text-emerald" /> شهادة إتمام موثقة</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Content -->
    <section class="container-site mt-10 grid gap-10 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-10">
        <div>
          <h2 class="section-title">عن هذا الكورس</h2>
          <p class="mt-4 whitespace-pre-line text-ink-soft leading-relaxed">{{ course.description || course.short_description }}</p>
        </div>

        <!-- Curriculum -->
        <div>
          <h2 class="section-title">محتوى الكورس</h2>
          <p class="section-sub">{{ course.modules?.length || 0 }} وحدة · {{ lessonsCount }} درسًا</p>
          <div class="mt-5 space-y-3">
            <div v-for="(mod, i) in course.modules" :key="mod.id" class="card overflow-hidden">
              <div class="flex items-center gap-3 border-b border-line bg-surface px-5 py-3.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-ink text-white text-xs font-bold">{{ i + 1 }}</span>
                <div class="min-w-0">
                  <h3 class="font-bold text-ink text-sm truncate">{{ mod.title }}</h3>
                  <p class="text-xs text-ink-faint">{{ mod.lessons?.length || 0 }} درسًا</p>
                </div>
              </div>
              <ul class="divide-y divide-line/60">
                <li v-for="lesson in mod.lessons" :key="lesson.id" class="flex items-center gap-3 px-5 py-3">
                  <PlayCircle :size="16" class="shrink-0 text-ink-faint" />
                  <span class="flex-1 text-sm text-ink-soft truncate">{{ lesson.title }}</span>
                  <span v-if="lesson.is_preview" class="badge-green">معاينة مجانية</span>
                  <span class="text-xs text-ink-faint">{{ formatDuration(lesson.duration_seconds) }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Instructor -->
      <aside v-if="course.instructor" class="space-y-5">
        <div class="card p-6">
          <h3 class="font-bold text-ink mb-4">المدرب</h3>
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-faint text-primary font-bold">
              {{ course.instructor.name?.charAt(0) }}
            </div>
            <div>
              <p class="font-bold text-ink">{{ course.instructor.name }}</p>
              <p class="text-xs text-ink-muted">{{ course.instructor.instructor_profile?.title || 'مدرب' }}</p>
            </div>
          </div>
          <p v-if="course.instructor.instructor_profile?.bio" class="mt-4 text-sm text-ink-muted leading-relaxed">
            {{ course.instructor.instructor_profile.bio }}
          </p>
        </div>
      </aside>
    </section>
  </div>

  <NotFoundView v-else />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import { auth } from '../../stores/auth';
import NotFoundView from './NotFoundView.vue';
import { UserCircle, PlayCircle, Clock, CheckCircle2, MessageCircle } from '@lucide/vue';

const route = useRoute();
const course = ref(null);
const canAccess = ref(false);
const whatsappUrl = ref(null);
const loading = ref(true);

const lessonsCount = computed(() =>
  (course.value?.modules || []).reduce((sum, m) => sum + (m.lessons?.length || 0), 0));

const durationLabel = computed(() => {
  const mins = course.value?.duration_minutes
    || Math.round((course.value?.modules || []).flatMap((m) => m.lessons || []).reduce((s, l) => s + (l.duration_seconds || 0), 0) / 60);
  if (!mins) return '—';
  const h = Math.floor(mins / 60);
  return h > 0 ? `${h} س ${mins % 60} د` : `${mins} د`;
});

const levelLabel = computed(() => ({
  beginner: 'مبتدئ', intermediate: 'متوسط', advanced: 'متقدم', all_levels: 'كل المستويات',
}[course.value?.level] || ''));

const priceLabel = computed(() => {
  const price = Number(course.value?.price || 0);
  return price > 0 ? `${price} ${course.value.currency || 'EGP'}` : 'مجاني';
});

function formatDuration(s) {
  if (!s) return '—';
  const m = Math.floor(s / 60);
  return `${m}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/courses/${route.params.slug}`);
    course.value = data.course;
    canAccess.value = data.can_access;
    whatsappUrl.value = data.whatsapp_url;
    document.title = `${data.course.title} — أكاديمية`;
  } catch {
    course.value = null;
  } finally {
    loading.value = false;
  }
});
</script>
