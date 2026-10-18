<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-ink">طلابي</h1>
        <p class="mt-0.5 text-sm text-ink-muted">الطلاب المشتركين في كورساتك والتفعيل.</p>
      </div>
      <button class="btn-primary" @click="openActivate"><Plus :size="16" /> تفعيل طالب</button>
    </div>

    <div class="flex flex-wrap gap-3">
      <input v-model="q" class="input !w-64" placeholder="بحث بالاسم أو البريد أو الموبايل" />
      <select v-model="courseFilter" class="input !w-48" @change="load(1)">
        <option value="">كل الكورسات</option>
        <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
      </select>
      <select v-model="status" class="input !w-40" @change="load(1)">
        <option value="">كل الحالات</option>
        <option value="active">نشط</option>
        <option value="pending">معلق</option>
        <option value="completed">مكتمل</option>
        <option value="suspended">موقوف</option>
        <option value="expired">منتهي</option>
      </select>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>الطالب</th><th>الكورس</th><th>رقم الموبايل</th><th>الحالة</th><th>ينتهي</th><th>مُفعل</th></tr></thead>
        <tbody>
          <tr v-for="e in data.data" :key="e.id">
            <td>
              <p class="font-semibold text-ink">{{ e.user?.name }}</p>
              <p class="text-xs text-ink-faint" dir="ltr">{{ e.user?.email }}</p>
            </td>
            <td class="text-sm text-ink-muted">{{ e.course?.title }}</td>
            <td class="text-sm" dir="ltr">{{ e.user?.phone || '—' }}</td>
            <td><StatusBadge :status="e.status" /></td>
            <td class="text-xs text-ink-faint">{{ e.expires_at ? new Date(e.expires_at).toLocaleDateString('ar-EG') : '—' }}</td>
            <td class="text-xs text-ink-faint">{{ e.activated_at ? new Date(e.activated_at).toLocaleDateString('ar-EG') : '—' }}</td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="6" class="py-10 text-center text-ink-faint">لا توجد اشتراكات.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <Modal :open="activateOpen" title="تفعيل كورس لطالب" @close="activateOpen = false">
      <form class="space-y-4" @submit.prevent="activate">
        <FormField label="البحث عن المستخدم" required>
          <div class="relative">
            <input v-model="search" class="input" placeholder="اكتب رقم الموبايل أو البريد أو الاسم" @input="searchUsers" />
            <Loader2 v-if="searching" :size="15" class="animate-spin absolute end-3 top-1/2 -translate-y-1/2" />
          </div>
          <ul v-if="found.length" class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-line bg-white">
            <li v-for="u in found" :key="u.id" class="cursor-pointer px-3 py-2 hover:bg-surface text-sm" @click="selectUser(u)">
              <p class="font-medium">{{ u.name }}</p>
              <p class="text-xs text-ink-faint" dir="ltr">{{ u.email }} — {{ u.phone || '—' }}</p>
            </li>
          </ul>
        </FormField>

        <div v-if="selectedUser" class="rounded-lg bg-surface p-3 text-sm">
          <p><b>مختار:</b> {{ selectedUser.name }} ({{ selectedUser.email }}) — {{ selectedUser.phone || '—' }}</p>
        </div>

        <FormField label="الكورس" required :error="errors.course_id?.[0]">
          <select v-model="form.course_id" class="input" required>
            <option :value="null" disabled>اختر كورس…</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
          </select>
        </FormField>

        <FormField label="تاريخ الانتهاء (اختياري)">
          <input v-model="form.expires_at" type="date" class="input" />
        </FormField>

        <FormField label="ملاحظات">
          <textarea v-model="form.notes" rows="2" class="input" />
        </FormField>

        <button class="btn-primary w-full" :disabled="busy || !selectedUser">
          <Loader2 v-if="busy" :size="15" class="animate-spin" /> تفعيل
        </button>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import Pagination from '../../components/Pagination.vue';
import { Plus, Loader2 } from '@lucide/vue';

const data = ref({});
const courses = ref([]);
const q = ref('');
const courseFilter = ref('');
const status = ref('');
const loading = ref(true);

const activateOpen = ref(false);
const search = ref('');
const found = ref([]);
const searching = ref(false);
const selectedUser = ref(null);
const busy = ref(false);
const errors = ref({});
const form = ref({ course_id: null, expires_at: '', notes: '' });

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/instructor/students', { params: { page, q: q.value || undefined, course_id: courseFilter.value || undefined, status: status.value || undefined } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function loadCourses() {
  const { data: res } = await api.get('/instructor/courses', { params: { per_page: 100 } });
  courses.value = res.data || [];
}

let searchTimeout;
function searchUsers() {
  found.value = [];
  if (searchTimeout) clearTimeout(searchTimeout);
  if (search.value.length < 2) return;
  searchTimeout = setTimeout(async () => {
    searching.value = true;
    try {
      const { data: res } = await api.get('/instructor/users/search', { params: { q: search.value } });
      found.value = res.users || [];
    } finally {
      searching.value = false;
    }
  }, 300);
}

function selectUser(u) {
  selectedUser.value = u;
  search.value = u.name;
  found.value = [];
}

function openActivate() {
  activateOpen.value = true;
  selectedUser.value = null;
  search.value = '';
  found.value = [];
  form.value = { course_id: null, expires_at: '', notes: '' };
  errors.value = {};
  if (!courses.value.length) loadCourses();
}

async function activate() {
  busy.value = true;
  errors.value = {};
  try {
    await api.post(`/instructor/users/${selectedUser.value.id}/enrollments`, {
      ...form.value,
      expires_at: form.value.expires_at || undefined,
    });
    toast('تم تفعيل الكورس للطالب.');
    activateOpen.value = false;
    load(1);
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err; else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

let qTimeout;
watch(q, () => {
  if (qTimeout) clearTimeout(qTimeout);
  qTimeout = setTimeout(() => load(1), 300);
});

watch(() => activateOpen.value, (v) => { if (!v) selectedUser.value = null; });

onMounted(() => { load(); loadCourses(); });
</script>
