<template>
  <div class="card p-7 sm:p-8">
    <h1 class="text-xl font-bold text-ink">{{ $t('auth.register_title') }}</h1>
    <p class="mt-1 text-sm text-ink-muted">{{ $t('auth.register_sub') }}</p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
      <FormField :label="$t('auth.full_name')" :error="errors.name?.[0]" required>
        <input v-model="form.name" class="input" autocomplete="name" required />
      </FormField>
      <FormField :label="$t('common.email')" :error="errors.email?.[0]" required>
        <input v-model="form.email" type="email" class="input" autocomplete="email" required dir="ltr" />
      </FormField>
      <FormField :label="$t('auth.phone')" :error="errors.phone?.[0]">
        <input v-model="form.phone" class="input" autocomplete="tel" dir="ltr" required placeholder="01xxxxxxxxx" />
      </FormField>
      <FormField :label="$t('auth.password')" :error="errors.password?.[0]" required hint="8 أحرف على الأقل — أحرف كبيرة وصغيرة وأرقام.">
        <input v-model="form.password" type="password" class="input" autocomplete="new-password" required dir="ltr" />
      </FormField>
      <FormField :label="$t('auth.password_confirm')" required>
        <input v-model="form.password_confirmation" type="password" class="input" autocomplete="new-password" required dir="ltr" />
      </FormField>

      <button class="btn-primary w-full !py-3" :disabled="busy">
        <Loader2 v-if="busy" :size="17" class="animate-spin" />
        {{ $t('nav.register') }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
      {{ $t('auth.have_account') }}
      <RouterLink to="/login" class="link font-semibold">{{ $t('nav.login') }}</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { register } from '../../stores/auth';
import { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import { Loader2 } from '@lucide/vue';

const router = useRouter();
const form = ref({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const errors = ref({});
const busy = ref(false);

async function submit() {
  busy.value = true;
  errors.value = {};
  try {
    await register(form.value);
    toast('تم إنشاء حسابك بنجاح. أهلًا بك!');
    router.push('/dashboard');
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}
</script>
