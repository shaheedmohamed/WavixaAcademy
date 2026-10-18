<template>
  <div v-if="loading"><div class="skeleton h-8 w-1/3 mb-4" /><div class="skeleton h-96" /></div>

  <div v-else-if="quiz" class="space-y-6 max-w-3xl">
    <div class="flex items-center justify-between">
      <div>
        <nav class="mb-1 text-xs text-ink-faint"><RouterLink to="/instructor/quizzes" class="hover:text-ink">الاختبارات</RouterLink> / {{ quiz.title }}</nav>
        <h1 class="text-xl font-bold text-ink">{{ quiz.title }}</h1>
      </div>
      <StatusBadge :status="quiz.status" />
    </div>

    <div class="card space-y-4 p-6">
      <h2 class="text-sm font-bold text-ink">إعدادات الاختبار</h2>
      <FormField label="عنوان الاختبار"><input v-model="quiz.title" class="input" /></FormField>
      <FormField label="وصف الاختبار"><textarea v-model="quiz.description" rows="2" class="input" /></FormField>
      <div class="grid grid-cols-3 gap-3">
        <FormField label="درجة النجاح %"><input v-model.number="quiz.passing_score" type="number" min="1" max="100" class="input" dir="ltr" /></FormField>
        <FormField label="المحاولات"><input v-model.number="quiz.max_attempts" type="number" min="1" max="10" class="input" dir="ltr" /></FormField>
        <FormField label="الحالة">
          <select v-model="quiz.status" class="input"><option value="draft">مسودة</option><option value="published">منشور</option></select>
        </FormField>
      </div>
    </div>

    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold text-ink">أسئلة الاختبار ({{ quiz.questions.length }})</h2>
          <p class="text-xs text-ink-muted mt-0.5">يمكنك إضافة نصوص، صور توضيحية، وأوصاف تفصيلية لكل سؤال.</p>
        </div>
        <button class="btn-outline !text-xs flex items-center gap-1.5" @click="addQuestion">
          <Plus :size="14" /> إضافة سؤال جديد
        </button>
      </div>

      <div v-for="(q, qi) in quiz.questions" :key="q.id || qi" class="card p-5 space-y-4 border border-line">
        <!-- Question Header & Main Text -->
        <div class="flex items-start gap-3">
          <span class="mt-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-ink text-white text-xs font-bold">{{ qi + 1 }}</span>
          <div class="flex-1 space-y-2">
            <textarea v-model="q.question" rows="2" class="input" placeholder="نص السؤال الرئيسي أو رأس المسألة…" />
          </div>
          <button class="btn-ghost !p-1.5 text-danger shrink-0" title="حذف هذا السؤال" @click="quiz.questions.splice(qi, 1)">
            <Trash2 :size="16" />
          </button>
        </div>

        <!-- Question Image Upload & Preview -->
        <div class="rounded-xl border border-line/80 bg-surface/50 p-3.5 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-ink flex items-center gap-1.5">
              <ImageIcon :size="14" class="text-primary" /> صورة توضيحية للسؤال (اختياري)
            </span>
            <label class="btn-outline shrink-0 cursor-pointer !py-1 !px-2.5 !text-xs flex items-center gap-1.5">
              <Upload :size="13" /> رفع صورة من الجهاز
              <input type="file" class="hidden" accept="image/*" @change="(e) => uploadQuestionImage(e, q)" />
            </label>
          </div>

          <!-- Direct URL or uploaded path -->
          <input v-model="q.image_url" class="input !py-1.5 !text-xs" dir="ltr" placeholder="رابط الصورة المباشر أو ارفع من الجهاز..." />

          <!-- Image Preview -->
          <div v-if="q.image_url" class="relative group rounded-lg overflow-hidden border border-line bg-stone-900 max-w-md">
            <img :src="q.image_url" alt="معاينة صورة السؤال" class="max-h-60 w-auto mx-auto object-contain" />
            <button class="absolute top-2 left-2 bg-black/70 hover:bg-red-600 text-white rounded-md p-1.5 transition-colors"
                    title="إزالة الصورة" @click="q.image_url = null">
              <Trash2 :size="14" />
            </button>
          </div>
        </div>

        <!-- Description / Note under Image -->
        <FormField label="وصف أو شرح توضيحي أسفل الصورة والسؤال (اختياري)" hint="يظهر للطالب تحت الصورة لتقديم معلومات إضافية أو كود أو إرشادات">
          <textarea v-model="q.description" rows="2" class="input !text-sm" placeholder="اكتب هنا أي تفاصيل، معطيات المسألة، أو إرشادات للطالب..." />
        </FormField>

        <!-- Options -->
        <div class="space-y-2 pt-2 border-t border-line/60">
          <span class="text-xs font-bold text-ink-muted block mb-1">خيارات الإجابة (حدد الدائرة الخضراء للخيار الصحيح):</span>
          <div v-for="(o, oi) in q.options" :key="o.id || oi" class="flex items-center gap-2">
            <input type="radio" :name="'correct' + qi" :checked="o.is_correct" @change="setCorrect(q, oi)" class="text-emerald focus:ring-emerald/30 h-4 w-4" title="الإجابة الصحيحة" />
            <input v-model="o.text" class="input flex-1 !py-2 !text-sm" :placeholder="'خيار ' + (oi + 1)" />
            <button v-if="q.options.length > 2" class="btn-ghost !p-1 text-danger" @click="q.options.splice(oi, 1)"><X :size="14" /></button>
          </div>
          <button class="btn-ghost !py-1 !text-xs text-primary font-semibold" @click="q.options.push({ text: '', is_correct: false })">+ خيار إضافي</button>
        </div>

        <!-- Explanation after answer -->
        <FormField label="التغذية الراجعة والشرح (يظهر للطالب بعد إرسال الإجابة)" class="pt-2">
          <input v-model="q.explanation" class="input !py-2 !text-sm" placeholder="مثال: الخيار الصحيح هو كذا لأن..." />
        </FormField>
      </div>

      <button class="btn-primary w-full !py-3 shadow-md font-bold" :disabled="busy" @click="save">
        <Loader2 v-if="busy" :size="16" class="animate-spin" /> حفظ الاختبار والأسئلة
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { Plus, Trash2, X, Loader2, Upload, ImageIcon } from '@lucide/vue';

