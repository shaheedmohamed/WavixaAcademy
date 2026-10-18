<template>
  <div v-if="loading"><div class="skeleton h-8 w-1/3 mb-4" /><div class="skeleton h-96" /></div>

  <div v-else-if="user" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <nav class="mb-1 flex items-center gap-2 text-xs text-ink-faint">
          <RouterLink to="/admin/instructors" class="hover:text-ink">المدربون</RouterLink> / <span>{{ user.name }}</span>
        </nav>
        <h1 class="text-xl font-bold text-ink">{{ user.name }}</h1>
      </div>
      <button class="btn-danger !text-xs" @click="remove"><Trash2 :size="14" /> حذف المدرب</button>
    </div>

    <form class="card max-w-3xl space-y-5 p-6" @submit.prevent="save">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="الاسم" required :error="errors.name?.[0]"><input v-model="form.name" class="input" required /></FormField>
        <FormField label="البريد الإلكتروني" required :error="errors.email?.[0]"><input v-model="form.email" class="input" dir="ltr" required /></FormField>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="رقم الهاتف" :error="errors.phone?.[0]"><input v-model="form.phone" class="input" dir="ltr" /></FormField>
        <FormField label="كلمة المرور الجديدة (اختياري)" :error="errors.password?.[0]"><input v-model="form.password" type="password" class="input" /></FormField>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="الدور الوظيفي" :error="errors.title?.[0]"><input v-model="form.title" class="input" /></FormField>
        <FormField label="الحالة" :error="errors.status?.[0]">
          <select v-model="form.status" class="input">
            <option value="active">نشط</option>
            <option value="suspended">موقوف</option>
          </select>
        </FormField>
      </div>
      <FormField label="نبذة" :error="errors.bio?.[0]"><textarea v-model="form.bio" rows="4" class="input" /></FormField>
      <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input v-model="form.is_public" type="checkbox" class="rounded border-line text-primary focus:ring-primary/30" />
        إظهار المدرب في صفحة المدربين العامة
      </label>
      <button class="btn-primary" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ التغييرات</button>
    </form>

    <section v-if="user.taught_courses?.length" class="card overflow-hidden">
      <div class="border-b border-line px-5 py-3.5 text-sm font-bold text-ink">الكورسات</div>
      <table class="table-base">
        <tbody>
          <tr v-for="c in user.taught_courses" :key="c.id">
            <td class="font-semibold text-ink">{{ c.title }}</td>
            <td><StatusBadge :status="c.status" /></td>
            <td><RouterLink :to="`/admin/courses/${c.id}`" class="link text-xs">تحرير</RouterLink></td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { Loader2, Trash2 } from '@lucide/vue';

const route = useRoute();
const router = useRouter();
const user = ref(null);
const loading = ref(true);
const busy = ref(false);
const errors = ref({});
const form = ref({});

async function load() {
  const { data } = await api.get(`/admin/instructors/${route.params.id}`);
  user.value = data.user;
  const p = data.user.instructor_profile || {};
  form.value = {
    name: data.user.name,
    email: data.user.email,
    phone: data.user.phone || '',
    password: '',
    status: data.user.status,
    title: p.title || '',
    bio: p.bio || '',
    is_public: p.is_public ?? true,
  };
  loading.value = false;
}

async function save() {
  busy.value = true;
  errors.value = {};
  try {
    await api.patch(`/admin/instructors/${route.params.id}`, form.value);
    toast('تم حفظ المدرب.');
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

async function remove() {
  if (!confirm('حذف المدرب نهائيًا؟ لا يمكن التراجع.')) return;
  await api.delete(`/admin/instructors/${route.params.id}`);
  toast('تم الحذف.');
  router.push('/admin/instructors');
}

onMounted(() => load());
</script>
