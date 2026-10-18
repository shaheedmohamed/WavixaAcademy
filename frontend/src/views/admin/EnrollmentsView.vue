<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-ink">الاشتراكات</h1>
        <p class="mt-0.5 text-sm text-ink-muted">{{ data.total ?? 0 }} اشتراك</p>
      </div>
      <div class="flex gap-2">
        <select v-model="status" class="input !w-40" @change="load(1)">
          <option value="">كل الحالات</option>
          <option value="pending">قيد الانتظار</option>
          <option value="active">نشط</option>
          <option value="suspended">موقوف</option>
          <option value="expired">منتهي</option>
          <option value="completed">مكتمل</option>
          <option value="cancelled">ملغي</option>
        </select>
      </div>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>الطالب</th><th>الكورس</th><th>الدفعة</th><th>فُعّل</th><th>ينتهي</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="e in data.data" :key="e.id">
            <td>
              <RouterLink :to="`/admin/students/${e.user?.id}`" class="font-semibold text-ink hover:text-primary">{{ e.user?.name }}</RouterLink>
              <p class="text-xs text-ink-faint" dir="ltr">{{ e.user?.student_number }}</p>
            </td>
            <td class="text-sm">{{ e.course?.title }}</td>
            <td class="text-xs text-ink-muted">{{ e.cohort?.name || '—' }}</td>
            <td class="text-xs text-ink-faint">{{ e.activated_at ? new Date(e.activated_at).toLocaleDateString('ar-EG') : '—' }}</td>
            <td class="text-xs text-ink-faint">{{ e.expires_at ? new Date(e.expires_at).toLocaleDateString('ar-EG') : '∞' }}</td>
            <td><StatusBadge :status="e.status" /></td>
            <td>
              <select :value="e.status" class="input !w-28 !py-1 !text-xs" @click.stop @change="(ev) => update(e, ev.target.value)">
                <option value="pending">قيد الانتظار</option>
                <option value="active">نشط</option>
                <option value="suspended">موقوف</option>
                <option value="expired">منتهي</option>
                <option value="completed">مكتمل</option>
                <option value="cancelled">ملغي</option>
              </select>
            </td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="7" class="py-10 text-center text-ink-faint">لا توجد اشتراكات.</td></tr>
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

const data = ref({});
const status = ref('');
const loading = ref(true);

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/enrollments', { params: { page, status: status.value || undefined } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function update(e, newStatus) {
  try {
    await api.patch(`/admin/enrollments/${e.id}`, { status: newStatus });
    e.status = newStatus;
    toast('تم تحديث الاشتراك.');
  } catch (err) {
    toast(unwrapError(err), 'error');
  }
}

onMounted(() => load());
</script>
