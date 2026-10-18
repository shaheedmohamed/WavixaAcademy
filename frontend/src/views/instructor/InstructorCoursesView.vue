<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-ink">كورساتي</h1>
        <p class="mt-0.5 text-sm text-ink-muted">{{ data.total ?? 0 }} كورس</p>
      </div>
      <div class="flex gap-2">
        <select v-model="status" class="input !w-40" @change="load(1)">
          <option value="">كل الحالات</option>
          <option value="draft">مسودة</option>
          <option value="published">منشور</option>
          <option value="archived">مؤرشف</option>
        </select>
        <button class="btn-primary" @click="openCreate"><Plus :size="16" /> كورس جديد</button>
      </div>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>الكورس</th><th>الوحدات</th><th>الاشتراكات</th><th>السعر</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="c in data.data" :key="c.id" class="cursor-pointer" @click="$router.push(`/instructor/courses/${c.id}`)">
            <td class="font-semibold text-ink">{{ c.title }}</td>
            <td>{{ c.modules_count }}</td>
            <td>{{ c.enrollments_count }}</td>
            <td>{{ Number(c.price) > 0 ? c.price + ' ' + c.currency : 'مجاني' }}</td>
            <td><StatusBadge :status="c.status" /></td>
            <td>
              <div class="flex items-center justify-end gap-2">
                <button
                  class="rounded-lg p-1.5 text-red-500 transition hover:bg-red-50"
                  title="حذف الكورس"
                  @click.stop="remove(c)"
                ><Trash2 :size="16" /></button>
                <ChevronLeft :size="16" class="text-ink-faint" />
              </div>
            </td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="6" class="py-10 text-center text-ink-faint">لا توجد كورسات. أنشئ أول كورس.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <Modal :open="createOpen" title="كورس جديد" @close="createOpen = false">
      <form class="space-y-4" @submit.prevent="create">
        <FormField label="عنوان الكورس" required :error="errors.title?.[0]">
          <input v-model="form.title" class="input" required />
        </FormField>
        <FormField label="وصف مختصر" :error="errors.short_description?.[0]">
          <textarea v-model="form.short_description" rows="2" class="input" />
        </FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="السعر" :error="errors.price?.[0]">
            <input v-model.number="form.price" type="number" min="0" step="0.01" class="input" dir="ltr" />
          </FormField>
          <FormField label="المستوى">
            <select v-model="form.level" class="input">
              <option value="all_levels">كل المستويات</option>
              <option value="beginner">مبتدئ</option>
              <option value="intermediate">متوسط</option>
              <option value="advanced">متقدم</option>
            </select>
          </FormField>
        </div>
        <FormField label="الحالة">
          <select v-model="form.status" class="input">
            <option value="draft">مسودة</option>
            <option value="published">منشور</option>
          </select>
        </FormField>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> إنشاء</button>
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
import { Plus, ChevronLeft, Loader2, Trash2 } from '@lucide/vue';

const router = useRouter();
const data = ref({});
const status = ref('');
const loading = ref(true);
const createOpen = ref(false);
const busy = ref(false);
const errors = ref({});
const form = ref({ title: '', short_description: '', price: 0, level: 'all_levels', status: 'draft' });

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/instructor/courses', { params: { page, status: status.value || undefined } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  form.value = { title: '', short_description: '', price: 0, level: 'all_levels', status: 'draft' };
  errors.value = {};
  createOpen.value = true;
}

async function create() {
  busy.value = true;
  errors.value = {};
  try {
    const { data: res } = await api.post('/instructor/courses', form.value);
    toast('تم إنشاء الكورس.');
    router.push(`/instructor/courses/${res.course.id}`);
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err; else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

async function remove(c) {
  if (!confirm(`حذف الكورس "${c.title}" نهائيًا؟ سيتم حذف كل وحداته ودروسه.`)) return;
  try {
    await api.delete(`/instructor/courses/${c.id}`);
    toast('تم حذف الكورس.');
    load(data.value.current_page || 1);
  } catch (e) {
    toast(unwrapError(e), 'error');
  }
}

onMounted(() => load());
</script>
