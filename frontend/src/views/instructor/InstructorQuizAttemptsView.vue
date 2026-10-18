<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-xl font-bold text-ink">تسليمات الاختبارات والمهام العملية</h1>
      <p class="mt-0.5 text-sm text-ink-muted">مراجعة نتائج الطلاب وتسليماتهم، استعراض الصور والملاحظات، والرد على الطلاب بالتعديلات المطلوبة.</p>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3">
      <input v-model="q" class="input !w-56" placeholder="بحث باسم أو إيميل الطالب…" />
      <select v-model="courseFilter" class="input !w-52" @change="load(1)">
        <option value="">كل الكورسات</option>
        <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
      </select>
      <select v-model="quizFilter" class="input !w-48" @change="load(1)">
        <option value="">كل الاختبارات والمهام</option>
        <option v-for="qz in quizzes" :key="qz.id" :value="qz.id">{{ qz.title }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
      <table class="table-base min-w-[750px]">
        <thead>
          <tr>
            <th>الطالب</th>
            <th>المهمة / الاختبار</th>
            <th>نوع التسليم</th>
            <th>النتيجة / الحالة</th>
            <th>ملاحظات المدرب</th>
            <th>التاريخ</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in data.data" :key="a.id" class="hover:bg-stone-50">
            <td>
              <p class="font-semibold text-ink">{{ a.user?.name }}</p>
              <p class="text-xs text-ink-faint" dir="ltr">{{ a.user?.email }}</p>
            </td>
            <td>
              <p class="text-sm font-medium text-ink">{{ a.quiz?.title }}</p>
              <p class="text-xs text-ink-muted">{{ a.quiz?.course?.title }}</p>
            </td>
            <td>
              <span v-if="a.submission_type === 'task'" class="badge-blue !bg-purple-100 !text-purple-800">
                مهمة عملية (صورة/رسالة)
              </span>
              <span v-else class="badge-gray">اختبار MCQ</span>
            </td>
            <td>
              <div class="space-y-1">
                <span v-if="a.status === 'approved'" class="badge-green">معتمد ومقبول</span>
                <span v-else-if="a.status === 'changes_requested'" class="badge-amber">مطلوب تعديلات</span>
                <span v-else class="badge-blue">قيد المراجعة</span>
                <p v-if="a.score !== null" class="text-xs font-bold text-ink" dir="ltr">{{ a.score }}%</p>
              </div>
            </td>
            <td>
              <p v-if="a.feedback" class="text-xs text-ink-muted max-w-[180px] truncate" :title="a.feedback">
                {{ a.feedback }}
              </p>
              <span v-else class="text-xs text-ink-faint">—</span>
            </td>
            <td class="text-xs text-ink-faint whitespace-nowrap">
              {{ a.completed_at ? new Date(a.completed_at).toLocaleDateString('ar-EG') : '—' }}
            </td>
            <td>
              <div class="flex items-center gap-2">
                <button class="btn-primary !py-1 !px-2.5 !text-xs font-semibold flex items-center gap-1"
                        @click="openReview(a)">
                  <Eye :size="13" /> فحص ورد
                </button>
                <button class="text-danger hover:underline text-xs" @click="remove(a)">حذف</button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && !data.data?.length">
            <td colspan="7" class="py-10 text-center text-ink-faint">لا توجد تسليمات حتى الآن.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :data="data" @change="load" />

    <!-- Review & Feedback Modal -->
    <Modal :open="!!activeAttempt" :title="`مراجعة تسليم: ${activeAttempt?.user?.name || ''}`" max-width="max-w-2xl" @close="activeAttempt = null">
      <div v-if="activeAttempt" class="space-y-4">
        <!-- Meta info -->
        <div class="rounded-xl bg-surface p-3.5 space-y-1 text-xs">
          <p><strong class="text-ink">المهمة:</strong> {{ activeAttempt.quiz?.title }}</p>
          <p><strong class="text-ink">الكورس:</strong> {{ activeAttempt.quiz?.course?.title }}</p>
          <p><strong class="text-ink">تاريخ التسليم:</strong> {{ activeAttempt.completed_at ? new Date(activeAttempt.completed_at).toLocaleString('ar-EG') : '—' }}</p>
        </div>

        <!-- Student Notes/Message -->
        <div v-if="activeAttempt.student_notes" class="space-y-1">
          <label class="label !text-xs">رسالة وشرح الطالب:</label>
          <div class="rounded-xl border border-line bg-stone-50 p-3.5 text-sm text-ink-soft whitespace-pre-line leading-relaxed">
            {{ activeAttempt.student_notes }}
          </div>
        </div>

        <!-- Student Image / Screenshot -->
        <div v-if="activeAttempt.image_url" class="space-y-1.5">
          <label class="label !text-xs flex items-center justify-between">
            <span>صورة / لقطة الشاشة المرفقة من الطالب:</span>
            <a :href="activeAttempt.image_url" target="_blank" rel="noopener" class="text-primary hover:underline text-[11px] inline-flex items-center gap-1">
              <ExternalLink :size="12" /> فتح الصورة بالحجم الكامل
            </a>
          </label>
          <div class="rounded-xl overflow-hidden border border-line bg-stone-900 max-h-80 flex items-center justify-center">
            <img :src="activeAttempt.image_url" alt="تسليم الطالب" class="max-h-80 w-auto object-contain" />
          </div>
        </div>

        <!-- Student Link -->
        <div v-if="activeAttempt.link_url" class="space-y-1">
          <label class="label !text-xs">رابط العمل (GitHub أو Demo):</label>
          <a :href="activeAttempt.link_url" target="_blank" rel="noopener" class="text-primary hover:underline text-xs flex items-center gap-1.5">
            <ExternalLink :size="13" /> {{ activeAttempt.link_url }}
          </a>
        </div>

        <!-- Review Form -->
        <form class="space-y-3 pt-3 border-t border-line" @submit.prevent="saveReview">
          <div class="grid grid-cols-2 gap-3">
            <FormField label="حالة التقييم">
              <select v-model="reviewForm.status" class="input">
                <option value="approved">مقبول ومعتمد ✅</option>
                <option value="changes_requested">مطلوب تعديلات ⚠️</option>
                <option value="submitted">قيد المراجعة ⏳</option>
              </select>
            </FormField>
            <FormField label="الدرجة %">
              <input v-model.number="reviewForm.score" type="number" min="0" max="100" class="input" dir="ltr" />
            </FormField>
          </div>

          <FormField label="ملاحظاتك وردك على الطالب (يظهر للطالب في حسابه وصفحة الفعاليات)">
            <textarea v-model="reviewForm.feedback" rows="3" class="input text-sm" placeholder="اكتب ملاحظاتك، توجيهات التعديل، أو تشجيعك للطالب…" />
          </FormField>

          <button class="btn-primary w-full !py-2.5 font-bold" :disabled="reviewBusy">
            <Loader2 v-if="reviewBusy" :size="16" class="animate-spin" /> حفظ وإرسال الرد للطالب
          </button>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import { Eye, ExternalLink, Loader2 } from '@lucide/vue';

const data = ref({});
const courses = ref([]);
const quizzes = ref([]);
const q = ref('');
const courseFilter = ref('');
const quizFilter = ref('');
const loading = ref(true);

const activeAttempt = ref(null);
const reviewBusy = ref(false);
const reviewForm = ref({ status: 'approved', score: 100, feedback: '' });

function openReview(attempt) {
  activeAttempt.value = attempt;
  reviewForm.value = {
    status: attempt.status || (attempt.passed ? 'approved' : 'submitted'),
    score: attempt.score ?? 100,
    feedback: attempt.feedback || '',
  };
}

async function saveReview() {
  reviewBusy.value = true;
  try {
    const { data: res } = await api.patch(`/instructor/quiz-attempts/${activeAttempt.value.id}/review`, reviewForm.value);
    toast('تم حفظ التقييم وإرسال الملاحظات بنجاح.');
    activeAttempt.value = null;
    load(data.value.current_page || 1);
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    reviewBusy.value = false;
  }
}

async function load(page = 1) {
  loading.value = true;
  try {
    const { data: res } = await api.get('/instructor/quiz-attempts', {
      params: {
        page,
        q: q.value || undefined,
        course_id: courseFilter.value || undefined,
        quiz_id: quizFilter.value || undefined,
      },
    });
    data.value = res;
  } finally {
    loading.value = false;
  }
}

async function loadFilters() {
  const [cRes, qRes] = await Promise.all([
    api.get('/instructor/courses', { params: { per_page: 100 } }),
    api.get('/instructor/quizzes', { params: { per_page: 100 } }),
  ]);
  courses.value = cRes.data.data || [];
  quizzes.value = qRes.data.data || [];
}

async function remove(a) {
  if (!confirm('حذف هذه المحاولة؟')) return;
  try {
    await api.delete(`/instructor/quiz-attempts/${a.id}`);
    toast('تم الحذف.');
    load(data.value.current_page || 1);
  } catch (e) {
    toast(unwrapError(e), 'error');
  }
}

let qTimeout;
watch(q, () => {
  if (qTimeout) clearTimeout(qTimeout);
  qTimeout = setTimeout(() => load(1), 300);
});

onMounted(() => {
  load();
  loadFilters();
});
</script>
