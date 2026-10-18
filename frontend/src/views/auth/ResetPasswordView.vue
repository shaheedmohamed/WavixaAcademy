<template>
  <div class="card p-7 sm:p-8">
    <h1 class="text-xl font-bold text-ink">تعيين كلمة مرور جديدة</h1>
    <p class="mt-1 text-sm text-ink-muted">اختر كلمة مرور قوية لحسابك.</p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
      <FormField :label="$t('common.email')" :error="errors.email?.[0]" required>
        <input v-model="form.email" type="email" class="input" required dir="ltr" />
      </FormField>
      <FormField :label="$t('auth.password')" :error="errors.password?.[0]" required>
        <input v-model="form.password" type="password" class="input" required dir="ltr" />
      </FormField>
      <FormField :label="$t('auth.password_confirm')" required>
        <input v-model="form.password_confirmation" type="password" class="input" required dir="ltr" />
      </FormField>
      <button class="btn-primary w-full !py-3" :disabled="busy">
        <Loader2 v-if="busy" :size="17" class="animate-spin" />
        تحديث كلمة المرور
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import { Loader2 } from '@lucide/vue';

const route = useRoute();
const router = useRouter();
const form = ref({
  email: route.query.email || '',
  token: route.query.token || route.params.token || '',
  password: '',
  password_confirmation: '',
});
const errors = ref({});
const busy = ref(false);

async function submit() {
  busy.value = true;
  errors.value = {};
  try {
    await api.post('/auth/reset-password', form.value);
    toast('تم تحديث كلمة المرور. سجّل الدخول الآن.');
    router.push('/login');
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}
</script>
