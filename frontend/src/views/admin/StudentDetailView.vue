<template>
  <div v-if="loading"><div class="skeleton h-32" /></div>

  <div v-else-if="student" class="space-y-6">
    <!-- Header -->
    <div class="card flex flex-wrap items-center gap-4 p-5">
      <div class="flex h-14 w-14 items-center justify-center rounded-full bg-ink text-white text-lg font-bold">{{ student.name?.charAt(0) }}</div>
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-3">
          <h1 class="text-lg font-bold text-ink">{{ student.name }}</h1>
          <StatusBadge :status="student.status" />
        </div>
        <p class="text-sm text-ink-muted">{{ student.email }} · <span dir="ltr">{{ student.student_number }}</span><template v-if="student.phone"> · <span dir="ltr">{{ student.phone }}</span></template></p>
      </div>
      <div class="flex gap-2">
        <button class="btn-outline !text-xs" @click="terminateSessions">إنهاء الجلسات</button>
        <button class="!text-xs" :class="student.status === 'active' ? 'btn-danger' : 'btn-emerald'" @click="toggleStatus">
          {{ student.status === 'active' ? 'إيقاف الحساب' : 'تفعيل الحساب' }}
        </button>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <!-- Enrollments -->
      <section class="card p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-bold text-ink">الاشتراكات</h2>
          <button class="btn-primary !px-3 !py-1.5 !text-xs" @click="openActivate">+ تفعيل كورس</button>
        </div>
        <ul v-if="enrollments.length" class="space-y-3">
          <li v-for="e in enrollments" :key="e.id" class="rounded-lg border border-line p-3.5">
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-semibold text-ink">{{ e.course?.title }}</p>
              <StatusBadge :status="e.status" />
            </div>
            <p class="mt-1 text-xs text-ink-faint">
              فُعّل: {{ e.activated_at ? new Date(e.activated_at).toLocaleDateString('ar-EG') : '—' }}
              <template v-if="e.expires_at"> · ينتهي: {{ new Date(e.expires_at).toLocaleDateString('ar-EG') }}</template>
              <template v-if="e.cohort"> · {{ e.cohort.name }}</template>
            </p>
            <div class="mt-2 flex items-center gap-2">
              <div class="h-1.5 flex-1 rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-emerald" :style="{ width: (progress[e.course_id]?.percent || 0) + '%' }" />
              </div>
              <span class="text-xs text-ink-muted">{{ progress[e.course_id]?.percent || 0 }}%</span>
              <select :value="e.status" class="input !w-auto !py-1 !text-xs" @change="(ev) => setStatus(e, ev.target.value)">
                <option value="pending">قيد الانتظار</option>
                <option value="active">نشط</option>
                <option value="suspended">موقوف</option>
                <option value="completed">مكتمل</option>
                <option value="cancelled">ملغي</option>
              </select>
            </div>
          </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-ink-faint">لا توجد اشتراكات.</p>
      </section>

      <!-- Devices -->
      <section class="card p-5">
        <h2 class="mb-4 text-sm font-bold text-ink">الأجهزة ({{ devices.length }})</h2>
        <ul v-if="devices.length" class="space-y-2.5">
          <li v-for="d in devices" :key="d.id" class="flex items-center gap-3 rounded-lg border border-line p-3">
            <MonitorSmartphone :size="18" class="text-ink-faint" />
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-ink">{{ d.name || 'جهاز' }}</p>
              <p class="text-xs text-ink-faint">آخر نشاط {{ d.last_seen_at ? new Date(d.last_seen_at).toLocaleString('ar-EG') : '—' }}</p>
            </div>
            <StatusBadge :status="d.status" />
            <button v-if="d.status === 'active'" class="btn-ghost !p-1.5 text-danger" @click="revokeDevice(d)"><Trash2 :size="14" /></button>
          </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-ink-faint">لا أجهزة مسجلة.</p>

        <h2 class="mb-3 mt-6 text-sm font-bold text-ink">جلسات نشطة ({{ activeSessions.length }})</h2>
        <ul v-if="activeSessions.length" class="space-y-2">
          <li v-for="s in activeSessions" :key="s.id" class="flex items-center justify-between rounded-lg border border-line p-3 text-xs">
            <span>درس #{{ s.lesson_id }} · مخاطرة {{ s.risk_score }}</span>
            <button class="btn-ghost !px-2 !py-1 text-danger !text-xs" @click="terminateSession(s)">إنهاء</button>
          </li>
        </ul>
        <p v-else class="py-3 text-center text-xs text-ink-faint">لا جلسات نشطة.</p>
      </section>

      <!-- Submissions -->
      <section class="card p-5">
        <h2 class="mb-4 text-sm font-bold text-ink">تسليمات المشاريع</h2>
        <ul v-if="submissions.length" class="space-y-2.5">
          <li v-for="s in submissions" :key="s.id" class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-ink">{{ s.project?.title }}</p>
              <p class="text-xs text-ink-faint">{{ s.submitted_at ? new Date(s.submitted_at).toLocaleDateString('ar-EG') : '' }}</p>
            </div>
            <StatusBadge :status="s.status" />
          </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-ink-faint">لا تسليمات.</p>
      </section>

      <!-- Quiz attempts -->
      <section class="card p-5">
        <h2 class="mb-4 text-sm font-bold text-ink">نتائج الاختبارات</h2>
        <ul v-if="quizAttempts.length" class="space-y-2.5">
          <li v-for="a in quizAttempts" :key="a.id" class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
            <p class="truncate text-sm font-medium text-ink">{{ a.quiz?.title }}</p>
            <span class="badge" :class="a.passed ? 'badge-green' : 'badge-red'">{{ a.score }}%</span>
          </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-ink-faint">لا محاولات.</p>
      </section>
    </div>

    <!-- Activate modal -->
    <Modal :open="activateOpen" title="تفعيل كورس للطالب" @close="activateOpen = false">
      <form class="space-y-4" @submit.prevent="activate">
        <FormField label="الكورس" required :error="actErrors.course_id?.[0]">
          <select v-model="actForm.course_id" class="input" required>
            <option :value="null" disabled>اختر كورسًا…</option>
            <option v-for="c in allCourses" :key="c.id" :value="c.id">{{ c.title }}</option>
          </select>
        </FormField>
        <FormField label="تاريخ الانتهاء (اختياري)" :error="actErrors.expires_at?.[0]">
          <input v-model="actForm.expires_at" type="date" class="input" dir="ltr" />
        </FormField>
        <FormField label="ملاحظات">
          <textarea v-model="actForm.notes" rows="2" class="input" placeholder="مثال: تم الدفع عبر Vodafone Cash…" />
        </FormField>
        <button class="btn-primary w-full" :disabled="busy">
          <Loader2 v-if="busy" :size="15" class="animate-spin" /> تأكيد التفعيل
        </button>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { MonitorSmartphone, Trash2, Loader2 } from '@lucide/vue';

