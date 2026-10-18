<template>
  <div v-if="loading" class="space-y-4">
    <div class="skeleton aspect-video rounded-2xl w-full" />
    <div class="skeleton h-8 w-1/2 rounded-lg" />
  </div>

  <div v-else-if="lesson" class="space-y-6">
    <!-- Top Bar: Course Return & Quick Nav -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/60 pb-3">
      <div class="flex items-center gap-2 min-w-0 text-xs text-ink-muted">
        <RouterLink :to="`/dashboard/courses/${route.params.slug}`" class="hover:text-primary transition-colors flex items-center gap-1 font-semibold text-ink">
          <ChevronRight :size="15" /> {{ courseTitle || 'الكورس' }}
        </RouterLink>
        <span>/</span>
        <span class="text-ink-faint truncate max-w-[200px] sm:max-w-md">{{ lesson.title }}</span>
      </div>

      <div class="flex items-center gap-2">
        <!-- Prev Lesson Button -->
        <button v-if="prevLesson"
                class="btn-outline !py-1.5 !px-3 !text-xs flex items-center gap-1"
                @click="navigateToLesson(prevLesson)">
          <ChevronRight :size="14" /> السابق
        </button>

        <!-- Next Lesson Button with Smart Lock -->
        <button v-if="nextLesson"
                class="btn-primary !py-1.5 !px-3 !text-xs flex items-center gap-1 transition-all"
                :class="{ 'opacity-60 !bg-stone-300 !text-ink-muted cursor-not-allowed shadow-none': isNextLocked }"
                :title="isNextLocked ? 'أكمل مشاهدة الفيديو أولاً لفتح الدرس التالي' : 'الانتقال للدرس التالي'"
                @click="attemptNextLesson">
          <Lock v-if="isNextLocked" :size="12" />
          <span>التالي</span>
          <ChevronLeft :size="14" />
        </button>
      </div>
    </div>

    <!-- Main Grid: Video Player + Sidebar -->
    <div class="grid gap-6 lg:grid-cols-12">
      <!-- Player and Lesson Info -->
      <div class="lg:col-span-8 xl:col-span-8 space-y-5">
        <!-- Video Player Box -->
        <div class="relative overflow-hidden rounded-2xl shadow-lg border border-line bg-black">
          <SecureVideoPlayer
            v-if="hasVideo"
            :key="lesson.id"
            :lesson-id="lesson.id"
            :course-name="courseTitle"
            @completed="onCompleted"
            @reload="reloadKey++"
          />
          <div v-else class="flex aspect-video items-center justify-center text-ink-faint bg-stone-900">
            <div class="text-center p-6 text-white/80">
              <FileText :size="40" class="mx-auto mb-3 text-primary" />
              <h3 class="text-base font-bold">درس نصي / بدون فيديو</h3>
              <p class="text-xs text-stone-400 mt-1 max-w-sm">اقرأ محتوى وتفاصيل الدرس بالأسفل ثم علّم على الدرس كمكتمل للمتابعة.</p>
              <button v-if="!isLessonCompleted" class="btn-primary mt-4 !text-xs" @click="onCompleted">
                <CheckCircle2 :size="15" /> تحديد كمكتمل
              </button>
            </div>
          </div>
        </div>

        <!-- Post-Video Completion & Quiz Prompter Banner -->
        <transition enter-active-class="transition duration-400 ease-out" enter-from-class="opacity-0 -translate-y-3 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100">
          <div v-if="showNextPrompt || (isLessonCompleted && !userPassedQuiz && quiz)"
               class="relative overflow-hidden rounded-2xl border-2 shadow-md p-5"
               :class="quiz && !userPassedQuiz
                 ? 'border-purple-300 bg-gradient-to-l from-purple-600/10 via-purple-500/5 to-white'
                 : 'border-primary/40 bg-gradient-to-l from-primary/10 via-emerald-500/10 to-primary/5'">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-3.5 min-w-0">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                     :class="quiz && !userPassedQuiz ? 'bg-purple-600' : 'bg-primary'">
                  <HelpCircle v-if="quiz && !userPassedQuiz" :size="24" class="text-amber-200" />
                  <CheckCircle2 v-else :size="24" />
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="badge-green !text-[11px] font-bold">
                      {{ isLessonCompleted ? 'أحسنت! أتممت مشاهدة الفيديو بالكامل ✅' : 'اكتملت المشاهدة' }}
                    </span>
                    <span v-if="countdown > 0" class="text-[11px] font-bold text-primary animate-pulse">
                      الانتقال التلقائي خلال {{ countdown }} ثوانٍ...
                    </span>
                  </div>

                  <!-- Case 1: Quiz attached and not passed yet -->
                  <div v-if="quiz && !userPassedQuiz" class="mt-1">
                    <h4 class="text-sm font-bold text-ink">يوجد اختبار قصير لهذا الدرس: {{ quiz.title }}</h4>
                    <p class="text-xs text-ink-muted">
                      اختبر استيعابك الآن لترسيخ المفاهيم قبل الانتقال للدرس التالي (درجة النجاح: {{ quiz.passing_score }}%).
                    </p>
                  </div>
                  <!-- Case 2: Quiz passed or no quiz, next lesson available -->
                  <div v-else-if="nextLesson" class="mt-1">
                    <h4 class="text-sm font-bold text-ink truncate">الدرس التالي: {{ nextLesson.title }}</h4>
                    <p class="text-xs text-ink-muted">تم فتح الدرس التالي بنجاح! جاهز لمواصلة التعلّم؟</p>
                  </div>
                  <!-- Case 3: Course completely finished -->
                  <div v-else class="mt-1">
                    <h4 class="text-sm font-bold text-ink">مبارك! أنهيت جميع دروس هذا الكورس 🎓</h4>
                    <p class="text-xs text-ink-muted">يمكنك الآن استعراض شهادتك المعتمدة مباشرة.</p>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2 shrink-0">
                <button v-if="countdown > 0" class="btn-outline !py-1.5 !px-2.5 !text-xs text-ink-muted" @click="cancelCountdown">
                  إيقاف المؤقت
                </button>

                <!-- Start Quiz CTA -->
                <RouterLink v-if="quiz && !userPassedQuiz"
                            :to="`/dashboard/quizzes/${quiz.id}`"
                            class="btn-primary !bg-purple-600 hover:!bg-purple-700 !py-2.5 !px-5 !text-xs font-bold text-white shadow-sm flex items-center gap-1.5">
                  <HelpCircle :size="15" /> بدء الاختبار الآن
                </RouterLink>

                <!-- Next Lesson CTA -->
                <button v-else-if="nextLesson"
                        class="btn-primary !py-2.5 !px-5 !text-xs font-bold shadow-sm flex items-center gap-1.5"
                        @click="goToNextLessonImmediate">
                  <span>الانتقال للدرس التالي</span> <ChevronLeft :size="15" />
                </button>

                <!-- View Certificate CTA -->
                <RouterLink v-else
                            :to="`/dashboard/courses/${route.params.slug}`"
                            class="btn-primary !py-2.5 !px-5 !text-xs font-bold shadow-sm">
                  عرض الشهادة والكورس
                </RouterLink>
              </div>
            </div>
          </div>
        </transition>

        <!-- Lesson Meta Card -->
        <div class="card p-6 space-y-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
              <span class="text-xs font-semibold text-primary">الوحدة {{ currentModuleIndex + 1 }}: {{ currentModule?.title }}</span>
              <h1 class="text-xl sm:text-2xl font-extrabold text-ink leading-snug">{{ lesson.title }}</h1>
              <div class="flex items-center gap-3 text-xs text-ink-faint pt-1">
                <span v-if="lesson.duration_seconds" class="flex items-center gap-1">
                  <Clock :size="13" /> {{ formatDuration(lesson.duration_seconds) }}
                </span>
                <span v-if="lesson.is_preview" class="badge-green">معاينة مجانية</span>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <span v-if="isLessonCompleted" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                <CheckCircle2 :size="14" /> درس مكتمل
              </span>
              <span v-else class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-ink-muted">
                <Clock :size="13" /> قيد المتابعة
              </span>
            </div>
          </div>

          <p v-if="lesson.description" class="whitespace-pre-line text-sm leading-relaxed text-ink-soft border-t border-line/60 pt-4">
            {{ lesson.description }}
          </p>
        </div>

        <!-- Attached Quiz Card (Always visible once video completed, or shown in meta) -->
        <div v-if="quiz" class="relative overflow-hidden rounded-2xl border border-purple-200 bg-white p-5 shadow-xs">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl shadow-sm"
                   :class="userPassedQuiz ? 'bg-emerald-600 text-white' : 'bg-purple-600 text-white'">
                <BadgeCheck v-if="userPassedQuiz" :size="24" />
                <FileQuestion v-else :size="24" />
              </div>
              <div>
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="badge-blue !bg-purple-100 !text-purple-800">اختبار الدرس</span>
                  <span v-if="userPassedQuiz" class="badge-green">تم الاجتياز بنجاح ({{ quizAttempt.score }}%)</span>
                </div>
                <h3 class="text-base font-bold text-ink">{{ quiz.title }}</h3>
                <p class="text-xs text-ink-muted">درجة النجاح: {{ quiz.passing_score }}% · الحد الأقصى للمحاولات: {{ quiz.max_attempts }}</p>
              </div>
            </div>
            <RouterLink :to="`/dashboard/quizzes/${quiz.id}`"
                        class="btn-primary !px-5 !py-2.5 !text-xs font-bold text-white shadow-xs"
                        :class="userPassedQuiz ? '!bg-emerald-700 hover:!bg-emerald-800' : '!bg-purple-600 hover:!bg-purple-700'">
              {{ userPassedQuiz ? 'إعادة الاختبار' : 'بدء الاختبار' }}
            </RouterLink>
          </div>
        </div>

        <!-- Resources Section -->
        <div v-if="lesson.resources?.length" class="card p-5 space-y-3">
          <h3 class="flex items-center gap-2 text-sm font-bold text-ink">
            <FolderKanban :size="16" class="text-primary" /> الموارد والملفات المرفقة
          </h3>
          <ul class="divide-y divide-line/60">
            <li v-for="r in lesson.resources" :key="r.id" class="py-2.5 first:pt-0 last:pb-0">
              <a :href="r.url || '#'" target="_blank" rel="noopener"
                 class="flex items-center justify-between text-sm text-ink-soft hover:text-primary transition-colors group">
                <span class="flex items-center gap-2 font-medium">
                  <Link2 :size="15" class="text-ink-faint group-hover:text-primary" /> {{ r.title }}
                </span>
                <ExternalLink :size="14" class="text-ink-faint group-hover:text-primary" />
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Sidebar: Interactive Curriculum List with Progress -->
      <aside class="lg:col-span-4 xl:col-span-4 space-y-4">
        <div class="card p-5 sticky top-20 shadow-sm border border-line">
          <!-- Sidebar Header: Total Course Progress -->
          <div class="border-b border-line pb-4 mb-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-sm font-bold text-ink">محتوى الكورس</h3>
              <span class="text-xs font-extrabold text-emerald-700">{{ overallProgressPercent }}%</span>
            </div>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-stone-100">
              <div class="h-full rounded-full bg-emerald-600 transition-all duration-500"
                   :style="{ width: overallProgressPercent + '%' }" />
            </div>
            <div class="flex justify-between items-center text-[11px] text-ink-faint mt-1.5">
              <span>{{ completedLessonsCount }} من {{ allLessonsFlat.length }} درساً منجزاً</span>
              <RouterLink :to="`/dashboard/courses/${route.params.slug}`" class="text-primary hover:underline font-semibold">
                صفحة الكورس
              </RouterLink>
            </div>
          </div>

          <!-- Modules and Lessons Accordion -->
          <div class="max-h-[65vh] overflow-y-auto space-y-3 pl-1 -mr-1 pr-1 custom-scrollbar">
            <div v-for="(mod, mIndex) in curriculum" :key="mod.id" class="rounded-xl border border-line/70 overflow-hidden bg-white">
              <!-- Module Header Toggle -->
              <div class="bg-surface/70 hover:bg-surface px-3.5 py-2.5 flex items-center justify-between cursor-pointer transition-colors select-none"
                   @click="toggleSidebarModule(mod.id)">
                <div class="min-w-0 flex items-center gap-2">
                  <span class="text-xs font-bold text-ink-muted">{{ mIndex + 1 }}.</span>
                  <p class="text-xs font-bold text-ink truncate">{{ mod.title }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <span class="text-[10px] font-semibold text-ink-faint">
                    {{ (mod.lessons || []).filter(l => l.user_progress?.completed).length }}/{{ (mod.lessons || []).length }}
                  </span>
                  <ChevronDown :size="14" class="text-ink-faint transition-transform duration-200"
                               :class="{ 'rotate-180': isSidebarExpanded(mod.id) }" />
                </div>
              </div>

              <!-- Module Lessons -->
              <div v-show="isSidebarExpanded(mod.id)" class="divide-y divide-line/40">
                <div
                  v-for="l in mod.lessons" :key="l.id"
                  class="flex items-center gap-2.5 px-3 py-2.5 text-xs transition-all duration-150 group cursor-pointer"
                  :class="l.id === lesson.id
                    ? 'bg-primary-faint text-primary font-bold border-r-2 border-primary'
                    : 'text-ink-soft hover:bg-stone-50 hover:text-ink'"
                  @click="tryNavigateToLesson(l)">
                  <!-- Icon indicator -->
                  <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px]"
                        :class="l.user_progress?.completed || (l.id === lesson.id && isLessonCompleted)
                          ? 'text-emerald-600 bg-emerald-50'
                          : (l.id === lesson.id ? 'text-primary' : 'text-ink-faint')">
                    <CheckCircle2 v-if="l.user_progress?.completed || (l.id === lesson.id && isLessonCompleted)" :size="14" />
                    <Play v-else-if="l.id === lesson.id" :size="10" class="fill-current translate-x-[-0.5px]" />
                    <PlayCircle v-else :size="13" />
                  </span>

                  <!-- Title -->
                  <span class="truncate flex-1">{{ l.title }}</span>

                  <!-- Quiz icon in list if has quiz -->
                  <HelpCircle v-if="l.quiz" :size="12" class="text-purple-600 shrink-0" title="اختبار مرفق" />

                  <!-- Duration / badges -->
                  <span v-if="l.duration_seconds" class="text-[10px] text-ink-faint shrink-0">
                    {{ formatDuration(l.duration_seconds) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>

  <!-- Locked Lesson Card -->
  <div v-else class="card p-10 text-center max-w-lg mx-auto my-12">
    <div class="h-16 w-16 rounded-2xl bg-amber-500/10 text-amber-700 flex items-center justify-center mx-auto mb-4">
      <Lock :size="32" />
    </div>
    <h2 class="text-lg font-bold text-ink">هذا الدرس غير متاح لك حالياً</h2>
    <p class="mt-2 text-sm text-ink-muted leading-relaxed">{{ lockMessage }}</p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
      <RouterLink :to="`/dashboard/courses/${route.params.slug}`" class="btn-outline text-xs">
        العودة للكورس
      </RouterLink>
      <a v-if="wa" :href="wa" target="_blank" rel="noopener" class="btn-emerald text-xs">
        <MessageCircle :size="15" /> تواصل معنا عبر WhatsApp للتفعيل
      </a>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { toast } from '../../stores/toast';
import { whatsappUrl } from '../../stores/settings';
import SecureVideoPlayer from '../../components/SecureVideoPlayer.vue';
import {
  FileText, Link2, PlayCircle, CheckCircle2, Lock, MessageCircle,
  ChevronRight, ChevronLeft, ChevronDown, Clock, FolderKanban,
  FileQuestion, ExternalLink, Play, HelpCircle, BadgeCheck
} from '@lucide/vue';

const route = useRoute();
const router = useRouter();

const lesson = ref(null);
const progress = ref(null);
const quiz = ref(null);
const quizAttempt = ref(null);
const hasVideo = ref(false);
const curriculum = ref([]);
const courseTitle = ref('');
const loading = ref(true);
const lockMessage = ref('');
const reloadKey = ref(0);

// Next Lesson & Completion Prompt states
const showNextPrompt = ref(false);
const countdown = ref(0);
let countdownTimer = null;

// Sidebar module accordion states
const sidebarExpanded = ref(new Set());

const wa = computed(() => whatsappUrl(courseTitle.value));

function formatDuration(s) {
  if (!s) return '0:00';
  return `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
}

// Flat list of all lessons in curriculum order
const allLessonsFlat = computed(() => {
  return curriculum.value.flatMap((m) => m.lessons || []);
});

const currentLessonIndex = computed(() => {
  return allLessonsFlat.value.findIndex((l) => l.id === lesson.value?.id);
});

const prevLesson = computed(() => {
  const idx = currentLessonIndex.value;
  if (idx > 0) return allLessonsFlat.value[idx - 1];
  return null;
});

const nextLesson = computed(() => {
  const idx = currentLessonIndex.value;
  if (idx >= 0 && idx < allLessonsFlat.value.length - 1) {
    return allLessonsFlat.value[idx + 1];
  }
  return null;
});

const currentModule = computed(() => {
  return curriculum.value.find((m) => (m.lessons || []).some((l) => l.id === lesson.value?.id));
});

const currentModuleIndex = computed(() => {
  return curriculum.value.findIndex((m) => m.id === currentModule.value?.id);
});

const isLessonCompleted = computed(() => {
  return !!progress.value?.completed;
});

const userPassedQuiz = computed(() => {
  return !!quizAttempt.value?.passed;
});

// Enforce video completion before advancing to next lesson
const isNextLocked = computed(() => {
  // If lesson has video, student MUST finish watching video first
  if (hasVideo.value && !isLessonCompleted.value) {
    return true;
  }
  return false;
});

const completedLessonsCount = computed(() => {
  return allLessonsFlat.value.filter((l) => l.user_progress?.completed || (l.id === lesson.value?.id && progress.value?.completed)).length;
});

const overallProgressPercent = computed(() => {
  const total = allLessonsFlat.value.length;
  if (!total) return 0;
  return Math.min(100, Math.round((completedLessonsCount.value / total) * 100));
});

// Sidebar Accordion helper
function isSidebarExpanded(modId) {
  return sidebarExpanded.value.has(modId);
}

function toggleSidebarModule(modId) {
  if (sidebarExpanded.value.has(modId)) {
    sidebarExpanded.value.delete(modId);
  } else {
    sidebarExpanded.value.add(modId);
  }
}

function cancelCountdown() {
  if (countdownTimer) {
    clearInterval(countdownTimer);
    countdownTimer = null;
  }
  countdown.value = 0;
}

function attemptNextLesson() {
  if (isNextLocked.value) {
    toast('يرجى إنهاء مشاهدة الفيديو بالكامل أولاً قبل الانتقال للدرس التالي 🔒', 'error');
    return;
  }
  goToNextLessonImmediate();
}

function tryNavigateToLesson(target) {
  if (target.id === lesson.value?.id) return;
  // If moving forward past current lesson and current lesson not completed
  const targetIdx = allLessonsFlat.value.findIndex(l => l.id === target.id);
  if (targetIdx > currentLessonIndex.value && isNextLocked.value) {
    toast('يرجى إكمال الدرس الحالي أولاً لمتابعة الدروس التالية 🔒', 'error');
    return;
  }
  navigateToLesson(target);
}

function goToNextLessonImmediate() {
  cancelCountdown();
  showNextPrompt.value = false;
  if (nextLesson.value) {
    navigateToLesson(nextLesson.value);
  }
}

function navigateToLesson(target) {
  cancelCountdown();
  showNextPrompt.value = false;
  router.push(`/dashboard/courses/${route.params.slug}/lessons/${target.id}`);
}

async function onCompleted() {
  if (!progress.value) progress.value = {};
  progress.value.completed = true;

  // Update in curriculum state
  const found = allLessonsFlat.value.find((l) => l.id === lesson.value?.id);
  if (found) {
    if (!found.user_progress) found.user_progress = {};
    found.user_progress.completed = true;
  }

  // Record completed position via API in case it was a text lesson or ended naturally
  try {
    await api.post('/me/progress', {
      lesson_id: lesson.value.id,
      position_seconds: lesson.value.duration_seconds || 1,
      duration_seconds: lesson.value.duration_seconds || 1,
    });
  } catch { /* ignore */ }

  // Show Prompt
  showNextPrompt.value = true;

  // If there's an attached Quiz that wasn't passed yet, do NOT auto-advance; student must take quiz!
  if (quiz.value && !userPassedQuiz.value) {
    cancelCountdown();
    return;
  }

  // If there's a next lesson and no quiz or quiz passed, start auto-advance countdown (5 seconds)
  if (nextLesson.value) {
    countdown.value = 5;
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(() => {
      countdown.value--;
      if (countdown.value <= 0) {
        clearInterval(countdownTimer);
        countdownTimer = null;
        goToNextLessonImmediate();
      }
    }, 1000);
  }
}

async function loadLesson() {
  loading.value = true;
  lesson.value = null;
  quizAttempt.value = null;
  showNextPrompt.value = false;
  cancelCountdown();

  try {
    const { data } = await api.get(`/me/courses/${route.params.slug}/lessons/${route.params.lessonId}`);
    lesson.value = data.lesson;
    progress.value = data.progress;
    quiz.value = data.quiz;
    quizAttempt.value = data.quiz_attempt || null;
    hasVideo.value = data.has_video;

    // Expand current module in sidebar
    if (lesson.value?.module_id) {
      sidebarExpanded.value.add(lesson.value.module_id);
    }
  } catch (e) {
    lockMessage.value = e?.response?.data?.message || 'لا تملك صلاحية الوصول لهذا الدرس.';
    lesson.value = null;
  } finally {
    loading.value = false;
  }
}

async function loadCurriculum() {
  try {
    const { data } = await api.get(`/me/courses/${route.params.slug}`);
    curriculum.value = data.course.modules || [];
    courseTitle.value = data.course.title;

    // Expand current lesson module by default
    const mod = curriculum.value.find((m) => (m.lessons || []).some((l) => l.id === Number(route.params.lessonId)));
    if (mod) {
      sidebarExpanded.value.add(mod.id);
    } else if (curriculum.value.length) {
      sidebarExpanded.value.add(curriculum.value[0].id);
    }
  } catch { /* enrolled check may fail for preview */ }
}

onMounted(() => {
  loadLesson();
  loadCurriculum();
});

onBeforeUnmount(() => {
  cancelCountdown();
});

watch(() => route.params.lessonId, () => {
  if (route.params.lessonId) {
    loadLesson();
  }
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 4px;
}
</style>
