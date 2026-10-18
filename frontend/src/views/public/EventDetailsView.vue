<template>
  <div v-if="loading" class="container-site py-14"><div class="skeleton h-8 w-1/2" /></div>

  <div v-else-if="event" class="pb-16">
    <section class="border-b border-line bg-white">
      <div class="container-site py-10 sm:py-14 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
          <div class="flex items-center gap-2 mb-4">
            <span class="badge-blue">{{ typeLabel }}</span>
            <StatusBadge :status="event.status" />
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-ink">{{ event.title }}</h1>
          <div class="mt-5 grid gap-3 text-sm text-ink-soft sm:grid-cols-2">
            <span class="flex items-center gap-2"><CalendarDays :size="17" class="text-primary" /> {{ dateLabel }}</span>
            <span class="flex items-center gap-2"><Clock :size="17" class="text-primary" /> {{ timeLabel }} ({{ event.timezone }})</span>
            <span v-if="event.location" class="flex items-center gap-2"><MapPin :size="17" class="text-primary" /> {{ event.location }}</span>
            <span v-if="event.online_url" class="flex items-center gap-2"><Video :size="17" class="text-primary" /> أونلاين</span>
            <span v-if="event.speaker" class="flex items-center gap-2"><Mic :size="17" class="text-primary" /> {{ event.speaker }}</span>
            <span v-if="event.capacity" class="flex items-center gap-2"><Users :size="17" class="text-primary" /> {{ remainingLabel }}</span>
          </div>
          <div class="mt-6 whitespace-pre-line text-ink-soft leading-relaxed">{{ event.description }}</div>
        </div>

        <div class="card p-6 h-fit">
          <template v-if="isPast">
            <p class="text-center text-sm font-semibold text-ink-muted">انتهت هذه الفعالية</p>
            <a v-if="event.recording_url" :href="event.recording_url" target="_blank" rel="noopener" class="btn-outline mt-4 w-full">مشاهدة التسجيل</a>
          </template>
          <template v-else-if="registration">
            <div class="flex items-center gap-2 text-emerald">
              <CheckCircle2 :size="20" />
              <p class="font-bold">أنت مسجّل في هذه الفعالية</p>
            </div>
            <p class="mt-2 text-xs text-ink-muted">تذكرتك: {{ registration.ticket_code }}</p>
            <RouterLink to="/dashboard/events" class="btn-primary mt-4 w-full">عرض تذكرتي (QR)</RouterLink>
            <button class="btn-ghost mt-2 w-full text-danger" :disabled="busy" @click="cancel">إلغاء التسجيل</button>
          </template>
          <template v-else>
            <p class="text-2xl font-bold text-ink">{{ priceLabel }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ countdownLabel }}</p>
            <button v-if="auth.user" class="btn-primary mt-4 w-full" :disabled="busy || soldOut" @click="register">
              <Ticket :size="17" /> {{ soldOut ? 'اكتمل العدد' : 'سجّل الآن' }}
            </button>
            <RouterLink v-else :to="{ name: 'login', query: { redirect: route.fullPath } }" class="btn-primary mt-4 w-full">
              سجّل الدخول للتسجيل
            </RouterLink>
            <p v-if="visibilityHint" class="mt-3 text-xs text-amber-800 bg-amber-soft rounded-lg p-2.5">{{ visibilityHint }}</p>
          </template>
        </div>
      </div>
    </section>
  </div>

  <NotFoundView v-else />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { auth } from '../../stores/auth';
import { toast } from '../../stores/toast';
import NotFoundView from './NotFoundView.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { CalendarDays, Clock, MapPin, Video, Mic, Users, Ticket, CheckCircle2 } from '@lucide/vue';

const route = useRoute();
const event = ref(null);
const registration = ref(null);
const remaining = ref(null);
const loading = ref(true);
const busy = ref(false);

const isPast = computed(() => event.value && new Date(event.value.start_at) < new Date());
const soldOut = computed(() => remaining.value === 0);

const typeLabel = computed(() => ({
  workshop: 'ورشة عمل', live_session: 'جلسة مباشرة', webinar: 'ويبينار',
  qna: 'أسئلة وأجوبة', meetup: 'لقاء', graduation: 'تخرج', other: 'فعالية',
}[event.value?.type] || 'فعالية'));

const priceLabel = computed(() => Number(event.value?.price || 0) > 0
  ? `${event.value.price} EGP` : 'مجاني');

const dateLabel = computed(() => new Date(event.value.start_at).toLocaleDateString('ar-EG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
const timeLabel = computed(() => new Date(event.value.start_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }));
const countdownLabel = computed(() => {
  const diff = new Date(event.value.start_at) - Date.now();
  if (diff <= 0) return '';
  const days = Math.floor(diff / 86400000);
  const hours = Math.floor((diff % 86400000) / 3600000);
  return days > 0 ? `تبدأ بعد ${days} يوم و${hours} ساعة` : `تبدأ بعد ${hours} ساعة`;
});
const remainingLabel = computed(() => remaining.value === null ? `سعة ${event.value.capacity}` : `متبقٍ ${remaining.value} مقعد`);
const visibilityHint = computed(() => ({
  students_only: 'هذه الفعالية مخصصة للطلاب المسجلين فقط.',
  course_students: 'هذه الفعالية مخصصة لطلاب الكورس المرتبط.',
  invite_only: 'هذه الفعالية بدعوات فقط.',
}[event.value?.visibility] || null));

async function register() {
  busy.value = true;
  try {
    const { data } = await api.post(`/events/${event.value.id}/register`);
    registration.value = data.registration;
    toast('تم تسجيلك في الفعالية. تذكرتك في لوحة التحكم.');
  } catch (e) {
    toast(typeof unwrapError(e) === 'string' ? unwrapError(e) : 'تعذر التسجيل.', 'error');
  } finally {
    busy.value = false;
  }
}

async function cancel() {
  busy.value = true;
  try {
    await api.delete(`/events/${event.value.id}/register`);
    registration.value = null;
    toast('تم إلغاء تسجيلك.', 'info');
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/events/${route.params.slug}`);
    event.value = data.event;
    registration.value = data.registration;
    remaining.value = data.remaining_capacity;
    document.title = `${data.event.title} — فعاليات`;
  } catch {
    event.value = null;
  } finally {
    loading.value = false;
  }
});
</script>
