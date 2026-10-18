<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div>
      <nav class="mb-1 flex items-center gap-2 text-xs text-ink-faint">
        <RouterLink to="/admin/instructors" class="hover:text-ink">المدربون</RouterLink> / <span>إضافة مدرب</span>
      </nav>
      <h1 class="text-xl font-bold text-ink">إضافة مدرب جديد</h1>
      <p class="mt-0.5 text-sm text-ink-muted">أنشئ حساب مدرب واربطه بملفه الشخصي.</p>
    </div>

    <form class="card space-y-5 p-6" @submit.prevent="submit">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="الاسم الكامل" required :error="errors.name?.[0]">
          <input v-model="form.name" class="input" required />
        </FormField>
        <FormField label="البريد الإلكتروني" required :error="errors.email?.[0]">
          <input v-model="form.email" type="email" class="input" dir="ltr" required />
        </FormField>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="رقم الهاتف" :error="errors.phone?.[0]">
          <input v-model="form.phone" class="input" dir="ltr" />
        </FormField>
        <FormField label="كلمة المرور" required :error="errors.password?.[0]" hint="8 أحرف على الأقل — أحرف كبيرة وصغيرة وأرقام.">
          <input v-model="form.password" type="password" class="input" dir="ltr" required />
        </FormField>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="الدور الوظيفي" :error="errors.title?.[0]">
          <input v-model="form.title" class="input" placeholder="مثال: مهندس برمجيات" />
        </FormField>
        <FormField label="الحالة" :error="errors.status?.[0]">
          <select v-model="form.status" class="input">
            <option value="active">نشط</option>
            <option value="suspended">موقوف</option>
          </select>
        </FormField>
      </div>

      <FormField label="نبذة عن المدرب" :error="errors.bio?.[0]">
        <textarea v-model="form.bio" rows="4" class="input" />
      </FormField>

      <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input v-model="form.is_public" type="checkbox" class="rounded border-line text-primary focus:ring-primary/30" />
        إظهار المدرب في الصفحة العامة للمدربين
      </label>

      <div class="flex gap-3 pt-2">
        <button class="btn-primary" :disabled="busy">
          <Loader2 v-if="busy" :size="15" class="animate-spin" />
          إنشاء المدرب
        </button>
        <RouterLink to="/admin/instructors" class="btn-ghost">إلغاء</RouterLink>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import { Loader2 } from '@lucide/vue';

const router = useRouter();
const busy = ref(false);
const errors = ref({});
const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  title: '',
  bio: '',
  status: 'active',
  is_public: true,
});

async function submit() {
  busy.value = true;
  errors.value = {};
  try {
    await api.post('/admin/instructors', form.value);
    toast('تم إنشاء المدرب بنجاح.');
    router.push('/admin/instructors');
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}
</script>
