<template>
  <div class="card p-7 sm:p-8">
    <h1 class="text-xl font-bold text-ink">{{ $t('auth.login_title') }}</h1>
    <p class="mt-1 text-sm text-ink-muted">{{ $t('auth.login_sub') }}</p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
      <FormField :label="$t('common.email')" :error="errors.email?.[0]" required>
        <input v-model="form.email" type="email" class="input" autocomplete="email" required dir="ltr" />
      </FormField>
      <FormField :label="$t('auth.password')" :error="errors.password?.[0]" required>
        <input v-model="form.password" type="password" class="input" autocomplete="current-password" required dir="ltr" />
      </FormField>

      <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2 text-ink-muted">
          <input v-model="form.remember" type="checkbox" class="rounded border-line text-primary focus:ring-primary/30" />
          تذكرني
        </label>
        <RouterLink to="/forgot-password" class="link">{{ $t('auth.forgot') }}</RouterLink>
      </div>

      <button class="btn-primary w-full !py-3" :disabled="busy">
        <Loader2 v-if="busy" :size="17" class="animate-spin" />
        {{ $t('nav.login') }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
      {{ $t('auth.no_account') }}
      <RouterLink to="/register" class="link font-semibold">{{ $t('nav.register') }}</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { login } from '../../stores/auth';
import { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import { Loader2 } from '@lucide/vue';

const router = useRouter();
const route = useRoute();
const form = ref({ email: '', password: '', remember: true });
const errors = ref({});
const busy = ref(false);

async function submit() {
  busy.value = true;
  errors.value = {};
  try {
    const user = await login(form.value.email, form.value.password);
    toast(`أهلًا بك، ${user.name}`);
    const isInstructor = user.roles?.includes('instructor');
    router.push(route.query.redirect || (user.is_admin ? '/admin' : (isInstructor ? '/instructor' : '/dashboard')));
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}
</script>
