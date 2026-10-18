<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-ink">الفعاليات</h1>
        <p class="mt-0.5 text-sm text-ink-muted">{{ data.total ?? 0 }} فعالية</p>
      </div>
      <button class="btn-primary" @click="openForm()"><Plus :size="16" /> فعالية جديدة</button>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>الفعالية</th><th>النوع</th><th>التاريخ</th><th>المسجلون</th><th>الظهور</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="e in data.data" :key="e.id">
            <td class="font-semibold text-ink">{{ e.title }}</td>
            <td class="text-xs">{{ typeLabel(e.type) }}</td>
            <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(e.start_at).toLocaleString('ar-EG', { dateStyle: 'short', timeStyle: 'short' }) }}</td>
            <td>{{ e.registrations_count }}</td>
            <td class="text-xs text-ink-muted">{{ visLabel(e.visibility) }}</td>
            <td><StatusBadge :status="e.status" /></td>
            <td class="whitespace-nowrap">
              <button class="btn-ghost !p-1.5" title="المسجلون" @click="$router.push(`/admin/events/${e.id}/registrations`)"><Users :size="15" /></button>
              <button class="btn-ghost !p-1.5" @click="openForm(e)"><Pencil :size="15" /></button>
              <button class="btn-ghost !p-1.5 text-danger" @click="remove(e)"><Trash2 :size="15" /></button>
            </td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="7" class="py-10 text-center text-ink-faint">لا فعاليات.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <Modal :open="formOpen" :title="form.id ? 'تعديل الفعالية' : 'فعالية جديدة'" @close="formOpen = false">
      <form class="space-y-4" @submit.prevent="save">
        <FormField label="العنوان" required :error="errors.title?.[0]"><input v-model="form.title" class="input" required /></FormField>
        <FormField label="الوصف"><textarea v-model="form.description" rows="3" class="input" /></FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="النوع">
            <select v-model="form.type" class="input">
              <option value="workshop">ورشة عمل</option><option value="live_session">جلسة مباشرة</option>
              <option value="webinar">ويبينار</option><option value="qna">أسئلة وأجوبة</option>
              <option value="meetup">لقاء</option><option value="graduation">تخرج</option><option value="other">أخرى</option>
            </select>
          </FormField>
          <FormField label="الظهور">
            <select v-model="form.visibility" class="input">
              <option value="public">عام</option><option value="students_only">للطلاب فقط</option>
              <option value="course_students">لطلاب كورس</option><option value="invite_only">بدعوة</option>
            </select>
          </FormField>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="البداية" required :error="errors.start_at?.[0]"><input v-model="form.start_at" type="datetime-local" class="input" dir="ltr" /></FormField>
          <FormField label="النهاية"><input v-model="form.end_at" type="datetime-local" class="input" dir="ltr" /></FormField>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="المكان (حضوري)"><input v-model="form.location" class="input" /></FormField>
          <FormField label="رابط أونلاين"><input v-model="form.online_url" class="input" dir="ltr" /></FormField>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <FormField label="السعة"><input v-model.number="form.capacity" type="number" min="1" class="input" dir="ltr" /></FormField>
          <FormField label="السعر"><input v-model.number="form.price" type="number" min="0" step="0.01" class="input" dir="ltr" /></FormField>
          <FormField label="المتحدث"><input v-model="form.speaker" class="input" /></FormField>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="الكورس المرتبط">
            <select v-model="form.course_id" class="input">
              <option :value="null">—</option>
              <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
            </select>
          </FormField>
          <FormField label="الحالة">
            <select v-model="form.status" class="input">
              <option value="draft">مسودة</option><option value="published">منشور</option>
              <option value="cancelled">ملغي</option><option value="completed">مكتمل</option>
            </select>
          </FormField>
        </div>
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
import { Plus, Pencil, Trash2, Users, Loader2 } from '@lucide/vue';

const data = ref({});
const courses = ref([]);
const loading = ref(true);
const formOpen = ref(false);
const busy = ref(false);
const errors = ref({});
const form = ref({});

const typeLabel = (t) => ({ workshop: 'ورشة', live_session: 'مباشرة', webinar: 'ويبينار', qna: 'أسئلة', meetup: 'لقاء', graduation: 'تخرج', other: 'أخرى' }[t] || t);
const visLabel = (v) => ({ public: 'عام', students_only: 'للطلاب', course_students: 'لطلاب كورس', invite_only: 'بدعوة' }[v] || v);

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/events', { params: { page } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function openForm(e = null) {
  form.value = e
    ? { ...e, start_at: e.start_at?.slice(0, 16), end_at: e.end_at?.slice(0, 16) }
    : { title: '', type: 'webinar', visibility: 'public', status: 'draft', timezone: 'Africa/Cairo', course_id: null };
  errors.value = {};
  formOpen.value = true;
  if (!courses.value.length) {
    const { data: res } = await api.get('/admin/courses', { params: { per_page: 100 } });
    courses.value = res.data || [];
  }
}

async function save() {
  busy.value = true;
  errors.value = {};
  try {
    if (form.value.id) {
      await api.patch(`/admin/events/${form.value.id}`, form.value);
    } else {
      await api.post('/admin/events', form.value);
    }
    toast('تم الحفظ.');
    formOpen.value = false;
    load(data.value.current_page || 1);
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err; else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

async function remove(e) {
  if (!confirm(`حذف «${e.title}»؟`)) return;
  await api.delete(`/admin/events/${e.id}`);
  load(data.value.current_page || 1);
}

onMounted(() => load());
</script>