const route = useRoute();
const quiz = ref(null);
const loading = ref(true);
const busy = ref(false);

function addQuestion() {
  quiz.value.questions.push({
    question: '',
    image_url: null,
    description: '',
    points: 1,
    options: [{ text: '', is_correct: true }, { text: '', is_correct: false }],
  });
}

function setCorrect(q, index) {
  q.options.forEach((o, i) => (o.is_correct = i === index));
}

async function uploadQuestionImage(e, q) {
  const file = e.target.files?.[0];
  e.target.value = '';
  if (!file) return;
  const fd = new FormData();
  fd.append('file', file);
  fd.append('folder', 'quizzes');
  try {
    const { data } = await api.post('/instructor/uploads', fd);
    q.image_url = data.url;
    toast('تم رفع صورة السؤال بنجاح.');
  } catch (err) {
    toast(unwrapError(err), 'error');
  }
}

async function save() {
  busy.value = true;
  try {
    const { data } = await api.patch(`/instructor/quizzes/${quiz.value.id}`, {
      title: quiz.value.title,
      description: quiz.value.description,
      passing_score: quiz.value.passing_score,
      max_attempts: quiz.value.max_attempts,
      status: quiz.value.status,
      questions: quiz.value.questions.map((q, i) => ({
        id: q.id,
        question: q.question,
        image_url: q.image_url || null,
        description: q.description || null,
        explanation: q.explanation,
        points: q.points || 1,
        options: q.options.map((o, j) => ({ id: o.id, text: o.text, is_correct: !!o.is_correct })),
      })),
    });
    quiz.value = data.quiz;
    toast('تم حفظ الاختبار بنجاح.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/instructor/quizzes/${route.params.id}`);
    quiz.value = data.quiz;
  } finally {
    loading.value = false;
  }
});
</script>
