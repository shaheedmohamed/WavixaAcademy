<template>
  <div v-if="loading"><div class="skeleton h-8 w-1/3 mb-4" /><div class="skeleton h-96" /></div>

  <div v-else-if="course" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <nav class="mb-1 flex items-center gap-2 text-xs text-ink-faint">
          <RouterLink to="/admin/courses" class="hover:text-ink">الكورسات</RouterLink> / <span>{{ course.title }}</span>
        </nav>
        <h1 class="text-xl font-bold text-ink">{{ course.title }}</h1>
      </div>
      <div class="flex items-center gap-2">
        <StatusBadge :status="course.status" />
        <RouterLink :to="`/courses/${course.slug}`" target="_blank" class="btn-outline !text-xs"><ExternalLink :size="14" /> معاينة</RouterLink>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 border-b border-line">
      <button v-for="t in tabs" :key="t.id" class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="tab === t.id ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink'"
        @click="tab = t.id">
        {{ t.label }}
      </button>
    </div>

    <!-- Content tab -->
    <div v-if="tab === 'content'" class="space-y-4">
      <div class="flex justify-end">
        <button class="btn-primary !text-xs" @click="openModule()"><Plus :size="14" /> وحدة جديدة</button>
      </div>

      <div v-for="(mod, i) in course.modules" :key="mod.id" class="card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-line bg-surface px-5 py-3.5">
          <div class="flex items-center gap-3 min-w-0">
            <GripVertical :size="16" class="text-ink-faint" />
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
            <StatusBadge v-if="lesson.video_asset" :status="lesson.video_asset.status" />
            <span v-else class="badge-gray">بدون فيديو</span>
            <div class="flex gap-1">
              <button class="btn-ghost !px-2 !py-1 !text-xs text-primary" @click="openImport(lesson)"><Video :size="13" /> فيديو</button>
              <button class="btn-ghost !p-1.5" @click="openLesson(mod, lesson)"><Pencil :size="14" /></button>
              <button class="btn-ghost !p-1.5 text-danger" @click="removeLesson(lesson)"><Trash2 :size="14" /></button>
            </div>
          </li>
          <li v-if="!mod.lessons?.length" class="px-5 py-4 text-center text-xs text-ink-faint">لا دروس — أضف أول درس.</li>
        </ul>

        <!-- Module activities / quizzes -->
        <div class="border-t border-line">
          <div v-for="p in moduleProjects(mod.id)" :key="p.id"
            class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-line/60 last:border-b-0">
            <FileQuestion :size="15" class="shrink-0 text-primary" />
            <span class="flex-1 min-w-0 text-sm font-medium text-ink truncate">{{ p.title }}</span>
            <span v-if="p.attachment_type" class="badge-blue">{{ { image: 'صورة', video: 'فيديو', link: 'رابط' }[p.attachment_type] }}</span>
            <span class="text-[11px] text-ink-faint">
              {{ p.after_lesson_id ? 'بعد: ' + (mod.lessons.find(l => l.id === p.after_lesson_id)?.title || '—') : 'نهاية الوحدة' }}
            </span>
            <StatusBadge :status="p.status" />
            <div class="flex gap-1">
              <button class="btn-ghost !p-1.5" @click="openProject(mod, p)"><Pencil :size="14" /></button>
              <button class="btn-ghost !p-1.5 text-danger" @click="removeProject(p)"><Trash2 :size="14" /></button>
            </div>
          </div>
          <div class="px-5 py-2.5 bg-surface/60">
            <button class="btn-ghost !px-2 !py-1 !text-xs text-primary" @click="openProject(mod)">
              <Plus :size="14" /> إضافة اختبار / نشاط للوحدة
            </button>
          </div>
        </div>
      </div>
      <p v-if="!course.modules?.length" class="card py-10 text-center text-sm text-ink-faint">ابدأ بإضافة أول وحدة للكورس.</p>
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
      <div class="grid gap-4 sm:grid-cols-3">
        <FormField label="الحالة">
          <select v-model="course.status" class="input">
            <option value="draft">مسودة</option>
            <option value="published">منشور</option>
            <option value="archived">مؤرشف</option>
          </select>
        </FormField>
        <FormField label="المدرب">
          <select v-model="course.instructor_id" class="input">
            <option :value="null">—</option>
            <option v-for="i in instructors" :key="i.id" :value="i.id">{{ i.name }}</option>
          </select>
        </FormField>
        <FormField label="ترتيب العرض"><input v-model.number="course.sort_order" type="number" min="0" class="input" dir="ltr" /></FormField>
      </div>
      <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input v-model="course.is_featured" type="checkbox" class="rounded border-line text-primary focus:ring-primary/30" />
        كورس مميز (يظهر في الصفحة الرئيسية)
      </label>
      <button class="btn-primary" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ التغييرات</button>
    </form>

    <!-- Cohorts tab -->
    <div v-if="tab === 'cohorts'" class="space-y-4">
      <div class="flex justify-end"><button class="btn-primary !text-xs" @click="cohortForm = { name: '', starts_at: '', ends_at: '', capacity: '' }; cohortOpen = true"><Plus :size="14" /> دفعة جديدة</button></div>
      <div class="card overflow-x-auto">
        <table class="table-base min-w-[520px]">
          <thead><tr><th>الاسم</th><th>البداية</th><th>النهاية</th><th>السعة</th><th>الحالة</th></tr></thead>
          <tbody>
            <tr v-for="c in course.cohorts" :key="c.id">
              <td class="font-semibold text-ink">{{ c.name }}</td>
              <td class="text-xs">{{ c.starts_at ? new Date(c.starts_at).toLocaleDateString('ar-EG') : '—' }}</td>
              <td class="text-xs">{{ c.ends_at ? new Date(c.ends_at).toLocaleDateString('ar-EG') : '—' }}</td>
              <td>{{ c.capacity || '—' }}</td>
              <td><StatusBadge :status="c.status" /></td>
            </tr>
            <tr v-if="!course.cohorts?.length"><td colspan="5" class="py-8 text-center text-ink-faint">لا دفعات.</td></tr>
          </tbody>
        </table>
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

    <!-- Video import modal -->
    <Modal :open="!!importLesson" :title="`استيراد فيديو — ${importLesson?.title || ''}`" @close="importLesson = null">
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
            <p class="text-xs text-ink-faint">{{ uploadProgress < 100 ? 'جارٍ الرفع…' : 'اكتمل الرفع — بدأت المعالجة والضغط على السيرفر.' }}</p>
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

    <!-- Project / activity modal -->
    <Modal :open="projectOpen" :title="projectForm.id ? 'تعديل النشاط' : 'اختبار / نشاط جديد'" @close="projectOpen = false">
      <form class="space-y-4" @submit.prevent="saveProject">
        <FormField label="عنوان النشاط" required :error="errors.title?.[0]">
          <input v-model="projectForm.title" class="input" required placeholder="مثال: اختبار الوحدة الأولى" />
        </FormField>
        <FormField label="ما المطلوب؟ (يظهر للطالب)" :error="errors.description?.[0]">
          <textarea v-model="projectForm.description" rows="3" class="input" placeholder="اشرح النشاط أو الاختبار وما المطلوب من الطالب…" />
        </FormField>
        <FormField label="تعليمات إضافية (اختياري)" :error="errors.instructions?.[0]">
          <textarea v-model="projectForm.instructions" rows="2" class="input" />
        </FormField>
        <FormField label="يظهر بعد درس" :error="errors.after_lesson_id?.[0]">
          <select v-model="projectForm.after_lesson_id" class="input">
            <option :value="null">نهاية الوحدة (بعد كل الدروس)</option>
            <option v-for="l in projectModule?.lessons || []" :key="l.id" :value="l.id">{{ l.title }}</option>
          </select>
        </FormField>
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField label="نوع المرفق" :error="errors.attachment_type?.[0]">
            <select v-model="projectForm.attachment_type" class="input">
              <option value="">بدون مرفق</option>
              <option value="image">صورة</option>
              <option value="video">فيديو</option>
              <option value="link">رابط</option>
            </select>
          </FormField>
          <FormField label="الحالة">
            <select v-model="projectForm.status" class="input">
              <option value="published">منشور</option>
              <option value="draft">مسودة</option>
            </select>
          </FormField>
        </div>
        <FormField v-if="projectForm.attachment_type === 'link'" label="الرابط" required :error="errors.attachment_url?.[0]">
          <input v-model="projectForm.attachment_url" class="input" dir="ltr" placeholder="https://..." />
        </FormField>
        <FormField v-else-if="projectForm.attachment_type" :label="projectForm.attachment_type === 'image' ? 'رفع صورة' : 'رفع فيديو'" :error="errors.attachment?.[0]">
          <input type="file" class="input !py-2" :accept="projectForm.attachment_type === 'image' ? 'image/*' : 'video/*'" @change="(e) => projectFile = e.target.files[0]" />
          <p v-if="projectForm.id && projectForm.attachment_url && !projectFile" class="mt-1 text-[11px] text-ink-faint">
            يوجد مرفق حالي — اترك الحقل فارغًا للإبقاء عليه.
          </p>
        </FormField>
        <button class="btn-primary w-full" :disabled="busy"><Loader2 v-if="busy" :size="15" class="animate-spin" /> حفظ النشاط</button>
      </form>
    </Modal>

    <!-- Cohort modal -->
    <Modal :open="cohortOpen" title="دفعة جديدة" @close="cohortOpen = false">
      <form class="space-y-4" @submit.prevent="saveCohort">
        <FormField label="اسم الدفعة" required><input v-model="cohortForm.name" class="input" required placeholder="دفعة أكتوبر 2026" /></FormField>
        <div class="grid grid-cols-2 gap-3">
          <FormField label="تبدأ"><input v-model="cohortForm.starts_at" type="date" class="input" dir="ltr" /></FormField>
          <FormField label="تنتهي"><input v-model="cohortForm.ends_at" type="date" class="input" dir="ltr" /></FormField>
        </div>
        <FormField label="السعة (اختياري)"><input v-model.number="cohortForm.capacity" type="number" min="1" class="input" dir="ltr" /></FormField>
        <button class="btn-primary w-full" :disabled="busy">حفظ</button>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import {
  Plus, Pencil, Trash2, Video, Upload, PlayCircle, GripVertical,
  ExternalLink, Loader2, FileQuestion,
} from '@lucide/vue';

