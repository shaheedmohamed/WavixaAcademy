<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-ink">الاختبارات</h1>
        <p class="mt-0.5 text-sm text-ink-muted">{{ data.total ?? 0 }} اختبار</p>
      </div>
      <button class="btn-primary" @click="openCreate"><Plus :size="16" /> اختبار جديد</button>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[640px]">
        <thead><tr><th>الاختبار</th><th>الكورس</th><th>الأسئلة</th><th>المحاولات</th><th>النجاح من</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="q in data.data" :key="q.id" class="cursor-pointer" @click="$router.push(`/admin/quizzes/${q.id}`)">
            <td class="font-semibold text-ink">{{ q.title }}</td>
            <td class="text-sm text-ink-muted">{{ q.course?.title }}</td>
            <td>{{ q.questions_count }}</td>
            <td>{{ q.attempts_count }}</td>
            <td>{{ q.passing_score }}%</td>
            <td><StatusBadge :status="q.status" /></td>
            <td><ChevronLeft :size="16" class="text-ink-faint" /></td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="7" class="py-10 text-center text-ink-faint">لا اختبارات.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <Modal :open="createOpen" title="اختبار جديد" @close="createOpen = false">
      <form class="space-y-4" @submit.prevent="create">
        <FormField label="الكورس" required :error="errors.course_id?.[0]">
          <select v-model="form.course_id" class="input" required>
            <option :value="null" disabled>اختر…</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
          </select>
        </FormField>
        <FormField label="العنوان" required :error="errors.title?.[0]"><input v-model="form.title" class="input" required /></FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="درجة النجاح %"><input v-model.number="form.passing_score" type="number" min="1" max="100" class="input" dir="ltr" /></FormField>
          <FormField label="عدد المحاولات"><input v-model.number="form.max_attempts" type="number" min="1" max="10" class="input" dir="ltr" /></FormField>
        </div>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> إنشاء وتحرير الأسئلة</button>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import Pagination from '../../components/Pagination.vue';
import { Plus, ChevronLeft, Loader2 } from '@lucide/vue';

const router = useRouter();
const data = ref({});
const courses = ref([]);
const loading = ref(true);
const createOpen = ref(false);
const busy = ref(false);
const errors = ref({});
const form = ref({ course_id: null, title: '', passing_score: 70, max_attempts: 3 });

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/quizzes', { params: { page } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function openCreate() {
  createOpen.value = true;
  if (!courses.value.length) {
    const { data: res } = await api.get('/admin/courses', { params: { per_page: 100 } });
    courses.value = res.data || [];
  }
}

async function create() {
  busy.value = true;
  errors.value = {};
  try {
    const course = courses.value.find((c) => c.id === form.value.course_id);
    const { data: res } = await api.post(`/admin/courses/${form.value.course_id}/quizzes`, {
      ...form.value,
      status: 'draft',
      questions: [{ question: 'سؤال جديد', options: [{ text: 'إجابة 1', is_correct: true }, { text: 'إجابة 2', is_correct: false }] }],
    });
    toast('أُنشئ الاختبار — أضف الأسئلة الآن.');
    router.push(`/admin/quizzes/${res.quiz.id}`);
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err; else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(() => load());
</script>
