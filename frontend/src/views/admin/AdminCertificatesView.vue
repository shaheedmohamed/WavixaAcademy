<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-ink">الشهادات</h1>
        <p class="mt-0.5 text-sm text-ink-muted">{{ data.total ?? 0 }} شهادة صادرة</p>
      </div>
      <button class="btn-primary" @click="openIssue"><Plus :size="16" /> إصدار شهادة</button>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>الشهادة</th><th>الطالب</th><th>الكورس</th><th>الإصدار</th><th>التحقق</th><th></th></tr></thead>
        <tbody>
          <tr v-for="c in data.data" :key="c.id">
            <td class="font-mono text-xs" dir="ltr">{{ c.certificate_number }}</td>
            <td>
              <p class="font-semibold text-ink">{{ c.user?.name }}</p>
              <p class="text-xs text-ink-faint" dir="ltr">{{ c.user?.student_number }}</p>
            </td>
            <td class="text-sm">{{ c.course?.title }}</td>
            <td class="text-xs text-ink-faint">{{ new Date(c.issued_at).toLocaleDateString('ar-EG') }}</td>
            <td>
              <a :href="`/certificates/${c.verification_token}`" target="_blank" class="link text-xs">رابط</a>
            </td>
            <td><button class="btn-ghost !p-1.5 text-danger" @click="revoke(c)"><Trash2 :size="15" /></button></td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="6" class="py-10 text-center text-ink-faint">لا شهادات بعد.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <Modal :open="issueOpen" title="إصدار شهادة" @close="issueOpen = false">
      <form class="space-y-4" @submit.prevent="issue">
        <FormField label="الطالب (ID أو رقم الطالب)" required :error="errors.user_id?.[0]">
          <input v-model="form.user_id" class="input" dir="ltr" placeholder="123" required />
        </FormField>
        <FormField label="الكورس" required :error="errors.course_id?.[0]">
          <select v-model.number="form.course_id" class="input" required>
            <option :value="null" disabled>اختر…</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
          </select>
        </FormField>
        <label class="flex items-center gap-2 text-sm text-ink-soft">
          <input v-model="form.force" type="checkbox" class="rounded border-line text-primary" />
          إصدار إجباري (تجاوز شروط الإكمال)
        </label>
        <div v-if="eligibility" class="rounded-lg bg-amber-soft p-3 text-xs text-amber-900">
          تقدم الدروس {{ eligibility.lessons_percent }}% · مشاريع معلقة {{ eligibility.pending_projects }} · اختبارات غير مجتازة {{ eligibility.unpassed_quizzes }}
        </div>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> إصدار</button>
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
import Pagination from '../../components/Pagination.vue';
import { Plus, Trash2, Loader2 } from '@lucide/vue';

const data = ref({});
const courses = ref([]);
const loading = ref(true);
const issueOpen = ref(false);
const busy = ref(false);
const errors = ref({});
const eligibility = ref(null);
const form = ref({ user_id: '', course_id: null, force: false });

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/certificates', { params: { page } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function openIssue() {
  issueOpen.value = true;
  eligibility.value = null;
  if (!courses.value.length) {
    const { data: res } = await api.get('/admin/courses', { params: { per_page: 100 } });
    courses.value = res.data || [];
  }
}

async function issue() {
  busy.value = true;
  errors.value = {};
  eligibility.value = null;
  try {
    await api.post('/admin/certificates', form.value);
    toast('صدرت الشهادة.');
    issueOpen.value = false;
    load();
  } catch (e) {
    if (e?.response?.data?.eligibility) {
      eligibility.value = e.response.data.eligibility;
    }
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err; else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

async function revoke(c) {
  if (!confirm(`سحب الشهادة ${c.certificate_number}؟`)) return;
  await api.delete(`/admin/certificates/${c.id}`);
  load(data.value.current_page || 1);
}

onMounted(() => load());
</script>
