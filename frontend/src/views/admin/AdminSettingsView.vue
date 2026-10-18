<template>
  <div class="max-w-3xl space-y-6">
    <div>
      <h1 class="text-xl font-bold text-ink">الإعدادات</h1>
      <p class="mt-0.5 text-sm text-ink-muted">إعدادات المنصة العامة — تُقرأ من قاعدة البيانات.</p>
    </div>

    <div v-if="loading" class="card skeleton h-64" />

    <template v-else>
      <!-- General -->
      <div class="card p-6 space-y-4">
        <h2 class="text-sm font-bold text-ink">عام</h2>
        <FormField label="اسم المنصة"><input v-model="general.platform_name" class="input" /></FormField>
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField label="رقم WhatsApp (دولي، بدون +)" hint="يُستخدم في زر التسجيل بالكورسات">
            <input v-model="general.whatsapp_number" class="input" dir="ltr" placeholder="201011679726" />
          </FormField>
          <FormField label="بريد الدعم"><input v-model="general.support_email" class="input" dir="ltr" /></FormField>
        </div>
        <FormField label="قالب رسالة WhatsApp" hint="{course_name} تُستبدل باسم الكورس">
          <input v-model="general.whatsapp_message_template" class="input" />
        </FormField>
        <FormField label="نبذة الفوتر"><textarea v-model="general.footer_about" rows="2" class="input" /></FormField>
        <button class="btn-primary" :disabled="busy" @click="save('general', general)"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ</button>
      </div>

      <!-- Security -->
      <div class="card p-6 space-y-4">
        <h2 class="text-sm font-bold text-ink">الأمان والفيديو (CourseShield)</h2>
        <div class="grid gap-4 sm:grid-cols-3">
          <FormField label="حد الأجهزة"><input v-model.number="security.max_devices" type="number" min="1" max="10" class="input" dir="ltr" /></FormField>
          <FormField label="جلسات متزامنة"><input v-model.number="security.max_concurrent_sessions" type="number" min="1" max="5" class="input" dir="ltr" /></FormField>
          <FormField label="نسبة الإكمال للدرس">
            <select v-model.number="security.completion_threshold" class="input" dir="ltr">
              <option :value="0.8">80%</option><option :value="0.9">90%</option><option :value="0.95">95%</option><option :value="1">100%</option>
            </select>
          </FormField>
        </div>
        <FormField label="سياسة التزامن">
          <select v-model="security.concurrent_policy" class="input">
            <option value="terminate_oldest">إنهاء الجلسة الأقدم</option>
            <option value="block_new">منع الجلسة الجديدة</option>
            <option value="alert">تنبيه فقط</option>
          </select>
        </FormField>
        <label class="flex items-center gap-2 text-sm text-ink-soft">
          <input v-model="security.watermark_enabled" type="checkbox" class="rounded border-line text-primary" />
          تفعيل العلامة المائية الديناميكية
        </label>
        <p class="rounded-lg bg-surface p-3 text-xs text-ink-muted">إعدادات الجلسات الحساسة (مدة الجلسة، فترة الـ heartbeat) تُدار من ملف البيئة لحماية أعلى.</p>
        <button class="btn-primary" :disabled="busy" @click="save('security', security)"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import { Loader2 } from '@lucide/vue';

const general = ref({});
const security = ref({});
const loading = ref(true);
const busy = ref(false);

async function save(group, values) {
  busy.value = true;
  try {
    await api.put('/admin/settings', { group, settings: values });
    toast('تم حفظ الإعدادات.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/settings');
    general.value = data.settings.general || {};
    security.value = data.settings.security || {};
  } finally {
    loading.value = false;
  }
});
</script>
