<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-ink">استيراد الفيديو</h1>
        <p class="mt-0.5 text-sm text-ink-muted">Google Drive → مزود البث → تشغيل محمي.</p>
      </div>
      <button class="btn-outline !text-xs" @click="load()"><RefreshCw :size="14" /> تحديث</button>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>الدرس</th><th>الملف المصدر</th><th>المزود</th><th>الحالة</th><th>المدة</th><th>التاريخ</th><th></th></tr></thead>
        <tbody>
          <tr v-for="a in data.data" :key="a.id">
            <td class="font-semibold text-ink">{{ a.lesson?.title || '—' }}</td>
            <td class="max-w-[220px] truncate text-xs text-ink-muted" dir="ltr">{{ a.source_filename || a.google_file_id }}</td>
            <td class="text-xs">{{ a.provider }}</td>
            <td>
              <StatusBadge :status="a.status" />
              <p v-if="a.error_message" class="mt-1 max-w-[200px] truncate text-[11px] text-danger">{{ a.error_message }}</p>
            </td>
            <td class="text-xs">{{ a.duration_seconds ? Math.round(a.duration_seconds / 60) + ' د' : '—' }}</td>
            <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(a.created_at).toLocaleString('ar-EG') }}</td>
            <td>
              <button v-if="a.status === 'failed'" class="btn-outline !px-2.5 !py-1 !text-xs" @click="retry(a)">إعادة</button>
            </td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="7" class="py-10 text-center text-ink-faint">لا توجد استيرادات. استورد من صفحة تعديل الكورس.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import StatusBadge from '../../components/StatusBadge.vue';
import Pagination from '../../components/Pagination.vue';
import { RefreshCw } from '@lucide/vue';

const data = ref({});
const loading = ref(true);
let poller = null;

async function load(page = 1) {
  try {
    const { data: res } = await api.get('/admin/video-imports', { params: { page } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function retry(a) {
  try {
    await api.post(`/admin/video-imports/${a.id}/retry`);
    a.status = 'pending';
    toast('أُعيد الاستيراد إلى الطابور.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  }
}

onMounted(() => {
  load();
  poller = setInterval(() => {
    if ((data.value.data || []).some((a) => ['pending', 'importing', 'processing'].includes(a.status))) {
      load(data.value.current_page || 1);
    }
  }, 15000);
});
onBeforeUnmount(() => clearInterval(poller));
</script>
