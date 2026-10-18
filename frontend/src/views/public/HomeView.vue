<template>
  <div>
    <!-- Hero -->
    <section class="border-b border-line bg-white">
      <div class="container-site grid gap-10 py-14 sm:py-20 lg:grid-cols-2 lg:items-center">
        <div>
          <span class="badge-blue mb-4">منصة تعليمية عربية للبرمجة</span>
          <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold leading-[1.25] text-ink">
            اتعلم البرمجة بالطريقة<br />اللي تخليك <span class="text-primary">تبني فعلًا</span>
          </h1>
          <p class="mt-5 max-w-lg text-base sm:text-lg leading-relaxed text-ink-muted">
            كورسات عملية مبنية على مشاريع حقيقية، جلسات مباشرة مع مدربين، ومتابعة شخصية لتقدمك —
            من أول سطر كود حتى شهادة إتمام موثّقة.
          </p>
          <div class="mt-7 flex flex-wrap gap-3">
            <RouterLink to="/courses" class="btn-primary !px-6 !py-3">
              استكشف الكورسات <ArrowLeft :size="17" />
            </RouterLink>
            <RouterLink to="/events" class="btn-outline !px-6 !py-3">
              تصفح الفعاليات
            </RouterLink>
          </div>
          <div class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm text-ink-muted">
            <span class="flex items-center gap-2"><PlayCircle :size="17" class="text-primary" /> فيديوهات محمية بصلاحيات وصول</span>
            <span class="flex items-center gap-2"><FolderKanban :size="17" class="text-primary" /> مشاريع تُراجع فعليًا</span>
            <span class="flex items-center gap-2"><Award :size="17" class="text-primary" /> شهادات قابلة للتحقق</span>
          </div>
        </div>

        <div class="relative">
          <div class="card overflow-hidden">
            <div class="border-b border-line bg-surface px-4 py-2.5 flex items-center gap-1.5">
              <span class="h-2.5 w-2.5 rounded-full bg-red-400" />
              <span class="h-2.5 w-2.5 rounded-full bg-amber" />
              <span class="h-2.5 w-2.5 rounded-full bg-emerald" />
            </div>
            <div class="p-5 font-mono text-[13px] leading-7 text-left" dir="ltr">
              <p class="text-ink-faint">// week 06 — your first real project</p>
              <p><span class="text-primary">async function</span> <span class="text-ink">ship</span><span class="text-ink-soft">(project) {</span></p>
              <p class="ps-5"><span class="text-ink-soft">const</span> <span class="text-emerald">skills</span> <span class="text-ink-soft">=</span> <span class="text-amber">await</span> <span class="text-ink">practice.daily()</span><span class="text-ink-soft">;</span></p>
              <p class="ps-5"><span class="text-ink-soft">const</span> <span class="text-emerald">feedback</span> <span class="text-ink-soft">=</span> <span class="text-amber">await</span> <span class="text-ink">mentor.review(project)</span><span class="text-ink-soft">;</span></p>
              <p class="ps-5"><span class="text-amber">return</span> <span class="text-ink">career.levelUp(feedback)</span><span class="text-ink-soft">;</span></p>
              <p><span class="text-ink-soft">}</span></p>
              <p class="mt-3 text-ink-faint">// output: a developer who actually builds</p>
            </div>
          </div>
          <div class="absolute -bottom-5 -start-4 card hidden sm:flex items-center gap-3 px-4 py-3 shadow-pop">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-soft text-emerald"><CheckCircle2 :size="19" /></div>
            <div>
              <p class="text-xs text-ink-muted">مشروعك الأخير</p>
              <p class="text-sm font-bold text-ink">تمت المراجعة والاعتماد</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Why us -->
    <section class="container-site py-14 sm:py-20">
      <div class="max-w-2xl">
        <h2 class="section-title">لماذا تتعلم معنا؟</h2>
        <p class="section-sub">منهج مصمم ليجعلك تبني — لا أن تشاهد فقط.</p>
      </div>
      <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="f in features" :key="f.title" class="card p-6">
          <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-lg" :class="f.bg">
            <component :is="f.icon" :size="21" :class="f.color" />
          </div>
          <h3 class="font-bold text-ink">{{ f.title }}</h3>
          <p class="mt-1.5 text-sm leading-relaxed text-ink-muted">{{ f.desc }}</p>
        </div>
      </div>
    </section>

    <!-- Featured courses -->
    <section class="border-y border-line bg-white">
      <div class="container-site py-14 sm:py-20">
        <div class="flex items-end justify-between gap-4">
          <div>
            <h2 class="section-title">كورسات مختارة</h2>
            <p class="section-sub">مسارات مكتملة من الأساس إلى بناء المشاريع.</p>
          </div>
          <RouterLink to="/courses" class="btn-outline hidden sm:inline-flex">كل الكورسات <ArrowLeft :size="15" /></RouterLink>
        </div>

        <div v-if="loadingCourses" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="i in 3" :key="i" class="card overflow-hidden"><div class="skeleton aspect-[16/9] !rounded-none" /><div class="p-5 space-y-3"><div class="skeleton h-4 w-3/4" /><div class="skeleton h-3 w-full" /><div class="skeleton h-3 w-2/3" /></div></div>
        </div>
        <div v-else-if="courses.length" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <CourseCard v-for="c in courses" :key="c.id" :course="c" />
        </div>
        <EmptyState v-else title="لا توجد كورسات منشورة بعد" subtitle="تابعنا قريبًا — يتم إعداد المحتوى حاليًا." class="mt-8" :icon="BookOpen" />
        <RouterLink to="/courses" class="btn-outline mt-6 w-full sm:hidden">كل الكورسات</RouterLink>
      </div>
    </section>

    <!-- Upcoming events -->
    <section class="container-site py-14 sm:py-20">
      <div class="flex items-end justify-between gap-4">
        <div>
          <h2 class="section-title">فعاليات قادمة</h2>
          <p class="section-sub">جلسات مباشرة، ورش عمل، ولقاءات أسئلة وأجوبة.</p>
        </div>
        <RouterLink to="/events" class="btn-outline hidden sm:inline-flex">كل الفعاليات <ArrowLeft :size="15" /></RouterLink>
      </div>
      <div v-if="loadingEvents" class="mt-8 space-y-4">
        <div v-for="i in 2" :key="i" class="card skeleton h-28" />
      </div>
      <div v-else-if="events.length" class="mt-8 space-y-4">
        <EventCard v-for="e in events" :key="e.id" :event="e" />
      </div>
      <EmptyState v-else title="لا توجد فعاليات قادمة حاليًا" subtitle="سيتم الإعلان عن الفعاليات الجديدة هنا أولًا." class="mt-8" :icon="CalendarDays" />
    </section>

    <!-- How it works -->
    <section class="border-y border-line bg-white">
      <div class="container-site py-14 sm:py-20">
        <div class="max-w-2xl">
          <h2 class="section-title">كيف يعمل التعلم هنا؟</h2>
          <p class="section-sub">أربع خطوات واضحة — بدون تعقيد.</p>
        </div>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="(s, i) in steps" :key="s.title" class="relative">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-ink text-white font-bold">{{ i + 1 }}</div>
            <h3 class="mt-4 font-bold text-ink">{{ s.title }}</h3>
            <p class="mt-1.5 text-sm leading-relaxed text-ink-muted">{{ s.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Student journey -->
    <section class="container-site py-14 sm:py-20">
      <div class="card overflow-hidden lg:grid lg:grid-cols-2">
        <div class="p-8 sm:p-10">
          <h2 class="section-title">رحلتك كطالب</h2>
          <ul class="mt-6 space-y-4">
            <li v-for="j in journey" :key="j" class="flex items-start gap-3 text-sm text-ink-soft">
              <CheckCircle2 :size="18" class="mt-0.5 shrink-0 text-emerald" />
              <span class="leading-relaxed">{{ j }}</span>
            </li>
          </ul>
        </div>
        <div class="bg-ink p-8 sm:p-10 text-white flex flex-col justify-center">
          <p class="text-sm font-semibold text-stone-400">نتيجة التعلم</p>
          <p class="mt-3 text-2xl sm:text-3xl font-bold leading-snug">محفظة مشاريع حقيقية<br />+ شهادة موثّقة بالتحقق</p>
          <p class="mt-4 text-sm leading-relaxed text-stone-300">
            كل كورس ينتهي بمشروع يُراجع من مدرب حقيقي، وشهادة برقم تحقق فريد يمكن لأي جهة التأكد منها.
          </p>
          <RouterLink to="/register" class="btn-primary mt-6 w-fit !bg-white !text-ink hover:!bg-stone-100">ابدأ الآن</RouterLink>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section v-if="faqs.length" class="container-site pb-14 sm:pb-20">
      <div class="max-w-2xl">
        <h2 class="section-title">أسئلة شائعة</h2>
      </div>
      <div class="mt-6 grid gap-3 max-w-3xl">
        <details v-for="f in faqs.slice(0, 5)" :key="f.id" class="card group px-5 py-4 open:shadow-pop">
          <summary class="flex cursor-pointer items-center justify-between gap-4 text-sm font-semibold text-ink list-none">
            {{ f.question }}
            <ChevronDown :size="17" class="shrink-0 text-ink-faint transition-transform group-open:rotate-180" />
          </summary>
          <p class="mt-3 text-sm leading-relaxed text-ink-muted">{{ f.answer }}</p>
        </details>
      </div>
      <RouterLink to="/faq" class="link mt-4 inline-block text-sm">كل الأسئلة الشائعة</RouterLink>
    </section>

    <!-- Final CTA -->
    <section class="border-t border-line bg-ink">
      <div class="container-site py-14 sm:py-16 text-center">
        <h2 class="text-2xl sm:text-3xl font-bold text-white">جاهز تبدأ تبني؟</h2>
        <p class="mx-auto mt-3 max-w-xl text-sm sm:text-base text-stone-300">أنشئ حسابك مجانًا، تصفح الكورسات، وابدأ أول درس معاينة اليوم.</p>
        <div class="mt-7 flex justify-center gap-3">
          <RouterLink to="/register" class="btn-primary !px-6 !py-3">إنشاء حساب مجاني</RouterLink>
          <RouterLink to="/courses" class="btn !px-6 !py-3 bg-white/10 text-white hover:bg-white/20">تصفح الكورسات</RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import CourseCard from '../../components/CourseCard.vue';
import EventCard from '../../components/EventCard.vue';
import EmptyState from '../../components/EmptyState.vue';
import {
  ArrowLeft, PlayCircle, FolderKanban, Award, CheckCircle2, ChevronDown,
  Code2, Users, ShieldCheck, CalendarDays, BookOpen,
} from '@lucide/vue';

const courses = ref([]);
const events = ref([]);
const faqs = ref([]);
const loadingCourses = ref(true);
const loadingEvents = ref(true);

const features = [
  { title: 'تعلم بالمشاريع', desc: 'كل مفهوم يتحول فورًا إلى كود تعمل عليه بيدك، ثم مشروع يُراجع.', icon: Code2, bg: 'bg-primary-faint', color: 'text-primary' },
  { title: 'جلسات مباشرة', desc: 'فعاليات ولقاءات أسئلة وأجوبة مع المدربين طوال رحلتك.', icon: Users, bg: 'bg-emerald-soft', color: 'text-emerald' },
  { title: 'محتوى محمي', desc: 'فيديوهاتك بصلاحيات وصول آمنة ومتابعة تقدم دقيقة.', icon: ShieldCheck, bg: 'bg-amber-soft', color: 'text-amber' },
  { title: 'شهادات موثقة', desc: 'شهادة إتمام برقم تحقق فريد وصفحة تحقق عامة.', icon: Award, bg: 'bg-primary-faint', color: 'text-primary' },
];

const steps = [
  { title: 'أنشئ حسابك', desc: 'تسجيل مجاني في أقل من دقيقة — بدون بطاقة دفع.' },
  { title: 'اختر كورسك', desc: 'تصفح المسارات وشاهد دروس المعاينة المجانية.' },
  { title: 'فعّل اشتراكك', desc: 'تواصل معنا عبر WhatsApp لتفعيل الكورس على حسابك.' },
  { title: 'ابنِ وتقدم', desc: 'دروس، مشاريع، اختبارات، وفعاليات — حتى الشهادة.' },
];

const journey = [
  'حساب شخصي يتتبع تقدمك درسًا بدرس ويستأنف من حيث توقفت.',
  'دروس فيديو محمية بصلاحيات وصول وعلامة مائية ديناميكية.',
  'مشاريع تطبيقية تُراجع من مدربين بملاحظات مكتوبة.',
  'اختبارات قصيرة تقيس فهمك قبل الانتقال.',
  'فعاليات مباشرة مرتبطة بكورسك مع تسجيل حضور بتذكرة QR.',
  'شهادة إتمام برقم تحقق عام عند استيفاء الشروط.',
];

onMounted(async () => {
  try {
    const [c, e, f] = await Promise.all([
      api.get('/courses', { params: { per_page: 6 } }),
      api.get('/events', { params: { filter: 'upcoming', per_page: 3 } }),
      api.get('/faqs'),
    ]);
    courses.value = (c.data.data || []).filter((x) => x.is_featured).concat(c.data.data || []).slice(0, 3);
    events.value = (e.data.data || []).slice(0, 3);
    faqs.value = f.data.faqs || [];
  } finally {
    loadingCourses.value = false;
    loadingEvents.value = false;
  }
});
</script>
