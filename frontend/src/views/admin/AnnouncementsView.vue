<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-ink">الإعلانات</h1>
        <p class="mt-0.5 text-sm text-ink-muted">إشعارات داخل المنصة للطلاب.</p>
      </div>
      <button class="btn-primary" @click="openForm()"><Plus :size="16" /> إعلان جديد</button>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[640px]">
        <thead><tr><th>العنوان</th><th>الجمهور</th><th>الحالة</th><th>نُشر</th><th></th></tr></thead>
        <tbody>
          <tr v-for="a in data.data" :key="a.id">
            <td class="font-semibold text-ink">{{ a.title }}</td>
            <td class="text-xs">{{ audLabel(a.audience) }}</td>
            <td><StatusBadge :status="a.status" /></td>
            <td class="text-xs text-ink-faint">{{ a.published_at ? new Date(a.published_at).toLocaleDateString('ar-EG') : '—' }}</td>
            <td class="whitespace-nowrap">
              <button class="btn-ghost !p-1.5" @click="openForm(a)"><Pencil :size="15" /></button>
              <button class="btn-ghost !p-1.5 text-danger" @click="remove(a)"><Trash2 :size="15" /></button>
            </td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="5" class="py-10 text-center text-ink-faint">لا إعلانات.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <Modal :open="formOpen" :title="form.id ? 'تعديل إعلان' : 'إعلان جديد'" @close="formOpen = false">
      <form class="space-y-4" @submit.prevent="save">
        <FormField label="العنوان" required><input v-model="form.title" class="input" required /></FormField>
        <FormField label="نص الإعلان" required><textarea v-model="form.body" rows="4" class="input" required /></FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="الجمهور">
            <select v-model="form.audience" class="input">
              <option value="all">الكل</option>
              <option value="students">الطلاب</option>
              <option value="course_students">طلاب كورس محدد</option>
            </select>
          </FormField>
          <FormField v-if="form.audience === 'course_students'" label="الكورس" required>
            <select v-model="form.course_id" class="input" required>
              <option :value="null" disabled>اختر…</option>
              <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
            </select>
          </FormField>
          <FormField label="الحالة">
            <select v-model="form.status" class="input">
              <option value="draft">مسودة</option>
              <option value="published">نشر الآن</option>
            </select>
          </FormField>
        </div>
        <p v-if="form.status === 'published'" class="rounded-lg bg-amber-soft p-3 text-xs text-amber-900">عند النشر سيصل إشعار لكل الجمهور المستهدف فورًا.</p>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ</button>
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
import { Plus, Pencil, Trash2, Loader2 } from '@lucide/vue';

const data = ref({});
const courses = ref([]);
const loading = ref(true);
const formOpen = ref(false);
const busy = ref(false);
const form = ref({});

const audLabel = (a) => ({ all: 'الكل', students: 'الطلاب', course_students: 'طلاب كورس' }[a] || a);

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/announcements', { params: { page } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function openForm(a = null) {
  form.value = a ? { ...a } : { title: '', body: '', audience: 'all', status: 'draft', course_id: null };
  formOpen.value = true;
  if (!courses.value.length) {
    const { data: res } = await api.get('/admin/courses', { params: { per_page: 100 } });
    courses.value = res.data || [];
  }
}

async function save() {
  busy.value = true;
  try {
    if (form.value.id) await api.patch(`/admin/announcements/${form.value.id}`, form.value);
    else await api.post('/admin/announcements', form.value);
    toast('تم الحفظ.');
    formOpen.value = false;
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

async function remove(a) {
  if (!confirm(`حذف «${a.title}»؟`)) return;
  await api.delete(`/admin/announcements/${a.id}`);
  load();
}

onMounted(() => load());
</script>
