<template>
  <div class="max-w-xl space-y-6">
    <div>
      <h1 class="text-xl font-bold text-ink">تسجيل الحضور</h1>
      <p class="mt-1 text-sm text-ink-muted">امسح رمز QR على تذكرة الطالب أو أدخل رمز التذكرة.</p>
    </div>

    <div class="card p-6">
      <FormField label="رمز التذكرة / QR token">
        <input v-model="token" class="input" dir="ltr" placeholder="ألصق محتوى الـ QR أو كود التذكرة" @keyup.enter="checkin" autofocus />
      </FormField>
      <button class="btn-primary mt-4 w-full" :disabled="busy || !token" @click="checkin">
        <Loader2 v-if="busy" :size="15" class="animate-spin" /> تحقق وسجّل الحضور
      </button>
    </div>

    <div v-if="result" class="card p-6" :class="result.ok ? 'border-emerald' : 'border-danger'">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-full" :class="result.ok ? 'bg-emerald-soft text-emerald' : 'bg-danger-soft text-danger'">
          <BadgeCheck v-if="result.ok" :size="22" />
          <XCircle v-else :size="22" />
        </div>
        <div>
          <p class="font-bold" :class="result.ok ? 'text-emerald-800' : 'text-red-800'">{{ result.message }}</p>
          <p v-if="result.registration?.user" class="text-sm text-ink-muted">
            {{ result.registration.user.name }} · {{ result.registration.event?.title }}
          </p>
        </div>
      </div>
      <p v-if="result.ok" class="mt-3 rounded-lg bg-emerald-soft px-4 py-2 text-center text-lg font-bold text-emerald-800">CHECKED IN</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../../api/client';
import FormField from '../../components/FormField.vue';
import { BadgeCheck, XCircle, Loader2 } from '@lucide/vue';

const token = ref('');
const busy = ref(false);
const result = ref(null);

async function checkin() {
  busy.value = true;
  result.value = null;
  try {
    // QR payload may be JSON {t: token} — extract.
    let qr = token.value.trim();
    try { qr = JSON.parse(qr).t || qr; } catch { /* plain token */ }
    const { data } = await api.post('/admin/events/checkin', { qr_token: qr });
    result.value = data;
  } catch (e) {
    result.value = { ok: false, message: e?.response?.data?.message || 'تذكرة غير صالحة.' };
  } finally {
    busy.value = false;
    token.value = '';
  }
}
</script>