const route = useRoute();
const student = ref(null);
const enrollments = ref([]);
const progress = ref({});
const devices = ref([]);
const activeSessions = ref([]);
const submissions = ref([]);
const quizAttempts = ref([]);
const loading = ref(true);

const activateOpen = ref(false);
const allCourses = ref([]);
const actForm = ref({ course_id: null, expires_at: '', notes: '' });
const actErrors = ref({});
const busy = ref(false);

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get(`/admin/students/${route.params.id}`);
    student.value = data.student;
    enrollments.value = data.enrollments;
    progress.value = data.progress;
    devices.value = data.devices;
    activeSessions.value = data.active_sessions;
    submissions.value = data.submissions;
    quizAttempts.value = data.quiz_attempts;
  } finally {
    loading.value = false;
  }
}

async function openActivate() {
  activateOpen.value = true;
  if (!allCourses.value.length) {
    const { data } = await api.get('/admin/courses', { params: { per_page: 100 } });
    allCourses.value = data.data || [];
  }
}

async function activate() {
  busy.value = true;
  actErrors.value = {};
  try {
    await api.post(`/admin/students/${student.value.id}/enrollments`, actForm.value);
    toast('تم تفعيل الكورس للطالب.');
    activateOpen.value = false;
    load();
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') actErrors.value = err; else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

async function setStatus(enrollment, status) {
  try {
    await api.patch(`/admin/enrollments/${enrollment.id}`, { status });
    enrollment.status = status;
    toast('تم تحديث حالة الاشتراك.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  }
}

async function toggleStatus() {
  const next = student.value.status === 'active' ? 'suspended' : 'active';
  await api.patch(`/admin/students/${student.value.id}/status`, { status: next });
  student.value.status = next;
  toast(next === 'active' ? 'تم تفعيل الحساب.' : 'تم إيقاف الحساب.');
}

async function terminateSessions() {
  const { data } = await api.post(`/admin/students/${student.value.id}/sessions/terminate`);
  toast(`تم إنهاء ${data.terminated} جلسة.`);
  load();
}

async function terminateSession(s) {
  await api.post(`/admin/security/sessions/${s.id}/terminate`);
  load();
}

async function revokeDevice(d) {
  await api.delete(`/admin/students/${student.value.id}/devices/${d.id}`);
  d.status = 'revoked';
  toast('تم إلغاء الجهاز.');
}

onMounted(load);
</script>
