<template>
  <div class="max-w-2xl space-y-6">
    <h1 class="text-xl sm:text-2xl font-bold text-ink">حسابي</h1>

    <!-- Profile -->
    <div class="card p-6">
      <h2 class="mb-4 text-base font-bold text-ink">البيانات الشخصية</h2>
      <form class="space-y-4" @submit.prevent="saveProfile">
        <FormField label="الاسم الكامل" :error="errors.name?.[0]">
          <input v-model="profile.name" class="input" required />
        </FormField>
        <FormField label="البريد الإلكتروني" hint="لا يمكن تغيير البريد حاليًا.">
          <input :value="auth.user?.email" class="input bg-surface" disabled dir="ltr" />
        </FormField>
        <FormField label="رقم الهاتف" :error="errors.phone?.[0]">
          <input v-model="profile.phone" class="input" dir="ltr" />
        </FormField>
        <div class="flex items-center justify-between">
          <span class="text-xs text-ink-faint">رقم الطالب: <b dir="ltr">{{ auth.user?.student_number }}</b></span>
          <button class="btn-primary !px-5" :disabled="busyProfile">
            <Loader2 v-if="busyProfile" :size="15" class="animate-spin" /> حفظ
          </button>
        </div>
      </form>
    </div>

    <!-- Password -->
    <div class="card p-6">
      <h2 class="mb-4 text-base font-bold text-ink">تغيير كلمة المرور</h2>
      <form class="space-y-4" @submit.prevent="savePassword">
        <FormField label="كلمة المرور الحالية" :error="errors.current_password?.[0]">
          <input v-model="pwd.current_password" type="password" class="input" dir="ltr" required />
        </FormField>
        <FormField label="كلمة المرور الجديدة" :error="errors.password?.[0]">
          <input v-model="pwd.password" type="password" class="input" dir="ltr" required />
        </FormField>
        <FormField label="تأكيد كلمة المرور">
          <input v-model="pwd.password_confirmation" type="password" class="input" dir="ltr" required />
        </FormField>
        <button class="btn-outline" :disabled="busyPwd"><Loader2 v-if="busyPwd" :size="15" class="animate-spin" /> تحديث</button>
      </form>
    </div>

    <!-- Devices -->
    <div class="card p-6">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-bold text-ink">أجهزتي</h2>
        <span class="text-xs text-ink-faint">الحد الأقصى: {{ devicesLimit }}</span>
      </div>
      <ul v-if="devices.length" class="space-y-3">
        <li v-for="d in devices" :key="d.id" class="flex items-center gap-3 rounded-lg border border-line p-3.5">
          <MonitorSmartphone :size="20" class="shrink-0 text-ink-faint" />
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-ink">{{ d.name || 'جهاز غير معروف' }}</p>
            <p class="text-xs text-ink-faint">آخر نشاط: {{ d.last_seen_at ? new Date(d.last_seen_at).toLocaleString('ar-EG') : '—' }}</p>
          </div>
          <span v-if="d.device_id === currentDeviceId" class="badge-green">هذا الجهاز</span>
          <StatusBadge v-else :status="d.status" />
          <button v-if="d.status === 'active' && d.device_id !== currentDeviceId" class="btn-ghost !p-1.5 text-danger" @click="revoke(d)">
            <Trash2 :size="15" />
          </button>
        </li>
      </ul>
      <p v-else class="text-sm text-ink-faint">لم يُسجل أي جهاز بعد — تُسجل الأجهزة تلقائيًا عند تشغيل فيديو.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { unwrapError } from '../../api/client';
import { auth } from '../../stores/auth';
import { getDeviceId } from '../../utils/device';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { MonitorSmartphone, Trash2, Loader2 } from '@lucide/vue';

const profile = ref({ name: auth.user?.name || '', phone: auth.user?.phone || '' });
const pwd = ref({ current_password: '', password: '', password_confirmation: '' });
const errors = ref({});
const busyProfile = ref(false);
const busyPwd = ref(false);
const devices = ref([]);
const devicesLimit = ref(2);
const currentDeviceId = getDeviceId();

async function saveProfile() {
  busyProfile.value = true;
  errors.value = {};
  try {
    const { data } = await api.put('/me/profile', profile.value);
    auth.user = data.user;
    toast('تم حفظ بياناتك.');
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err; else toast(err, 'error');
  } finally {
    busyProfile.value = false;
  }
}

async function savePassword() {
  busyPwd.value = true;
  errors.value = {};
  try {
    await api.put('/me/password', pwd.value);
    pwd.value = { current_password: '', password: '', password_confirmation: '' };
    toast('تم تحديث كلمة المرور.');
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err; else toast(err, 'error');
  } finally {
    busyPwd.value = false;
  }
}

async function revoke(d) {
  await api.delete(`/me/devices/${d.id}`);
  d.status = 'revoked';
  toast('تم إلغاء الجهاز.');
}

onMounted(async () => {
  try {
    const { data } = await api.get('/me/devices');
    devices.value = data.devices;
    devicesLimit.value = data.limit;
  } catch { /* ignore */ }
});
</script>
