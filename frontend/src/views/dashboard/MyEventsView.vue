<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-ink">فعالياتي والمهام التعليمية</h1>
      <p class="mt-1 text-sm text-ink-muted">متابعة الفعاليات المسجل بها، والاختبارات والمهام العملية وملاحظات وتعديلات المدربين.</p>
    </div>

    <!-- Active Tasks, Quizzes & Practical Submissions -->
    <div v-if="activities.length" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm sm:text-base font-bold text-ink flex items-center gap-2">
          <ClipboardList :size="18" class="text-primary" /> الاختبارات والمهام العملية بكورساتك
        </h2>
        <span class="badge-gray">{{ activities.length }} مهام</span>
      </div>

      <div class="grid gap-3">
        <div v-for="a in activities" :key="a.type + '-' + a.id"
             class="card p-4 sm:p-5 transition-all duration-150 hover:border-line/90 bg-white"
             :class="{
               'border-amber-300 bg-amber-50/20': a.submission_status === 'changes_requested',
               'border-emerald-300 bg-emerald-50/15': a.submission_status === 'approved',
             }">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3.5 min-w-0 flex-1">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                    :class="a.submitted
                      ? (a.submission_status === 'approved' ? 'bg-emerald-100 text-emerald-700' : (a.submission_status === 'changes_requested' ? 'bg-amber-100 text-amber-800' : 'bg-primary-faint text-primary'))
                      : 'bg-stone-100 text-ink-faint'">
                <BadgeCheck v-if="a.submission_status === 'approved'" :size="20" />
                <AlertCircle v-else-if="a.submission_status === 'changes_requested'" :size="20" />
                <CheckCircle2 v-else-if="a.submitted" :size="20" />
                <FileQuestion v-else :size="20" />
              </span>

              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="badge" :class="a.type === 'quiz' ? 'badge-blue !bg-purple-100 !text-purple-800' : 'badge-amber'">
                    {{ a.type === 'quiz' ? 'مهمة / اختبار' : 'مشروع تطبيقي' }}
                  </span>
                  <span v-if="a.submission_status === 'approved'" class="badge-green">معتمد ومقبول ✅</span>
                  <span v-else-if="a.submission_status === 'changes_requested'" class="badge-amber">مطلوب تعديلات من المدرب ⚠️</span>
                  <span v-else-if="a.submitted" class="badge-blue">تم التسليم قيد المراجعة ⏳</span>
                  <span v-else class="badge-gray">بانتظار تسليمك</span>
                </div>

                <h3 class="text-base font-bold text-ink mt-1 truncate">{{ a.title }}</h3>
                <p class="text-xs text-ink-muted mt-0.5">
                  {{ a.course?.title }}<span v-if="a.module"> · {{ a.module.title }}</span>
                </p>

                <!-- Feedback from Instructor -->
                <div v-if="a.feedback" class="mt-3 rounded-xl p-3 text-xs leading-relaxed border"
                     :class="a.submission_status === 'changes_requested' ? 'bg-amber-100/70 border-amber-300 text-amber-950 font-medium' : 'bg-surface border-line text-ink-soft'">
                  <p class="font-bold mb-1 flex items-center gap-1.5" :class="a.submission_status === 'changes_requested' ? 'text-amber-900' : 'text-primary'">
                    <MessageSquare :size="13" /> رد وتوجيهات المدرب:
                  </p>
                  <p class="whitespace-pre-line">{{ a.feedback }}</p>
                </div>
              </div>
            </div>

            <!-- Action Button -->
            <div class="shrink-0 self-center">
              <RouterLink v-if="a.type === 'quiz'"
                          :to="`/dashboard/quizzes/${a.id}`"
                          class="btn-primary !py-2 !px-4 !text-xs font-bold flex items-center gap-1.5"
                          :class="a.submission_status === 'changes_requested' ? '!bg-amber-600 hover:!bg-amber-700' : ''">
                <span>{{ a.submitted ? (a.submission_status === 'changes_requested' ? 'تعديل الحل وإعادة التسليم' : 'عرض المهمة') : 'بدء المهمة' }}</span>
                <ChevronLeft :size="14" />
              </RouterLink>

              <RouterLink v-else
                          :to="`/dashboard/courses/${a.course?.slug}`"
                          class="btn-outline !py-2 !px-4 !text-xs font-semibold flex items-center gap-1.5">
                <span>{{ a.submitted ? 'عرض التسليم' : 'فتح الكورس' }}</span>
                <ChevronLeft :size="14" />
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Events Section -->
    <div class="space-y-4 pt-4 border-t border-line/60">
      <h2 class="text-sm sm:text-base font-bold text-ink flex items-center gap-2">
        <CalendarDays :size="18" class="text-primary" /> الفعاليات المسجل بها
      </h2>

      <div v-if="loading" class="space-y-3"><div v-for="i in 2" :key="i" class="card skeleton h-28" /></div>

      <div v-else-if="registrations.length" class="space-y-3">
        <div v-for="r in registrations" :key="r.id" class="card p-5">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <RouterLink :to="`/events/${r.event.slug}`" class="font-bold text-ink hover:text-primary transition-colors">{{ r.event.title }}</RouterLink>
              <p class="mt-1 text-xs text-ink-muted">
                {{ new Date(r.event.start_at).toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' }) }} · {{ r.event.location || 'أونلاين' }}
              </p>
              <div class="mt-2 flex items-center gap-2">
                <StatusBadge :status="r.status" />
                <span class="text-xs text-ink-faint font-mono" dir="ltr">{{ r.ticket_code }}</span>
              </div>
            </div>
            <button class="btn-outline !text-xs font-semibold flex items-center gap-1.5" @click="showTicket(r)">
              <QrCode :size="15" /> تذكرة الحضور
            </button>
          </div>
        </div>
      </div>

      <div v-else class="card p-8 text-center text-ink-muted">
        <CalendarDays :size="32" class="mx-auto mb-2 text-ink-faint" />
        <p class="text-sm font-semibold">لم تسجل في أي فعالية بعد.</p>
        <RouterLink to="/events" class="btn-primary mt-3 !text-xs inline-flex">تصفح الفعاليات المتاحة</RouterLink>
      </div>
    </div>

    <!-- Ticket Modal -->
    <Modal :open="!!ticket" title="تذكرة الدخول" @close="ticket = null">
      <div v-if="ticket" class="text-center">
        <div class="mx-auto w-fit rounded-xl2 border border-line bg-white p-4" v-html="ticket.qr_svg" />
        <p class="mt-4 font-bold text-ink">{{ ticket.event.title }}</p>
        <p class="mt-1 text-sm text-ink-muted font-mono" dir="ltr">{{ ticket.ticket_code }}</p>
        <StatusBadge class="mt-3" :status="ticket.checked_in_at ? 'attended' : 'registered'" />
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import Modal from '../../components/Modal.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import {
  CalendarDays, QrCode, ClipboardList, FileQuestion, CheckCircle2,
  BadgeCheck, AlertCircle, MessageSquare, ChevronLeft
} from '@lucide/vue';

const registrations = ref([]);
const activities = ref([]);
const loading = ref(true);
const ticket = ref(null);

async function showTicket(reg) {
  const { data } = await api.get(`/me/events/${reg.id}/ticket`);
  ticket.value = data;
}

onMounted(async () => {
  try {
    const [{ data }, { data: act }] = await Promise.all([
      api.get('/me/events'),
      api.get('/me/projects/pending'),
    ]);
    registrations.value = data.registrations || [];
    activities.value = act.activities || [];
  } finally {
    loading.value = false;
  }
});
</script>
