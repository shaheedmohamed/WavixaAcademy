<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <nav class="mb-1 text-xs text-ink-faint"><RouterLink to="/admin/events" class="hover:text-ink">الفعاليات</RouterLink> / المسجلون</nav>
        <h1 class="text-xl font-bold text-ink">تسجيلات الفعالية</h1>
      </div>
      <RouterLink to="/admin/checkin" class="btn-primary !text-xs"><QrCode :size="14" /> تسجيل حضور</RouterLink>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[640px]">
        <thead><tr><th>الطالب</th><th>التذكرة</th><th>التسجيل</th><th>الحالة</th><th>الحضور</th></tr></thead>
        <tbody>
          <tr v-for="r in data.data" :key="r.id">
            <td>
              <p class="font-semibold text-ink">{{ r.user?.name }}</p>
              <p class="text-xs text-ink-faint" dir="ltr">{{ r.user?.student_number }}</p>
            </td>
            <td class="text-xs font-mono" dir="ltr">{{ r.ticket_code }}</td>
            <td class="text-xs text-ink-faint">{{ r.registered_at ? new Date(r.registered_at).toLocaleString('ar-EG') : '—' }}</td>
            <td><StatusBadge :status="r.status" /></td>
            <td>
              <span v-if="r.checked_in_at" class="badge-green">حضر {{ new Date(r.checked_in_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) }}</span>
              <span v-else class="text-xs text-ink-faint">—</span>
            </td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="5" class="py-10 text-center text-ink-faint">لا تسجيلات.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import StatusBadge from '../../components/StatusBadge.vue';
import Pagination from '../../components/Pagination.vue';
import { QrCode } from '@lucide/vue';

const route = useRoute();
const data = ref({});
const loading = ref(true);

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get(`/admin/events/${route.params.id}/registrations`, { params: { page } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>