const route = useRoute();
const course = ref(null);
const instructors = ref([]);
const loading = ref(true);
const busy = ref(false);
const tab = ref('content');
const tabs = [
  { id: 'content', label: 'المحتوى' },
  { id: 'settings', label: 'إعدادات الكورس' },
  { id: 'cohorts', label: 'الدفعات' },
];

const moduleOpen = ref(false);
const moduleForm = ref({});
const lessonOpen = ref(false);
const lessonForm = ref({});
const lessonModule = ref(null);
const importLesson = ref(null);
const driveUrl = ref('');
const importBusy = ref(false);
const fileInput = ref(null);
const uploadProgress = ref(0);
const uploadName = ref('');
const cohortOpen = ref(false);
const cohortForm = ref({});
const projectOpen = ref(false);
const projectForm = ref({});
const projectModule = ref(null);
const projectFile = ref(null);
const errors = ref({});

function moduleProjects(moduleId) {
  return (course.value?.projects || []).filter((p) => p.module_id === moduleId);
}

function openProject(mod, project = null) {
  projectModule.value = mod;
  projectFile.value = null;
  errors.value = {};
  projectForm.value = project
    ? { ...project }
    : { title: '', description: '', instructions: '', module_id: mod.id, after_lesson_id: null, attachment_type: '', attachment_url: '', status: 'published' };
  projectOpen.value = true;
}

