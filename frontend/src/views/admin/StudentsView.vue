<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-ink">الطلاب</h1>
        <p class="mt-0.5 text-sm text-ink-muted">{{ data.total ?? 0 }} طالب</p>
      </div>
      <div class="relative w-full sm:w-72">
        <Search :size="16" class="absolute start-3 top-1/2 -translate-y-1/2 text-ink-faint" />
        <input v-model="q" class="input ps-9" placeholder="بحث بالاسم أو البريد أو رقم الطالب…" @input="debounced" />
      </div>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[640px]">
        <thead>
          <tr><th>الطالب</th><th>رقم الطالب</th><th>الاشتراكات</th><th>الشهادات</th><th>الحالة</th><th>انضم</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="s in data.data" :key="s.id" class="cursor-pointer" @click="$router.push(`/admin/students/${s.id}`)">
            <td>
              <p class="font-semibold text-ink">{{ s.name }}</p>
              <p class="text-xs text-ink-faint">{{ s.email }}</p>
            </td>
            <td class="text-xs" dir="ltr">{{ s.student_number }}</td>
            <td>{{ s.enrollments_count }}</td>
            <td>{{ s.certificates_count }}</td>
            <td><StatusBadge :status="s.status" /></td>
            <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(s.created_at).toLocaleDateString('ar-EG') }}</td>
            <td>
              <button class="btn-ghost !p-1.5 text-danger" @click.stop="remove(s)">
                <Trash2 :size="16" />
              </button>
            </td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="6" class="py-10 text-center text-ink-faint">لا توجد نتائج.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import StatusBadge from '../../components/StatusBadge.vue';
import Pagination from '../../components/Pagination.vue';
import { Search, Trash2 } from '@lucide/vue';

const data = ref({});
const q = ref('');
const loading = ref(true);
let timer = null;

function debounced() {
  clearTimeout(timer);
  timer = setTimeout(() => load(1), 350);
}

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/students', { params: { page, q: q.value || undefined } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function remove(s) {
  if (!confirm(`حذف الطالب «${s.name}» نهائيًا؟ لا يمكن التراجع.`)) return;
  try {
    await api.delete(`/admin/students/${s.id}`);
    toast('تم حذف الطالب.');
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  }
}

onMounted(() => load());
</script>
