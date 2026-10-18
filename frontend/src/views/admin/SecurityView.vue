<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-xl font-bold text-ink">مركز الأمان</h1>
      <p class="mt-0.5 text-sm text-ink-muted">جلسات الفيديو، الأجهزة، والتنبيهات الأمنية.</p>
    </div>

    <div class="flex gap-1 border-b border-line overflow-x-auto">
      <button v-for="t in tabs" :key="t.id" class="whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 -mb-px"
        :class="tab === t.id ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink'"
        @click="tab = t.id; loadTab()">
        {{ t.label }}
      </button>
    </div>

    <!-- Sessions -->
    <div v-if="tab === 'sessions'" class="card overflow-x-auto">
      <table class="table-base min-w-[760px]">
        <thead><tr><th>الطالب</th><th>الدرس</th><th>الجهاز</th><th>المخاطرة</th><th>آخر نشاط</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="s in sessions.data" :key="s.id">
            <td>
              <RouterLink :to="`/admin/students/${s.user?.id}`" class="font-semibold text-ink hover:text-primary">{{ s.user?.name }}</RouterLink>
              <p class="text-xs text-ink-faint" dir="ltr">{{ s.user?.student_number }}</p>
            </td>
            <td class="text-sm">{{ s.lesson?.title || '—' }}</td>
            <td class="text-xs text-ink-muted">{{ s.device?.name || '—' }}</td>
            <td><span class="badge" :class="s.risk_score >= 50 ? 'badge-red' : s.risk_score >= 25 ? 'badge-amber' : 'badge-gray'">{{ s.risk_score }}</span></td>
            <td class="text-xs text-ink-faint whitespace-nowrap">{{ s.last_seen_at ? new Date(s.last_seen_at).toLocaleString('ar-EG') : '—' }}</td>
            <td><StatusBadge :status="s.status" /></td>
            <td><button v-if="s.status === 'active'" class="btn-ghost !px-2 !py-1 !text-xs text-danger" @click="terminate(s)">إنهاء</button></td>
          </tr>
          <tr v-if="!sessions.data?.length"><td colspan="7" class="py-10 text-center text-ink-faint">لا جلسات.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Devices -->
    <div v-if="tab === 'devices'" class="card overflow-x-auto">
      <table class="table-base min-w-[640px]">
        <thead><tr><th>الطالب</th><th>الجهاز</th><th>المتصفح</th><th>آخر نشاط</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="d in devices.data" :key="d.id">
            <td class="font-semibold text-ink">{{ d.user?.name }} <span class="text-xs text-ink-faint" dir="ltr">{{ d.user?.student_number }}</span></td>
            <td class="text-sm">{{ d.name || '—' }}</td>
            <td class="text-xs text-ink-muted">{{ d.browser || '—' }} / {{ d.os || '—' }}</td>
            <td class="text-xs text-ink-faint">{{ d.last_seen_at ? new Date(d.last_seen_at).toLocaleString('ar-EG') : '—' }}</td>
            <td><StatusBadge :status="d.status" /></td>
            <td><button v-if="d.status === 'active'" class="btn-ghost !px-2 !py-1 !text-xs text-danger" @click="revokeDevice(d)">إلغاء</button></td>
          </tr>
          <tr v-if="!devices.data?.length"><td colspan="6" class="py-10 text-center text-ink-faint">لا أجهزة.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Security events -->
    <div v-if="tab === 'events'" class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>الخطورة</th><th>النوع</th><th>الوصف</th><th>الطالب</th><th>الوقت</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="e in events.data" :key="e.id">
            <td><StatusBadge :status="e.severity" /></td>
            <td class="text-xs font-mono">{{ e.type }}</td>
            <td class="max-w-[260px] text-sm text-ink">{{ e.description }}</td>
            <td class="text-sm">{{ e.user?.name || '—' }}</td>
            <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(e.created_at).toLocaleString('ar-EG') }}</td>
            <td><StatusBadge :status="e.status" /></td>
            <td><button v-if="e.status === 'open'" class="btn-outline !px-2.5 !py-1 !text-xs" @click="resolve(e)">معالجة</button></td>
          </tr>
          <tr v-if="!events.data?.length"><td colspan="7" class="py-10 text-center text-ink-faint">لا تنبيهات — كل شيء هادئ.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import { toast } from '../../stores/toast';
import StatusBadge from '../../components/StatusBadge.vue';

const tab = ref('events');
const tabs = [
  { id: 'events', label: 'التنبيهات' },
  { id: 'sessions', label: 'جلسات الفيديو' },
  { id: 'devices', label: 'الأجهزة' },
];
const sessions = ref({});
const devices = ref({});
const events = ref({});

async function loadTab() {
  if (tab.value === 'sessions') sessions.value = (await api.get('/admin/security/sessions', { params: { active_only: 1 } })).data;
  else if (tab.value === 'devices') devices.value = (await api.get('/admin/security/devices')).data;
  else events.value = (await api.get('/admin/security/events')).data;
}

async function terminate(s) {
  await api.post(`/admin/security/sessions/${s.id}/terminate`);
  s.status = 'terminated';
  toast('تم إنهاء الجلسة.');
}

async function revokeDevice(d) {
  await api.delete(`/admin/security/devices/${d.id}`);
  d.status = 'revoked';
  toast('تم إلغاء الجهاز.');
}

async function resolve(e) {
  await api.post(`/admin/security/events/${e.id}/resolve`);
  e.status = 'resolved';
  toast('تمت المعالجة.');
}

onMounted(loadTab);
</script>
