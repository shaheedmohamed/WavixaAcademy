<template>
  <div class="card p-7 sm:p-8">
    <h1 class="text-xl font-bold text-ink">استعادة كلمة المرور</h1>
    <p class="mt-1 text-sm text-ink-muted">أدخل بريدك وسنرسل لك رابط الاستعادة.</p>

    <div v-if="sent" class="mt-6 rounded-lg border border-emerald-soft bg-emerald-soft/40 p-4 text-sm text-emerald-800">
      إن كان البريد مسجلًا لدينا، ستصلك رسالة استعادة خلال دقائق. تحقق من صندوق الوارد والرسائل غير المرغوبة.
    </div>

    <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
      <FormField :label="$t('common.email')" :error="errors.email?.[0]" required>
        <input v-model="email" type="email" class="input" required dir="ltr" />
      </FormField>
      <button class="btn-primary w-full !py-3" :disabled="busy">
        <Loader2 v-if="busy" :size="17" class="animate-spin" />
        إرسال رابط الاستعادة
      </button>
    </form>

    <p class="mt-6 text-center text-sm">
      <RouterLink to="/login" class="link">العودة لتسجيل الدخول</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import { Loader2 } from '@lucide/vue';

const email = ref('');
const errors = ref({});
const busy = ref(false);
const sent = ref(false);

async function submit() {
  busy.value = true;
  errors.value = {};
  try {
    await api.post('/auth/forgot-password', { email: email.value });
    sent.value = true;
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}
</script>