async function saveProject() {
  busy.value = true;
  errors.value = {};
  try {
    const fd = new FormData();
    fd.append('module_id', projectModule.value.id);
    fd.append('title', projectForm.value.title || '');
    fd.append('description', projectForm.value.description || '');
    fd.append('instructions', projectForm.value.instructions || '');
    fd.append('status', projectForm.value.status || 'published');
    fd.append('attachment_type', projectForm.value.attachment_type || '');
    if (projectForm.value.after_lesson_id) fd.append('after_lesson_id', projectForm.value.after_lesson_id);
    if (projectForm.value.attachment_type === 'link') fd.append('attachment_url', projectForm.value.attachment_url || '');
    if (projectFile.value) fd.append('attachment', projectFile.value);

    if (projectForm.value.id) {
      fd.append('_method', 'PATCH');
      await api.post(`/admin/projects/${projectForm.value.id}`, fd);
    } else {
      await api.post(`/admin/courses/${course.value.id}/projects`, fd);
    }
    projectOpen.value = false;
    toast('تم حفظ النشاط.');
    load();
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    busy.value = false;
  }
}

async function removeProject(p) {
  if (!confirm(`حذف النشاط «${p.title}» وكل تسليماته؟`)) return;
  await api.delete(`/admin/projects/${p.id}`);
  load();
}

