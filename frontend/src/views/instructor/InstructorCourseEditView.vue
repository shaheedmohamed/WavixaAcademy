<template>
  <div v-if="loading"><div class="skeleton h-8 w-1/3 mb-4" /><div class="skeleton h-96" /></div>

  <div v-else-if="course" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <nav class="mb-1 flex items-center gap-2 text-xs text-ink-faint">
          <RouterLink to="/instructor/courses" class="hover:text-ink">كورساتي</RouterLink> / <span>{{ course.title }}</span>
        </nav>
        <h1 class="text-xl font-bold text-ink">{{ course.title }}</h1>
      </div>
      <StatusBadge :status="course.status" />
    </div>

    <div class="flex gap-1 border-b border-line">
      <button v-for="t in tabs" :key="t.id" class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="tab === t.id ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink'"
        @click="tab = t.id">
        {{ t.label }}
      </button>
    </div>

    <!-- Content tab -->
    <div v-if="tab === 'content'" class="space-y-4">
      <div class="flex justify-end gap-2">
        <button class="btn-primary !text-xs" @click="openDirectLesson()"><Plus :size="14" /> درس مباشر</button>
        <button class="btn-primary !text-xs" @click="openModule()"><Plus :size="14" /> وحدة جديدة</button>
      </div>

      <div v-for="(mod, i) in course.modules" :key="mod.id" class="card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-line bg-surface px-5 py-3.5">
          <div class="flex items-center gap-3 min-w-0">
            <h3 class="text-sm font-bold text-ink truncate">الوحدة {{ i + 1 }}: {{ mod.title }}</h3>
            <StatusBadge :status="mod.status" />
          </div>
          <div class="flex gap-1 shrink-0">
            <button class="btn-ghost !p-1.5" @click="openLesson(mod)"><Plus :size="15" /></button>
            <button class="btn-ghost !p-1.5" @click="openModule(mod)"><Pencil :size="14" /></button>
            <button class="btn-ghost !p-1.5 text-danger" @click="removeModule(mod)"><Trash2 :size="14" /></button>
          </div>
        </div>
        <ul class="divide-y divide-line/60">
          <li v-for="lesson in mod.lessons" :key="lesson.id" class="flex flex-wrap items-center gap-3 px-5 py-3">
            <PlayCircle :size="15" class="shrink-0 text-ink-faint" />
            <span class="flex-1 min-w-0 text-sm text-ink truncate">{{ lesson.title }}</span>
            <span v-if="lesson.is_preview" class="badge-green">معاينة</span>
            <span v-if="!lesson.is_published" class="badge-gray">مسودة</span>
            <span v-if="lesson.quiz" class="badge-blue !bg-purple-100 !text-purple-800">اختبار: {{ lesson.quiz.title }}</span>
            <span v-if="lesson.video_asset" class="badge-blue">{{ { pending: 'قيد المعالجة', ready: 'جاهز', failed: 'فشل' }[lesson.video_asset.status] || lesson.video_asset.status }}</span>
            <span v-else class="badge-gray">بدون فيديو</span>
            <div class="flex items-center gap-1">
              <button v-if="lesson.quiz" class="btn-ghost !p-1.5 text-purple-700" title="تعديل اختبار الدرس" @click="$router.push(`/instructor/quizzes/${lesson.quiz.id}`)"><HelpCircle :size="14" /> اختبار</button>
              <button v-else class="btn-ghost !p-1.5 text-purple-700" title="إضافة اختبار لهذا الدرس" @click="openCreateQuiz(lesson)"><PlusCircle :size="14" /> اختبار</button>
              <button v-if="lesson.video_asset?.status === 'ready'" class="btn-ghost !p-1.5 text-emerald" @click="watchLesson = lesson"><Play :size="14" /> مشاهدة</button>
              <button class="btn-ghost !p-1.5 text-primary" @click="openImport(lesson)"><Video :size="14" /> فيديو</button>
              <button class="btn-ghost !p-1.5" @click="openLesson(mod, lesson)"><Pencil :size="14" /></button>
              <button class="btn-ghost !p-1.5 text-danger" @click="removeLesson(lesson)"><Trash2 :size="14" /></button>
            </div>
          </li>
          <li v-if="!mod.lessons?.length" class="px-5 py-4 text-center text-xs text-ink-faint">لا دروس — أضف أول درس.</li>
        </ul>
      </div>
      <p v-if="!course.modules?.length" class="card py-10 text-center text-sm text-ink-faint">ابدأ بإضافة أول درس مباشر أو وحدة.</p>
    </div>

    <!-- Settings tab -->
    <form v-if="tab === 'settings'" class="card max-w-3xl space-y-5 p-6" @submit.prevent="saveCourse">
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="العنوان" required><input v-model="course.title" class="input" /></FormField>
        <FormField label="الـ slug" hint="اتركه فارغًا للتوليد التلقائي"><input v-model="course.slug" class="input" dir="ltr" /></FormField>
      </div>
      <FormField label="وصف مختصر"><textarea v-model="course.short_description" rows="2" class="input" /></FormField>
      <FormField label="الوصف الكامل"><textarea v-model="course.description" rows="6" class="input" /></FormField>
      <div class="grid gap-4 sm:grid-cols-3">
        <FormField label="السعر"><input v-model.number="course.price" type="number" min="0" step="0.01" class="input" dir="ltr" /></FormField>
        <FormField label="العملة"><input v-model="course.currency" class="input" dir="ltr" /></FormField>
        <FormField label="المستوى">
          <select v-model="course.level" class="input">
            <option value="all_levels">كل المستويات</option>
            <option value="beginner">مبتدئ</option>
            <option value="intermediate">متوسط</option>
            <option value="advanced">متقدم</option>
          </select>
        </FormField>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="صورة مصغرة (thumbnail)">
          <div class="flex gap-2">
            <input v-model="course.thumbnail" class="input" dir="ltr" placeholder="/storage/..." />
            <label class="btn-outline shrink-0 cursor-pointer !text-xs"><Upload :size="14" /> رفع<input type="file" class="hidden" accept="image/*" @change="(e) => uploadImage(e, 'thumbnail')" /></label>
          </div>
        </FormField>
        <FormField label="صورة الغلاف (cover)">
          <div class="flex gap-2">
            <input v-model="course.cover" class="input" dir="ltr" placeholder="/storage/..." />
            <label class="btn-outline shrink-0 cursor-pointer !text-xs"><Upload :size="14" /> رفع<input type="file" class="hidden" accept="image/*" @change="(e) => uploadImage(e, 'cover')" /></label>
          </div>
        </FormField>
      </div>
      <FormField label="الحالة">
        <select v-model="course.status" class="input">
          <option value="draft">مسودة</option>
          <option value="published">منشور</option>
          <option value="archived">مؤرشف</option>
        </select>
      </FormField>
      <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input v-model="course.is_featured" type="checkbox" class="rounded border-line text-primary focus:ring-primary/30" />
        كورس مميز (يظهر في الصفحة الرئيسية)
      </label>
      <button class="btn-primary" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ التغييرات</button>
    </form>

    <!-- Certificate tab -->
    <div v-if="tab === 'certificate'" class="space-y-6">
      <div class="card max-w-3xl space-y-4 p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-base font-bold text-ink">نظام شهادات الإتمام المعتمدة من Wavixa</h2>
            <p class="mt-1 text-xs text-ink-muted">
              فعّل إصدار الشهادات التلقائي للطلاب بمجرد إكمال دراسة الكورس واجتياز المتطلبات.
            </p>
          </div>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
            :class="course.certificate_enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'">
            {{ course.certificate_enabled ? 'الشهادة مفعلة' : 'الشهادة معطلة' }}
          </span>
        </div>

        <div class="pt-2 border-t border-line space-y-4">
          <label class="flex items-center gap-3 text-sm font-medium text-ink cursor-pointer select-none">
            <input v-model="course.certificate_enabled" type="checkbox" class="h-4 w-4 rounded border-line text-primary focus:ring-primary/30" />
            <span>تفعيل منح شهادة إتمام للطلاب عند إنهاء الكورس (100%)</span>
          </label>

          <div class="grid gap-4 sm:grid-cols-2">
            <FormField label="لغة الشهادة الافتراضية للكورس">
              <select v-model="course.certificate_language" class="input">
                <option value="both">إتاحة اللغتين (عربي + English) وحرية الاختيار للطالب</option>
                <option value="ar">اللغة العربية فقط (Arabic Only)</option>
                <option value="en">اللغة الإنجليزية فقط (English Only)</option>
              </select>
            </FormField>

            <FormField label="عنوان الشهادة المخصص (اختياري)" hint="اتركه فارغًا لاستخدام عنوان الكورس الافتراضي">
              <input v-model="course.certificate_title" class="input" placeholder="مثال: شهادة إتمام واحتراف تطوير الواجهات" />
            </FormField>
          </div>

          <button class="btn-primary !px-5" :disabled="busy" @click="saveCourse">
            <Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ إعدادات الشهادة
          </button>
        </div>
      </div>

      <!-- Live Certificate Preview Card -->
      <div class="card p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
          <div>
            <div class="flex items-center gap-2">
              <Award :size="18" class="text-[#C5A059]" />
              <h3 class="text-base font-bold text-ink">معاينة حية لشكل الشهادة المعتمدة من Wavixa</h3>
            </div>
            <p class="text-xs text-ink-muted mt-0.5">
              هذا هو التصميم الملكي الفاخر باللغتين العربية والإنجليزية. يمكنك تبديل اللغة وتجربة التحميل المباشر كـ PDF أو صورة.
            </p>
          </div>
          <span class="badge-blue">معاينة مباشرة</span>
        </div>

        <WavixaCertificate
          :is-preview="true"
          :student-name="'أحمد محمد إبراهيم'"
          :course-title="course.title"
          :certificate-title="course.certificate_title"
          :instructor-name="course.instructor?.name || auth.user?.name"
          :certificate-number="'WXA-' + new Date().getFullYear() + '-' + String(course.id).padStart(6, '0')"
          :default-lang="course.certificate_language === 'en' ? 'en' : 'ar'"
        />
      </div>
    </div>

    <!-- Module modal -->
    <Modal :open="moduleOpen" :title="moduleForm.id ? 'تعديل الوحدة' : 'وحدة جديدة'" @close="moduleOpen = false">
      <form class="space-y-4" @submit.prevent="saveModule">
        <FormField label="العنوان" required><input v-model="moduleForm.title" class="input" required /></FormField>
        <FormField label="الوصف"><textarea v-model="moduleForm.description" rows="2" class="input" /></FormField>
        <FormField label="الحالة"><select v-model="moduleForm.status" class="input"><option value="published">منشور</option><option value="draft">مسودة</option></select></FormField>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ</button>
      </form>
    </Modal>

    <!-- Lesson modal -->
    <Modal :open="lessonOpen" :title="lessonForm.id ? 'تعديل الدرس' : 'درس جديد'" @close="lessonOpen = false">
      <form class="space-y-4" @submit.prevent="saveLesson">
        <FormField label="عنوان الدرس" required><input v-model="lessonForm.title" class="input" required /></FormField>
        <FormField label="الوصف"><textarea v-model="lessonForm.description" rows="3" class="input" /></FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="المدة (ثوانٍ)"><input v-model.number="lessonForm.duration_seconds" type="number" min="0" class="input" dir="ltr" /></FormField>
          <FormField label="الحالة"><select v-model="lessonForm.status" class="input"><option value="published">منشور</option><option value="draft">مسودة</option></select></FormField>
        </div>
        <div class="flex gap-6">
          <label class="flex items-center gap-2 text-sm text-ink-soft"><input v-model="lessonForm.is_preview" type="checkbox" class="rounded border-line text-primary" /> معاينة مجانية</label>
          <label class="flex items-center gap-2 text-sm text-ink-soft"><input v-model="lessonForm.is_published" type="checkbox" class="rounded border-line text-primary" /> منشور</label>
        </div>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ الدرس</button>
      </form>
    </Modal>

    <!-- Direct lesson modal -->
    <Modal :open="directLessonOpen" title="درس مباشر جديد" @close="directLessonOpen = false">
      <form class="space-y-4" @submit.prevent="saveDirectLesson">
        <FormField label="عنوان الدرس" required><input v-model="directLessonForm.title" class="input" required /></FormField>
        <FormField label="الوصف"><textarea v-model="directLessonForm.description" rows="3" class="input" /></FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="المدة (ثوانٍ)"><input v-model.number="directLessonForm.duration_seconds" type="number" min="0" class="input" dir="ltr" /></FormField>
          <FormField label="الحالة"><select v-model="directLessonForm.status" class="input"><option value="published">منشور</option><option value="draft">مسودة</option></select></FormField>
        </div>
        <div class="flex gap-6">
          <label class="flex items-center gap-2 text-sm text-ink-soft"><input v-model="directLessonForm.is_preview" type="checkbox" class="rounded border-line text-primary" /> معاينة مجانية</label>
          <label class="flex items-center gap-2 text-sm text-ink-soft"><input v-model="directLessonForm.is_published" type="checkbox" class="rounded border-line text-primary" /> منشور</label>
        </div>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ الدرس</button>
      </form>
    </Modal>

    <!-- Lesson watch modal -->
    <Modal :open="!!watchLesson" :title="`مشاهدة — ${watchLesson?.title || ''}`" wide @close="watchLesson = null">
      <SecureVideoPlayer v-if="watchLesson" :key="watchLesson.id" :lesson-id="watchLesson.id" :course-name="course?.title || ''" />
    </Modal>

    <!-- Video import modal -->
    <Modal :open="!!importLesson" :title="`فيديو — ${importLesson?.title || ''}`" @close="importLesson = null">
      <div class="space-y-4">
        <div v-if="importLesson?.video_asset" class="rounded-lg border border-line p-3.5 text-sm">
          <div class="flex items-center justify-between">
            <span class="font-semibold text-ink">الفيديو الحالي</span>
            <StatusBadge :status="importLesson.video_asset.status" />
          </div>
          <p v-if="importLesson.video_asset.error_message" class="mt-1.5 text-xs text-danger">{{ importLesson.video_asset.error_message }}</p>
          <p class="mt-1 text-xs text-ink-faint" dir="ltr">{{ importLesson.video_asset.source_filename || '' }}</p>
        </div>

        <!-- Upload from device -->
        <div class="rounded-lg border border-line p-4 space-y-3">
          <p class="text-sm font-semibold text-ink">رفع من الجهاز</p>
          <input ref="fileInput" type="file" accept="video/*" class="hidden" @change="onVideoPicked" />
          <button v-if="!uploadProgress" class="btn-outline w-full" :disabled="importBusy" @click="fileInput?.click()">
            <Upload :size="15" /> اختر ملف فيديو…
          </button>
          <div v-if="uploadProgress" class="space-y-1.5">
            <div class="flex justify-between text-xs text-ink-muted">
              <span class="truncate" dir="ltr">{{ uploadName }}</span>
              <span>{{ uploadProgress }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-line">
              <div class="h-full rounded-full bg-primary transition-all" :style="{ width: uploadProgress + '%' }" />
            </div>
            <p class="text-xs text-ink-faint">{{ uploadProgress < 100 ? 'جارارٍ الرفع…' : 'اكتمل الرفع — بدأت المعالجة والضغط.' }}</p>
          </div>
          <p class="text-xs text-ink-faint">يُضغط تلقائيًا بعد الرفع (بدون فقدان محسوس للجودة) ويُحذف الأصل لتوفير المساحة.</p>
        </div>

        <div class="flex items-center gap-3">
          <span class="h-px flex-1 bg-line" /><span class="text-xs text-ink-faint">أو</span><span class="h-px flex-1 bg-line" />
        </div>

        <FormField label="رابط Google Drive" hint="شارك الملف مع حساب الخدمة (صلاحية عرض) ثم الصق الرابط.">
          <input v-model="driveUrl" class="input" dir="ltr" placeholder="https://drive.google.com/file/d/…/view" />
        </FormField>
        <button class="btn-primary w-full" :disabled="importBusy || !driveUrl" @click="doImport">
          <Loader2 v-if="importBusy" :size="15" class="animate-spin" /> استيراد وتأمين الفيديو
        </button>
      </div>
    </Modal>

    <!-- Create Quiz for Lesson Modal -->
    <Modal :open="quizModalOpen" :title="`إضافة اختبار لدرس: ${targetLesson?.title || ''}`" @close="quizModalOpen = false">
      <form class="space-y-4" @submit.prevent="saveLessonQuiz">
        <FormField label="عنوان الاختبار" required :error="quizErrors.title?.[0]">
          <input v-model="quizForm.title" class="input" required placeholder="مثال: اختبار استيعاب الدرس..." />
        </FormField>
        <FormField label="وصف الاختبار">
          <textarea v-model="quizForm.description" rows="2" class="input" placeholder="تعليمات سريعة للاختبار..." />
        </FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="درجة النجاح %">
            <input v-model.number="quizForm.passing_score" type="number" min="1" max="100" class="input" dir="ltr" />
          </FormField>
          <FormField label="عدد المحاولات">
            <input v-model.number="quizForm.max_attempts" type="number" min="1" max="10" class="input" dir="ltr" />
          </FormField>
        </div>
        <div class="rounded-lg bg-surface p-3 text-xs text-ink-muted">
          💡 بعد الإنشاء، ستنتقل مباشرة لشاشة إضافة وتحرير أسئلة الاختبار وخيارات الإجابة.
        </div>
        <button class="btn-primary w-full !py-2.5" :disabled="busy">
          <Loader2 v-if="busy" :size="15" class="animate-spin" /> إنشاء وبدء كتابة الأسئلة
        </button>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { auth } from '../../stores/auth';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import WavixaCertificate from '../../components/WavixaCertificate.vue';
import SecureVideoPlayer from '../../components/SecureVideoPlayer.vue';
import {
  Plus, Pencil, Trash2, PlayCircle, Play, Loader2, Video, Upload, Award,
  HelpCircle, PlusCircle
} from '@lucide/vue';

const route = useRoute();
const router = useRouter();
const course = ref(null);
const loading = ref(true);
const busy = ref(false);
const tab = ref('content');
const tabs = [
  { id: 'content', label: 'المحتوى' },
  { id: 'settings', label: 'إعدادات الكورس' },
  { id: 'certificate', label: 'شهادة الكورس 🎓' },
];

const moduleOpen = ref(false);
const moduleForm = ref({});
const lessonOpen = ref(false);
const lessonForm = ref({});
const lessonModule = ref(null);
const directLessonOpen = ref(false);
const directLessonForm = ref({
  title: '', description: '', duration_seconds: 0, status: 'published', is_preview: false, is_published: true,
});
const importLesson = ref(null);
const watchLesson = ref(null);
const driveUrl = ref('');
const importBusy = ref(false);
const fileInput = ref(null);
const uploadProgress = ref(0);
const uploadName = ref('');

// Lesson Quiz Modal States
const quizModalOpen = ref(false);
const targetLesson = ref(null);
const quizForm = ref({ title: '', description: '', passing_score: 70, max_attempts: 3 });
const quizErrors = ref({});

function openCreateQuiz(lesson) {
  targetLesson.value = lesson;
  quizForm.value = {
    title: `اختبار: ${lesson.title}`,
    description: '',
    passing_score: 70,
    max_attempts: 3,
  };
  quizErrors.value = {};
  quizModalOpen.value = true;
}

async function saveLessonQuiz() {
  busy.value = true;
  quizErrors.value = {};
  try {
    const payload = {
      title: quizForm.value.title,
      description: quizForm.value.description,
      passing_score: quizForm.value.passing_score,
      max_attempts: quizForm.value.max_attempts,
      lesson_id: targetLesson.value.id,
      module_id: targetLesson.value.module_id,
      status: 'published',
      questions: [
        {
          question: 'نص السؤال الأول...',
          points: 1,
          options: [
            { text: 'الخيار الأول (صحيح)', is_correct: true },
            { text: 'الخيار الثاني', is_correct: false },
          ],
        },
      ],
    };
    const { data } = await api.post(`/instructor/courses/${course.value.id}/quizzes`, payload);
    quizModalOpen.value = false;
    toast('تم إنشاء الاختبار بنجاح، يمكنك الآن تعديل الأسئلة.');
    router.push(`/instructor/quizzes/${data.quiz.id}`);
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') quizErrors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

async function load() {
  const { data } = await api.get(`/instructor/courses/${route.params.id}`);
  course.value = data.course;
  loading.value = false;
}

async function saveCourse() {
  busy.value = true;
  try {
    const { data } = await api.patch(`/instructor/courses/${course.value.id}`, course.value);
    course.value = data.course;
    toast('تم حفظ الكورس.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

async function uploadImage(e, field) {
  const file = e.target.files?.[0];
  e.target.value = '';
  if (!file) return;
  const fd = new FormData();
  fd.append('file', file);
  fd.append('folder', 'courses');
  try {
    const { data } = await api.post('/instructor/uploads', fd);
    course.value[field] = data.url;
    toast('تم رفع الصورة — لا تنسَ الحفظ.');
  } catch (err) {
    toast(unwrapError(err), 'error');
  }
}

function openModule(mod = null) {
  moduleForm.value = mod ? { ...mod } : { title: '', description: '', status: 'published' };
  moduleOpen.value = true;
}

async function saveModule() {
  busy.value = true;
  try {
    if (moduleForm.value.id) {
      await api.patch(`/instructor/modules/${moduleForm.value.id}`, moduleForm.value);
    } else {
      await api.post(`/instructor/courses/${course.value.id}/modules`, moduleForm.value);
    }
    moduleOpen.value = false;
    toast('تم الحفظ.');
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

async function removeModule(mod) {
  if (!confirm(`حذف الوحدة «${mod.title}» وكل دروسها؟`)) return;
  await api.delete(`/instructor/modules/${mod.id}`);
  load();
}

function openLesson(mod, lesson = null) {
  lessonModule.value = mod;
  lessonForm.value = lesson
    ? { ...lesson }
    : { title: '', description: '', duration_seconds: 0, status: 'published', is_preview: false, is_published: true };
  lessonOpen.value = true;
}

async function saveLesson() {
  busy.value = true;
  try {
    if (lessonForm.value.id) {
      await api.patch(`/instructor/lessons/${lessonForm.value.id}`, lessonForm.value);
    } else {
      await api.post(`/instructor/modules/${lessonModule.value.id}/lessons`, lessonForm.value);
    }
    lessonOpen.value = false;
    toast('تم الحفظ.');
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

async function removeLesson(lesson) {
  if (!confirm(`حذف الدرس «${lesson.title}»؟`)) return;
  await api.delete(`/instructor/lessons/${lesson.id}`);
  load();
}

function openDirectLesson() {
  directLessonForm.value = { title: '', description: '', duration_seconds: 0, status: 'published', is_preview: false, is_published: true };
  directLessonOpen.value = true;
}

async function saveDirectLesson() {
  busy.value = true;
  try {
    await api.post(`/instructor/courses/${course.value.id}/lessons`, directLessonForm.value);
    directLessonOpen.value = false;
    toast('تم إنشاء الدرس.');
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

function openImport(lesson) {
  importLesson.value = lesson;
  driveUrl.value = '';
  uploadProgress.value = 0;
  uploadName.value = '';
}

async function onVideoPicked(e) {
  const file = e.target.files?.[0];
  e.target.value = '';
  if (!file) return;

  importBusy.value = true;
  uploadProgress.value = 1;
  uploadName.value = file.name;
  try {
    const fd = new FormData();
    fd.append('video', file);
    const { data } = await api.post(`/instructor/lessons/${importLesson.value.id}/upload-video`, fd, {
      onUploadProgress: (ev) => { uploadProgress.value = Math.round((ev.loaded / (ev.total || file.size)) * 100); },
    });
    toast('اكتمل الرفع — بدأت المعالجة.');
    importLesson.value.video_asset = data.asset;
    importLesson.value = null;
    load();
  } catch (err) {
    uploadProgress.value = 0;
    toast(unwrapError(err), 'error');
  } finally {
    importBusy.value = false;
  }
}

async function doImport() {
  importBusy.value = true;
  try {
    const { data } = await api.post(`/instructor/lessons/${importLesson.value.id}/import-video`, { drive_url: driveUrl.value });
    toast('بدأ الاستيراد.');
    importLesson.value.video_asset = data.asset;
    importLesson.value = null;
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    importBusy.value = false;
  }
}

onMounted(() => load());
</script>
