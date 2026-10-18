<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-ink">سجل العمليات</h1>
        <p class="mt-0.5 text-sm text-ink-muted">كل العمليات الحساسة على المنصة.</p>
      </div>
      <select v-model="action" class="input !w-56" @change="load(1)">
        <option value="">كل العمليات</option>
        <option value="auth">المصادقة</option>
        <option value="enrollment">الاشتراكات</option>
        <option value="video">الفيديو</option>
        <option value="event">الفعاليات</option>
        <option value="certificate">الشهادات</option>
        <option value="settings">الإعدادات</option>
        <option value="course">الكورسات</option>
        <option value="quiz">الاختبارات</option>
      </select>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>العملية</th><th>المستخدم</th><th>الهدف</th><th>IP</th><th>الوقت</th></tr></thead>
        <tbody>
          <tr v-for="l in data.data" :key="l.id">
            <td class="font-mono text-xs">{{ l.action }}</td>
            <td class="text-sm">{{ l.user?.name || 'نظام' }}</td>
            <td class="text-xs text-ink-muted font-mono">{{ l.auditable_type?.split('\\').pop() }}#{{ l.auditable_id || '—' }}</td>
            <td class="text-xs text-ink-faint" dir="ltr">{{ l.ip || '—' }}</td>
            <td class="text-xs text-ink-faint whitespace-nowrap">{{ new Date(l.created_at).toLocaleString('ar-EG') }}</td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="5" class="py-10 text-center text-ink-faint">السجل فارغ.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import Pagination from '../../components/Pagination.vue';

const data = ref({});
const action = ref('');
const loading = ref(true);

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/audit-logs', { params: { page, action: action.value || undefined } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>
