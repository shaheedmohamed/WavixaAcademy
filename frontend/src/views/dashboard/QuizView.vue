<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div v-if="loading"><div class="skeleton h-8 w-1/2 mb-4" /><div class="skeleton h-64" /></div>

    <template v-else-if="quiz">
      <div class="card p-6 bg-gradient-to-br from-white to-surface border border-line">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
          <span class="badge-blue">{{ isPracticalTask ? 'مهمة وتطبيق عملي 💻' : 'اختبار دراسي' }}</span>
          <span v-if="latestAttempt" class="badge" :class="latestAttempt.status === 'approved' ? 'badge-green' : (latestAttempt.status === 'changes_requested' ? 'badge-amber' : 'badge-gray')">
            {{ latestAttempt.status === 'approved' ? 'معتمد ومقبول ✅' : (latestAttempt.status === 'changes_requested' ? 'مطلوب تعديلات ⚠️' : 'تم التسليم قيد المراجعة ⏳') }}
          </span>
        </div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-ink">{{ quiz.title }}</h1>
        <p v-if="quiz.description" class="mt-2 text-sm text-ink-muted leading-relaxed whitespace-pre-line">{{ quiz.description }}</p>
        <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
          <span class="badge-blue">النجاح من {{ quiz.passing_score }}%</span>
          <span class="badge-gray">المحاولات المتبقية: {{ attemptsLeft }}</span>
          <span v-if="quiz.time_limit_minutes" class="badge-amber">{{ quiz.time_limit_minutes }} دقيقة</span>
          <span class="badge-gray">{{ quiz.questions?.length || 0 }} متطلبات</span>
        </div>
      </div>

      <!-- Instructor Feedback Card if available -->
      <div v-if="latestAttempt && (latestAttempt.feedback || latestAttempt.status)" class="card p-5 border-2"
           :class="latestAttempt.status === 'approved' ? 'border-emerald-300 bg-emerald-50/30' : (latestAttempt.status === 'changes_requested' ? 'border-amber-300 bg-amber-50/40' : 'border-line bg-surface/50')">
        <div class="flex items-start gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
               :class="latestAttempt.status === 'approved' ? 'bg-emerald-600 text-white' : (latestAttempt.status === 'changes_requested' ? 'bg-amber-600 text-white' : 'bg-primary text-white')">
            <MessageSquare :size="20" />
          </div>
          <div class="flex-1 space-y-1">
            <h3 class="text-sm font-bold text-ink">ملاحظات وتقييم المدرب:</h3>
            <p v-if="latestAttempt.feedback" class="text-sm text-ink-soft whitespace-pre-line leading-relaxed bg-white/80 p-3 rounded-lg border border-line/60">
              {{ latestAttempt.feedback }}
            </p>
            <p v-else class="text-xs text-ink-muted">تم استلام تسليمك للمهمة، والمدرب يقوم بمراجعته حالياً.</p>
            <p v-if="latestAttempt.reviewed_at" class="text-[11px] text-ink-faint">
              تم التقييم بتاريخ: {{ new Date(latestAttempt.reviewed_at).toLocaleDateString('ar-EG') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Result Card (MCQ or Post-Submit) -->
      <div v-if="result" class="card p-6 sm:p-8 text-center space-y-6">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl shadow-sm"
          :class="result.passed ? 'bg-emerald-100 text-emerald-700 ring-4 ring-emerald-50' : 'bg-red-100 text-danger ring-4 ring-red-50'">
          <BadgeCheck v-if="result.passed" :size="36" />
          <XCircle v-else :size="36" />
        </div>
        <div>
          <p v-if="!result.is_task" class="text-3xl font-extrabold text-ink tracking-tight">{{ result.score }}%</p>
          <p class="mt-1.5 text-base font-bold" :class="result.passed ? 'text-emerald-700' : 'text-danger'">
            {{ result.message || (result.passed ? 'مبارك! تم استلام تسليمك بنجاح 🎓' : 'لم تصل لدرجة النجاح المطلوبة بعد.') }}
          </p>
          <p v-if="result.is_task" class="text-xs text-ink-muted mt-2">
            تم تسجيل تسليمك بنجاح، وستظهر ملاحظات وتوجيهات المدرب في هذه الصفحة وفي صفحة الأنشطة فور مراجعتها.
          </p>
        </div>

        <div class="mt-6 flex flex-wrap justify-center gap-3 pt-4 border-t border-line/60">
          <button v-if="attemptsLeft > 0" class="btn-primary !px-6" @click="reset">تسليم جديد أو تحديث</button>
          <button class="btn-outline !px-6" @click="$router.back()">العودة للدرس</button>
        </div>
      </div>

      <!-- Main Submission Section: Practical Task Mode OR MCQ Mode -->
      <div v-else class="space-y-6">
        <!-- Display All Questions/Requirements (Images, Descriptions) -->
        <div v-for="(q, i) in quiz.questions" :key="q.id" class="card p-5 sm:p-6 space-y-4 border border-line">
          <div class="flex items-start gap-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary text-white text-xs font-bold">{{ i + 1 }}</span>
            <div class="flex-1">
              <h2 class="text-base font-bold text-ink leading-snug">{{ q.question }}</h2>
            </div>
          </div>

          <!-- Question Image -->
          <div v-if="q.image_url" class="rounded-xl overflow-hidden border border-line bg-stone-900 max-w-2xl mx-auto shadow-sm">
            <img :src="q.image_url" :alt="q.question" class="max-h-96 w-auto mx-auto object-contain" loading="lazy" />
          </div>

          <!-- Question Description / Notes -->
          <div v-if="q.description" class="rounded-xl bg-surface p-3.5 text-sm text-ink-soft leading-relaxed whitespace-pre-line border border-line/60">
            {{ q.description }}
          </div>

          <!-- If MCQ options exist and user didn't switch to task mode -->
          <div v-if="!isPracticalTask && q.options?.length" class="space-y-2.5 pt-2">
            <label v-for="o in q.options" :key="o.id"
              class="flex cursor-pointer items-center gap-3.5 rounded-xl border p-3.5 text-sm transition-all duration-150 select-none"
              :class="answers[q.id] === o.id
                ? 'border-primary bg-primary-faint ring-1 ring-primary font-semibold text-primary-dark shadow-xs'
                : 'border-line hover:border-ink-faint hover:bg-stone-50 text-ink'">
              <input type="radio" :name="'q' + q.id" :value="o.id" v-model.number="answers[q.id]" class="text-primary focus:ring-primary/30 h-4 w-4" />
              <span class="flex-1">{{ o.text }}</span>
            </label>
          </div>
        </div>

        <!-- Mode Toggle Pill if test has options -->
        <div v-if="hasOptions" class="flex items-center justify-between px-2 text-xs">
          <span class="text-ink-muted">طريقة التسليم:</span>
          <div class="flex gap-2">
            <button class="px-2.5 py-1 rounded-lg border text-xs font-semibold"
                    :class="!isPracticalTask ? 'bg-primary text-white border-primary' : 'bg-white text-ink-soft border-line'"
                    @click="isPracticalTask = false">
              إجابة بالخيارات
            </button>
            <button class="px-2.5 py-1 rounded-lg border text-xs font-semibold"
                    :class="isPracticalTask ? 'bg-primary text-white border-primary' : 'bg-white text-ink-soft border-line'"
                    @click="isPracticalTask = true">
              تسليم مهمة عملية (رفع صورة / رسالة)
            </button>
          </div>
        </div>

        <!-- Practical Task Submission Box -->
        <div v-if="isPracticalTask" class="card p-6 border-2 border-primary/30 bg-white space-y-4 shadow-sm">
          <div class="border-b border-line pb-3">
            <h3 class="text-base font-bold text-ink flex items-center gap-2">
              <Upload :size="18" class="text-primary" /> تسليم حل المهمة والتطبيق العملي
            </h3>
            <p class="text-xs text-ink-muted mt-1">
              اكتب رسالتك وتفاصيل حلك للمدرب، وارفع لقطة شاشة أو صورة من تطبيقك للموقع للتأكيد.
            </p>
          </div>

          <!-- Student Notes / Message -->
          <FormField label="رسالتك أو شرحك للمدرب" hint="اكتب ما قمت بإنجازه، التقنيات التي استخدمتها، أو أي استفسار">
            <textarea v-model="taskForm.student_notes" rows="3" class="input" placeholder="مثال: قمت بتصميم وتطوير الموقع بالكامل كما هو موضح بالصورة، واستخدمت HTML و CSS..." />
          </FormField>

          <!-- Image Upload / Screenshot -->
          <div class="space-y-2">
            <label class="label">صورة أو لقطة شاشة لحلك وتطبيقك</label>
            <div class="flex flex-wrap items-center gap-3">
              <label class="btn-outline shrink-0 cursor-pointer !py-2 !px-4 !text-xs flex items-center gap-2">
                <Upload :size="15" /> رفع لقطة شاشة من جهازك
                <input type="file" class="hidden" accept="image/*" @change="uploadTaskImage" />
              </label>
              <input v-model="taskForm.image_url" class="input flex-1 !py-2 !text-xs" dir="ltr" placeholder="رابط مباشر للصورة أو ارفع من جهازك..." />
            </div>

            <!-- Preview uploaded image -->
            <div v-if="taskForm.image_url" class="relative group rounded-xl overflow-hidden border border-line bg-stone-900 max-w-md mt-2">
              <img :src="taskForm.image_url" alt="معاينة تسليم الطالب" class="max-h-64 w-auto mx-auto object-contain" />
              <button class="absolute top-2 left-2 bg-black/70 hover:bg-red-600 text-white rounded-md p-1.5 transition-colors"
                      title="إزالة الصورة" @click="taskForm.image_url = ''">
                <Trash2 :size="14" />
              </button>
            </div>
          </div>

          <!-- Optional Link (Live Demo or GitHub) -->
          <FormField label="رابط الموقع المباشر أو GitHub (اختياري)">
            <input v-model="taskForm.link_url" class="input" dir="ltr" placeholder="https://..." />
          </FormField>

          <!-- Submit Button -->
          <button class="btn-primary w-full !py-3.5 text-sm font-bold shadow-md"
                  :disabled="busy || attemptsLeft === 0"
                  @click="submitTask">
            <Loader2 v-if="busy" :size="16" class="animate-spin" />
            <span v-if="latestAttempt">تحديث وإعادة إرسال المهمة للمدرب</span>
            <span v-else>تأكيد وإرسال المهمة للمدرب</span>
          </button>
        </div>

        <!-- MCQ Submit Button -->
        <button v-else
                class="btn-primary w-full !py-3.5 text-sm font-bold shadow-md"
                :disabled="busy || attemptsLeft === 0"
                @click="submitMCQ">
          <Loader2 v-if="busy" :size="16" class="animate-spin" />
          {{ attemptsLeft === 0 ? 'استنفدت جميع المحاولات' : 'إرسال الإجابات وتصحيح الاختبار' }}
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import {
  BadgeCheck, XCircle, Loader2, Upload, Trash2, MessageSquare
} from '@lucide/vue';

const route = useRoute();
const quiz = ref(null);
const answers = ref({});
const result = ref(null);
const attemptsLeft = ref(0);
const latestAttempt = ref(null);
const loading = ref(true);
const busy = ref(false);

const isPracticalTask = ref(false);
const taskForm = ref({
  student_notes: '',
  image_url: '',
  link_url: '',
});

const hasOptions = computed(() => {
  return (quiz.value?.questions || []).some((q) => (q.options || []).length > 0);
});

function reset() {
  result.value = null;
  answers.value = {};
}

async function uploadTaskImage(e) {
  const file = e.target.files?.[0];
  e.target.value = '';
  if (!file) return;

  busy.value = true;
  const fd = new FormData();
  fd.append('file', file);
  fd.append('folder', 'task-submissions');
  try {
    const { data } = await api.post('/uploads', fd);
    taskForm.value.image_url = data.url;
    toast('تم رفع لقطة الشاشة بنجاح.');
  } catch (err) {
    toast(unwrapError(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function submitTask() {
  if (!taskForm.value.student_notes && !taskForm.value.image_url && !taskForm.value.link_url) {
    toast('يرجى كتابة رسالة أو رفع صورة لحلك قبل الإرسال.', 'error');
    return;
  }

  busy.value = true;
  try {
    const payload = {
      submission_type: 'task',
      student_notes: taskForm.value.student_notes,
      image_url: taskForm.value.image_url,
      link_url: taskForm.value.link_url,
    };
    const { data } = await api.post(`/quizzes/${quiz.value.id}/attempts`, payload);
    result.value = data;
    latestAttempt.value = data.attempt;
    attemptsLeft.value = Math.max(0, attemptsLeft.value - 1);
    toast(data.message || 'تم تسليم المهمة بنجاح.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

async function submitMCQ() {
  if (Object.keys(answers.value).length < (quiz.value.questions?.length || 0)) {
    if (!confirm('لم تُجب على جميع الأسئلة بعد. هل تريد الإرسال على أي حال؟')) return;
  }

  busy.value = true;
  try {
    const { data } = await api.post(`/quizzes/${quiz.value.id}/attempts`, { answers: answers.value });
    result.value = data;
    latestAttempt.value = data.attempt;
    attemptsLeft.value = data.attempts_left;
    toast(data.passed ? 'مبارك! اجتزت الاختبار بنجاح.' : 'تم تصحيح الاختبار.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get(`/quizzes/${route.params.id}`);
    quiz.value = data.quiz;
    attemptsLeft.value = data.attempts_left;
    latestAttempt.value = data.latest_attempt || null;

    if (latestAttempt.value) {
      taskForm.value.student_notes = latestAttempt.value.student_notes || '';
      taskForm.value.image_url = latestAttempt.value.image_url || '';
      taskForm.value.link_url = latestAttempt.value.link_url || '';
    }

    // If quiz questions have images and no realistic choices, default to Practical Task
    const questions = quiz.value.questions || [];
    const hasImage = questions.some((q) => !!q.image_url);
    const optionsAreDefault = questions.every((q) => {
      const texts = (q.options || []).map((o) => o.text.trim());
      return texts.length === 0 || texts.some((t) => t.includes('الخيار الأول') || t.includes('الخيار الثاني'));
    });

    if (hasImage || optionsAreDefault || questions.length === 0) {
      isPracticalTask.value = true;
    }
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>
