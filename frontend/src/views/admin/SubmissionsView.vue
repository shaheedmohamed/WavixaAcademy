<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-ink">تسليمات المشاريع</h1>
        <p class="mt-0.5 text-sm text-ink-muted">مراجعة وتقييم مشاريع الطلاب.</p>
      </div>
      <select v-model="status" class="input !w-44" @change="load(1)">
        <option value="">كل الحالات</option>
        <option value="submitted">مُرسل</option>
        <option value="under_review">قيد المراجعة</option>
        <option value="approved">مقبول</option>
        <option value="needs_changes">يحتاج تعديلات</option>
        <option value="rejected">مرفوض</option>
      </select>
    </div>

    <div class="card overflow-x-auto">
      <table class="table-base min-w-[720px]">
        <thead><tr><th>الطالب</th><th>المشروع</th><th>الروابط</th><th>أُرسل</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <tr v-for="s in data.data" :key="s.id">
            <td>
              <p class="font-semibold text-ink">{{ s.user?.name }}</p>
              <p class="text-xs text-ink-faint" dir="ltr">{{ s.user?.student_number }}</p>
            </td>
            <td class="text-sm">
              {{ s.project?.title }}
              <p v-if="s.project?.module" class="text-[11px] text-ink-faint">{{ s.project.module.title }}</p>
            </td>
            <td class="text-xs space-x-2" dir="ltr">
              <a v-if="s.github_url" :href="s.github_url" target="_blank" rel="noopener" class="link">GitHub</a>
              <a v-if="s.live_demo_url" :href="s.live_demo_url" target="_blank" rel="noopener" class="link">Demo</a>
              <a v-if="s.link_url" :href="s.link_url" target="_blank" rel="noopener" class="link">Link</a>
              <a v-if="s.file_path" :href="`/api/admin/submissions/${s.id}/file`" target="_blank" rel="noopener" class="link">الملف</a>
            </td>
            <td class="text-xs text-ink-faint whitespace-nowrap">{{ s.submitted_at ? new Date(s.submitted_at).toLocaleDateString('ar-EG') : '—' }}</td>
            <td><StatusBadge :status="s.status" /></td>
            <td><button class="btn-outline !px-3 !py-1 !text-xs" @click="openReview(s)">مراجعة</button></td>
          </tr>
          <tr v-if="!loading && !data.data?.length"><td colspan="6" class="py-10 text-center text-ink-faint">لا تسليمات.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <Modal :open="!!active" :title="`مراجعة: ${active?.project?.title || ''}`" @close="active = null">
      <div v-if="active" class="space-y-4">
        <div class="rounded-lg bg-surface p-3.5 text-sm text-ink-soft">
          <p><b>الطالب:</b> {{ active.user?.name }}</p>
          <p v-if="active.notes" class="mt-1"><b>رسالته:</b> {{ active.notes }}</p>
          <div class="mt-2 flex flex-wrap gap-3 text-xs" dir="ltr">
            <a v-if="active.github_url" :href="active.github_url" target="_blank" class="link">GitHub ↗</a>
            <a v-if="active.live_demo_url" :href="active.live_demo_url" target="_blank" class="link">Live ↗</a>
            <a v-if="active.link_url" :href="active.link_url" target="_blank" class="link">Link ↗</a>
            <a v-if="active.file_path" :href="`/api/admin/submissions/${active.id}/file`" target="_blank" class="link">{{ active.file_name || 'الملف' }} ↗</a>
          </div>
        </div>
        <img v-if="active.file_mime?.startsWith('image/')" :src="`/api/admin/submissions/${active.id}/file`" class="max-h-72 rounded-lg border border-line" />
        <video v-else-if="active.file_mime?.startsWith('video/')" :src="`/api/admin/submissions/${active.id}/file`" controls class="max-h-72 w-full rounded-lg border border-line bg-black" />
        <FormField label="القرار">
          <select v-model="review.status" class="input">
            <option value="under_review">قيد المراجعة</option>
            <option value="approved">مقبول</option>
            <option value="needs_changes">يحتاج تعديلات</option>
            <option value="rejected">مرفوض</option>
          </select>
        </FormField>
        <FormField label="ملاحظات للطالب">
          <textarea v-model="review.feedback" rows="4" class="input" placeholder="ما الذي تحسّن؟ ما الذي يحتاج عملًا؟" />
        </FormField>
        <button class="btn-primary w-full" :disabled="busy" @click="saveReview">
          <Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ المراجعة
        </button>
      </div>
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
import { Loader2 } from '@lucide/vue';

const data = ref({});
const status = ref('');
const loading = ref(true);
const active = ref(null);
const review = ref({ status: 'under_review', feedback: '' });
const busy = ref(false);

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/submissions', { params: { page, status: status.value || undefined } });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

function openReview(s) {
  active.value = s;
  review.value = { status: s.status === 'submitted' ? 'under_review' : s.status, feedback: s.feedback || '' };
}

async function saveReview() {
  busy.value = true;
  try {
    await api.patch(`/admin/submissions/${active.value.id}`, review.value);
    toast('تم حفظ المراجعة وإشعار الطالب.');
    active.value = null;
    load(data.value.current_page || 1);
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(() => load());
</script>
