<template>
  <div class="container-site py-12 sm:py-16">
    <div class="grid gap-10 lg:grid-cols-2">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-ink">تواصل معنا</h1>
        <p class="mt-3 text-ink-muted leading-relaxed">
          سؤال عن كورس؟ تحتاج مساعدة في التفعيل؟ راسلنا وسنرد في أقرب وقت.
        </p>
        <div class="mt-8 space-y-4">
          <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener" class="card flex items-center gap-4 p-5 hover:shadow-pop transition-shadow">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-soft text-emerald"><MessageCircle :size="21" /></div>
            <div>
              <p class="font-bold text-ink text-sm">WhatsApp</p>
              <p class="text-xs text-ink-muted">أسرع وسيلة للتواصل — للتفعيل والاستفسارات</p>
            </div>
          </a>
          <a v-if="settings.support_email" :href="'mailto:' + settings.support_email" class="card flex items-center gap-4 p-5 hover:shadow-pop transition-shadow">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-faint text-primary"><Mail :size="21" /></div>
            <div>
              <p class="font-bold text-ink text-sm">البريد الإلكتروني</p>
              <p class="text-xs text-ink-muted">{{ settings.support_email }}</p>
            </div>
          </a>
        </div>
      </div>

      <div class="card p-6 sm:p-8">
        <h2 class="font-bold text-ink">أرسل رسالة</h2>
        <form class="mt-5 space-y-4" @submit.prevent="submit">
          <FormField label="الاسم" :error="errors.name?.[0]" required>
            <input v-model="form.name" class="input" required />
          </FormField>
          <FormField label="البريد الإلكتروني" :error="errors.email?.[0]" required>
            <input v-model="form.email" type="email" class="input" required />
          </FormField>
          <FormField label="الموضوع" :error="errors.subject?.[0]">
            <input v-model="form.subject" class="input" />
          </FormField>
          <FormField label="رسالتك" :error="errors.message?.[0]" required>
            <textarea v-model="form.message" rows="5" class="input" required />
          </FormField>
          <button class="btn-primary w-full" :disabled="busy">
            <Loader2 v-if="busy" :size="16" class="animate-spin" />
            إرسال
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api, { unwrapError } from '../../api/client';
import { settings, loadSettings } from '../../stores/settings';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import { MessageCircle, Mail, Loader2 } from '@lucide/vue';

const form = ref({ name: '', email: '', subject: '', message: '' });
const errors = ref({});
const busy = ref(false);

const whatsappLink = computed(() => {
  const n = String(settings.whatsapp_number || '').replace(/\D/g, '');
  return n ? `https://wa.me/${n}` : null;
});

async function submit() {
  busy.value = true;
  errors.value = {};
  try {
    const { data } = await api.post('/contact', form.value);
    toast(data.message || 'تم الإرسال بنجاح.');
    form.value = { name: '', email: '', subject: '', message: '' };
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(() => loadSettings());
</script>
