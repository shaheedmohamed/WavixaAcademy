<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-ink">المدربون</h1>
        <p class="mt-0.5 text-sm text-ink-muted">{{ data.total ?? 0 }} مدرب</p>
      </div>
      <div class="flex gap-2">
        <div class="relative w-full sm:w-72">
          <Search :size="16" class="absolute start-3 top-1/2 -translate-y-1/2 text-ink-faint" />
          <input v-model="q" class="input ps-9" placeholder="بحث بالاسم أو البريد…" @input="debounced" />
        </div>
        <button class="btn-primary" @click="openCreate"><Plus :size="16" /> مدرب جديد</button>
      </div>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[640px]">
        <thead><tr><th>المدرب</th><th>الدور الوظيفي</th><th>الكورسات</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="i in data.data" :key="i.id" class="cursor-pointer" @click="$router.push(`/admin/instructors/${i.id}`)">
            <td>
              <p class="font-semibold text-ink">{{ i.name }}</p>
              <p class="text-xs text-ink-faint">{{ i.email }}</p>
            </td>
            <td class="text-sm text-ink-muted">{{ i.instructor_profile?.title || '—' }}</td>
            <td>{{ i.taught_courses_count }}</td>
            <td><StatusBadge :status="i.status" /></td>
            <td><ChevronLeft :size="16" class="text-ink-faint" /></td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="5" class="py-10 text-center text-ink-faint">لا توجد نتائج.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <Modal :open="createOpen" title="مدرب جديد" @close="createOpen = false">
      <form class="space-y-4" @submit.prevent="create">
        <FormField label="الاسم" required :error="errors.name?.[0]"><input v-model="form.name" class="input" required /></FormField>
        <FormField label="البريد الإلكتروني" required :error="errors.email?.[0]"><input v-model="form.email" class="input" dir="ltr" required /></FormField>
        <FormField label="رقم الهاتف" :error="errors.phone?.[0]"><input v-model="form.phone" class="input" dir="ltr" /></FormField>
        <FormField label="كلمة المرور" required :error="errors.password?.[0]"><input v-model="form.password" type="password" class="input" required /></FormField>
        <FormField label="الدور الوظيفي" :error="errors.title?.[0]"><input v-model="form.title" class="input" /></FormField>
        <FormField label="نبذة" :error="errors.bio?.[0]"><textarea v-model="form.bio" rows="3" class="input" /></FormField>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> إنشاء</button>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import Pagination from '../../components/Pagination.vue';
import { Search, Plus, ChevronLeft, Loader2 } from '@lucide/vue';

const data = ref({});
const q = ref('');
const loading = ref(true);
const createOpen = ref(false);
const busy = ref(false);
const errors = ref({});
const form = ref({ name: '', email: '', phone: '', password: '', title: '', bio: '' });
let timer = null;

function debounced() {
  clearTimeout(timer);
  timer = setTimeout(() => load(1), 350);
}

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/instructors', { params: { page, q: q.value || undefined } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  form.value = { name: '', email: '', phone: '', password: '', title: '', bio: '' };
  errors.value = {};
  createOpen.value = true;
}

async function create() {
  busy.value = true;
  errors.value = {};
  try {
    await api.post('/admin/instructors', form.value);
    toast('تم إنشاء المدرب.');
    createOpen.value = false;
    load();
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(() => load());
</script>
