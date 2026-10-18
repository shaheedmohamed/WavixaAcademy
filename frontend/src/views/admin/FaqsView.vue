<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-ink">الأسئلة الشائعة</h1>
        <p class="mt-0.5 text-sm text-ink-muted">تظهر في صفحة FAQ العامة والرئيسية.</p>
      </div>
      <button class="btn-primary" @click="openForm()"><Plus :size="16" /> سؤال جديد</button>
    </div>

    <div class="space-y-2.5">
      <div v-for="f in data.data" :key="f.id" class="card flex items-start gap-4 p-4">
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-ink">{{ f.question }}</p>
          <p class="mt-1 line-clamp-2 text-xs text-ink-muted">{{ f.answer }}</p>
        </div>
        <span class="badge" :class="f.is_published ? 'badge-green' : 'badge-gray'">{{ f.is_published ? 'منشور' : 'مخفي' }}</span>
        <div class="flex gap-1">
          <button class="btn-ghost !p-1.5" @click="openForm(f)"><Pencil :size="14" /></button>
          <button class="btn-ghost !p-1.5 text-danger" @click="remove(f)"><Trash2 :size="14" /></button>
        </div>
      </div>
      <p v-if="!loading && !data.data?.length" class="card py-10 text-center text-sm text-ink-faint">لا أسئلة.</p>
    </div>

    <Modal :open="formOpen" :title="form.id ? 'تعديل سؤال' : 'سؤال جديد'" @close="formOpen = false">
      <form class="space-y-4" @submit.prevent="save">
        <FormField label="السؤال" required><input v-model="form.question" class="input" required /></FormField>
        <FormField label="الإجابة" required><textarea v-model="form.answer" rows="4" class="input" required /></FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="التصنيف"><input v-model="form.category" class="input" placeholder="عام" /></FormField>
          <FormField label="الترتيب"><input v-model.number="form.sort_order" type="number" min="0" class="input" dir="ltr" /></FormField>
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-soft">
          <input v-model="form.is_published" type="checkbox" class="rounded border-line text-primary" /> منشور
        </label>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ</button>
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
import { Plus, Pencil, Trash2, Loader2 } from '@lucide/vue';

const data = ref({});
const loading = ref(true);
const formOpen = ref(false);
const busy = ref(false);
const form = ref({});

async function load() {
  loading.value = true;
  try {
    const { data: res } = await api.get('/admin/faqs');
    data.value = res;
  } finally {
    loading.value = false;
  }
}

function openForm(f = null) {
  form.value = f ? { ...f } : { question: '', answer: '', category: '', sort_order: 0, is_published: true };
  formOpen.value = true;
}

async function save() {
  busy.value = true;
  try {
    if (form.value.id) await api.patch(`/admin/faqs/${form.value.id}`, form.value);
    else await api.post('/admin/faqs', form.value);
    toast('تم الحفظ.');
    formOpen.value = false;
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

async function remove(f) {
  if (!confirm('حذف السؤال؟')) return;
  await api.delete(`/admin/faqs/${f.id}`);
  load();
}

onMounted(load);
</script>