async function load() {
  const { data } = await api.get(`/admin/courses/${route.params.id}`);
  course.value = data.course;
  loading.value = false;
}

async function saveCourse() {
  busy.value = true;
  try {
    const { data } = await api.patch(`/admin/courses/${course.value.id}`, course.value);
    course.value = data.course;
    toast('تم حفظ الكورس.');
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

async function uploadImage(e, field) {
  const file = e.target.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('file', file);
  fd.append('folder', 'courses');
  try {
    const { data } = await api.post('/admin/uploads', fd);
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
      await api.patch(`/admin/modules/${moduleForm.value.id}`, moduleForm.value);
    } else {
      await api.post(`/admin/courses/${course.value.id}/modules`, moduleForm.value);
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
  await api.delete(`/admin/modules/${mod.id}`);
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
      await api.patch(`/admin/lessons/${lessonForm.value.id}`, lessonForm.value);
    } else {
      await api.post(`/admin/modules/${lessonModule.value.id}/lessons`, lessonForm.value);
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
  await api.delete(`/admin/lessons/${lesson.id}`);
  load();
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
    const { data } = await api.post(`/admin/lessons/${importLesson.value.id}/upload-video`, fd, {
      onUploadProgress: (ev) => { uploadProgress.value = Math.round((ev.loaded / (ev.total || file.size)) * 100); },
    });
    toast('اكتمل الرفع — المعالجة والضغط بدأت. تابع «استيراد الفيديو».');
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
    const { data } = await api.post(`/admin/lessons/${importLesson.value.id}/import-video`, { drive_url: driveUrl.value });
    toast('بدأ الاستيراد — تابع الحالة في صفحة استيراد الفيديو.');
    importLesson.value.video_asset = data.asset;
    importLesson.value = null;
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    importBusy.value = false;
  }
}

async function saveCohort() {
  busy.value = true;
  try {
    await api.post(`/admin/courses/${course.value.id}/cohorts`, cohortForm.value);
    cohortOpen.value = false;
    toast('تم إنشاء الدفعة.');
    load();
  } catch (e) {
    toast(unwrapError(e), 'error');
  } finally {
    busy.value = false;
  }
}

onMounted(async () => {
  await load();
  try {
    const { data } = await api.get('/admin/instructors/dropdown');
    instructors.value = data.instructors || [];
  } catch { /* non-critical */ }
});
</script>
