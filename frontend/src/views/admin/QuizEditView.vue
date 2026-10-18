<template>
  <div v-if="loading"><div class="skeleton h-8 w-1/3 mb-4" /><div class="skeleton h-96" /></div>

  <div v-else-if="quiz" class="space-y-6 max-w-3xl">
    <div class="flex items-center justify-between">
      <div>
        <nav class="mb-1 text-xs text-ink-faint"><RouterLink to="/admin/quizzes" class="hover:text-ink">الاختبارات</RouterLink> / {{ quiz.title }}</nav>
        <h1 class="text-xl font-bold text-ink">{{ quiz.title }}</h1>
      </div>
      <StatusBadge :status="quiz.status" />
    </div>

    <div class="card space-y-4 p-6">
      <h2 class="text-sm font-bold text-ink">الإعدادات</h2>
      <FormField label="العنوان"><input v-model="quiz.title" class="input" /></FormField>
      <FormField label="الوصف"><textarea v-model="quiz.description" rows="2" class="input" /></FormField>
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
        <h2 class="text-sm font-bold text-ink">الأسئلة ({{ quiz.questions.length }})</h2>
        <button class="btn-outline !text-xs" @click="addQuestion"><Plus :size="14" /> سؤال</button>
      </div>

      <div v-for="(q, qi) in quiz.questions" :key="q.id || qi" class="card p-5 space-y-3">
        <div class="flex items-start gap-3">
          <span class="mt-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-ink text-white text-xs font-bold">{{ qi + 1 }}</span>
          <textarea v-model="q.question" rows="2" class="input flex-1" placeholder="نص السؤال…" />
          <button class="btn-ghost !p-1.5 text-danger" @click="quiz.questions.splice(qi, 1)"><Trash2 :size="15" /></button>
        </div>
        <div class="space-y-2 ps-10">
          <div v-for="(o, oi) in q.options" :key="o.id || oi" class="flex items-center gap-2">
            <input type="radio" :name="'correct' + qi" :checked="o.is_correct" @change="setCorrect(q, oi)" class="text-emerald focus:ring-emerald/30" title="الإجابة الصحيحة" />
            <input v-model="o.text" class="input flex-1 !py-2 !text-sm" :placeholder="'خيار ' + (oi + 1)" />
            <button v-if="q.options.length > 2" class="btn-ghost !p-1 text-danger" @click="q.options.splice(oi, 1)"><X :size="14" /></button>
          </div>
          <button class="btn-ghost !py-1 !text-xs text-primary" @click="q.options.push({ text: '', is_correct: false })">+ خيار</button>
        </div>
        <FormField label="شرح (يظهر بعد الإجابة)">
          <input v-model="q.explanation" class="input !py-2 !text-sm" />
        </FormField>
      </div>

      <button class="btn-primary w-full !py-3" :disabled="busy" @click="save">
        <Loader2 v-if="busy" :size="16" class="animate-spin" /> حفظ الاختبار
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
import { Plus, Trash2, X, Loader2 } from '@lucide/vue';

const route = useRoute();
const quiz = ref(null);
const loading = ref(true);
const busy = ref(false);

function addQuestion() {
  quiz.value.questions.push({
    question: '', points: 1,
    options: [{ text: '', is_correct: true }, { text: '', is_correct: false }],
  });
}

function setCorrect(q, index) {
  q.options.forEach((o, i) => (o.is_correct = i === index));
}

async function save() {
  busy.value = true;
  try {
    const { data } = await api.patch(`/admin/quizzes/${quiz.value.id}`, {
      title: quiz.value.title,
      description: quiz.value.description,
      passing_score: quiz.value.passing_score,
      max_attempts: quiz.value.max_attempts,
      status: quiz.value.status,
      module_id: quiz.value.module_id,
      lesson_id: quiz.value.lesson_id,
      questions: quiz.value.questions.map((q, i) => ({
        id: q.id, question: q.question, explanation: q.explanation, points: q.points || 1,
        options: q.options.map((o, j) => ({ id: o.id, text: o.text, is_correct: !!o.is_correct })),
      })),
    });
    quiz.value = data.quiz;
    toast('تم حفظ الاختبار.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/admin/quizzes/${route.params.id}`);
    quiz.value = data.quiz;
  } finally {
    loading.value = false;
  }
});
</script>
