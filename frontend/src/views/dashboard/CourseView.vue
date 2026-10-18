<template>
  <div v-if="loading" class="space-y-4">
    <div class="skeleton h-56 rounded-2xl" />
    <div class="grid gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-4">
        <div class="skeleton h-32 rounded-xl" />
        <div class="skeleton h-64 rounded-xl" />
      </div>
      <div class="skeleton h-80 rounded-xl" />
    </div>
  </div>

  <EmptyState v-else-if="!course" title="الكورس غير متاح" subtitle="ربما لم يُفعّل على حسابك بعد أو الرابط غير صحيح." :icon="BookOpen" class="py-16">
    <RouterLink to="/dashboard/courses" class="btn-primary mt-4">العودة لكورساتي</RouterLink>
  </EmptyState>

  <div v-else class="space-y-6">
    <!-- Certificate Unlocked Banner -->
    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
      <div v-if="certificate || (course.certificate_enabled !== false && progress.percent >= 100)"
           class="relative overflow-hidden rounded-2xl border border-amber-400/40 bg-gradient-to-l from-amber-500/15 via-emerald-600/10 to-amber-500/10 p-5 sm:p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-700 text-white shadow-md ring-4 ring-amber-400/20">
              <Award :size="28" />
              <Sparkles :size="14" class="absolute -top-1 -right-1 text-amber-200 animate-pulse" />
            </div>
            <div>
              <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-bold text-emerald-700 mb-1">
                <CheckCircle2 :size="12" /> شهادة معتمدة جاهزة
              </div>
              <h3 class="text-base sm:text-lg font-bold text-ink">مبارك إتمام الكورس بنجاح! 🎓</h3>
              <p class="text-xs sm:text-sm text-ink-muted">تم توثيق إنجازك برقم اعتمادي رسمي، يمكنك استعراض الشهادة وتحميلها كـ PDF أو صورة بجودة فائقة.</p>
            </div>
          </div>
          <button class="btn-primary !bg-gradient-to-r !from-amber-600 !to-amber-700 hover:!from-amber-700 hover:!to-amber-800 text-white shadow-md flex items-center gap-2 !px-5 !py-2.5 text-sm font-bold"
                  @click="showCertModal = true">
            <Award :size="17" /> استعراض وتحميل الشهادة
          </button>
        </div>
      </div>
    </transition>

    <!-- Hero Card: Clean, modern, highly functional -->
    <div class="relative overflow-hidden rounded-2xl border border-line bg-white shadow-sm">
      <!-- Ambient subtle background glow -->
      <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-primary/5 blur-3xl pointer-events-none" />
      <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-amber-500/5 blur-3xl pointer-events-none" />

      <div class="relative p-6 sm:p-8">
        <!-- Breadcrumb & Status -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
          <nav class="flex items-center gap-2 text-xs font-medium text-ink-faint">
            <RouterLink to="/dashboard/courses" class="hover:text-primary transition-colors flex items-center gap-1">
              <BookOpen :size="13" /> كورساتي
            </RouterLink>
            <span>/</span>
            <span class="text-ink-muted truncate max-w-[240px] sm:max-w-md">{{ course.title }}</span>
          </nav>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-ink-soft">
            <span class="h-2 w-2 rounded-full" :class="progress.percent === 100 ? 'bg-emerald-500' : 'bg-primary'" />
            {{ progress.percent === 100 ? 'كورس مكتمل' : 'قيد التعلّم' }}
          </span>
        </div>

        <!-- Main Title & Meta -->
        <div class="grid gap-6 lg:grid-cols-12 lg:items-center">
          <div class="lg:col-span-8 space-y-3">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold leading-tight text-ink tracking-tight">
              {{ course.title }}
            </h1>
            <p v-if="course.short_description" class="text-sm leading-relaxed text-ink-muted">
              {{ course.short_description }}
            </p>

            <!-- Metadata pills -->
            <div class="flex flex-wrap items-center gap-2 pt-1 text-xs text-ink-soft font-medium">
              <span v-if="course.instructor?.name" class="inline-flex items-center gap-1.5 rounded-lg bg-surface px-2.5 py-1.5 border border-line/60">
                <UserCircle :size="15" class="text-primary" /> {{ course.instructor.name }}
              </span>
              <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface px-2.5 py-1.5 border border-line/60">
                <Layers :size="15" class="text-primary" /> {{ course.modules?.length || 0 }} وحدات
              </span>
              <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface px-2.5 py-1.5 border border-line/60">
                <PlayCircle :size="15" class="text-primary" /> {{ progress.total_lessons }} درسًا
              </span>
              <span v-if="totalDuration" class="inline-flex items-center gap-1.5 rounded-lg bg-surface px-2.5 py-1.5 border border-line/60">
                <Clock :size="15" class="text-primary" /> {{ totalDuration }}
              </span>
              <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface px-2.5 py-1.5 border border-line/60">
                <GraduationCap :size="15" class="text-primary" /> {{ levelLabel }}
              </span>
            </div>
          </div>

          <!-- Hero Action Side / Thumbnail -->
          <div class="lg:col-span-4 flex flex-col items-stretch sm:items-end justify-center">
            <div v-if="course.thumbnail" class="hidden lg:block w-full max-w-[260px] aspect-video rounded-xl overflow-hidden border border-line shadow-sm mb-4">
              <img :src="course.thumbnail" :alt="course.title" class="w-full h-full object-cover" />
            </div>

            <!-- Primary Action Button -->
            <RouterLink v-if="resumeTarget"
                        :to="`/dashboard/courses/${course.slug}/lessons/${resumeTarget.id}`"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-md hover:bg-primary-dark transition-all duration-200 hover:shadow-lg active:scale-[0.98]">
              <Play :size="16" class="fill-current" />
              <span>{{ progress.completed_lessons > 0 ? (progress.percent === 100 ? 'مراجعة أول درس' : 'متابعة التعلّم') : 'ابدأ أول درس الآن' }}</span>
            </RouterLink>
            <p v-if="resumeTarget && progress.completed_lessons > 0 && progress.percent < 100" class="text-[11px] text-ink-faint mt-2 text-center sm:text-start truncate max-w-full">
              الدرس التالي: <span class="text-ink font-semibold">{{ resumeTarget.title }}</span>
            </p>
          </div>
        </div>

        <!-- Course Progress Bar: Visual & Interactive Feedback -->
        <div class="mt-6 pt-6 border-t border-line/80">
          <div class="flex flex-wrap items-end justify-between gap-2 mb-2">
            <div>
              <span class="text-xs font-bold text-ink uppercase tracking-wider">تقدمك التعليمي</span>
              <p class="text-xs text-ink-faint mt-0.5">
                أكملت <span class="font-bold text-ink">{{ progress.completed_lessons }}</span> من إجمالي <span class="font-bold text-ink">{{ progress.total_lessons }}</span> درساً
              </p>
            </div>
            <div class="flex items-baseline gap-1 text-emerald-700 font-extrabold">
              <span class="text-2xl tracking-tight">{{ progress.percent }}</span>
              <span class="text-sm">%</span>
            </div>
          </div>

          <div class="relative h-3 w-full overflow-hidden rounded-full bg-stone-100 p-0.5 border border-line/60">
            <div class="h-full rounded-full bg-gradient-to-l from-emerald-500 via-primary to-teal-500 transition-all duration-700 ease-out relative"
                 :style="{ width: progress.percent + '%' }">
              <span v-if="progress.percent > 10" class="absolute inset-0 bg-white/20 animate-pulse rounded-full" />
            </div>
          </div>

          <!-- Quick milestone dots -->
          <div class="flex items-center justify-between text-[11px] text-ink-faint font-semibold mt-1.5 px-0.5">
            <span :class="{ 'text-primary': progress.percent >= 0 }">البداية</span>
            <span :class="{ 'text-primary font-bold': progress.percent >= 25 }">25%</span>
            <span :class="{ 'text-primary font-bold': progress.percent >= 50 }">منتصف الطريق</span>
            <span :class="{ 'text-primary font-bold': progress.percent >= 75 }">75%</span>
            <span class="flex items-center gap-0.5" :class="{ 'text-emerald-700 font-bold': progress.percent === 100 }">
              <Award :size="12" /> الشهادة
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats Cards Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
      <div class="card p-4 flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
          <CheckCircle2 :size="20" />
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-semibold text-ink-faint uppercase">الدروس المنجزة</p>
          <p class="text-lg font-bold text-ink">{{ progress.completed_lessons }}</p>
        </div>
      </div>

      <div class="card p-4 flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-faint text-primary">
          <PlayCircle :size="20" />
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-semibold text-ink-faint uppercase">المتبقي</p>
          <p class="text-lg font-bold text-ink">{{ Math.max(0, progress.total_lessons - progress.completed_lessons) }}</p>
        </div>
      </div>

      <div class="card p-4 flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
          <FolderKanban :size="20" />
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-semibold text-ink-faint uppercase">المشاريع</p>
          <p class="text-lg font-bold text-ink">{{ course.projects?.length || 0 }}</p>
        </div>
      </div>

      <div class="card p-4 flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-700">
          <Award :size="20" />
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-semibold text-ink-faint uppercase">حالة الشهادة</p>
          <p class="text-xs font-bold truncate" :class="progress.percent >= 100 ? 'text-emerald-700' : 'text-ink'">
            {{ progress.percent >= 100 ? 'مستحقة 🎓' : 'عند 100%' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Main Content & Curriculum -->
    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Curriculum Column -->
      <div class="lg:col-span-2 space-y-4">
        <!-- Curriculum Controls Bar -->
        <div class="flex items-center justify-between px-1">
          <div class="flex items-center gap-2">
            <h2 class="text-base font-bold text-ink">محتوى الكورس والمنهج</h2>
            <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-ink-muted">
              {{ course.modules?.length || 0 }} وحدات
            </span>
          </div>
          <button class="text-xs font-semibold text-primary hover:text-primary-dark transition-colors"
                  @click="toggleAllModules">
            {{ allExpanded ? 'طيّ الكل' : 'توسيع الكل' }}
          </button>
        </div>

        <!-- Modules Accordion List -->
        <div v-for="(mod, modIndex) in course.modules" :key="mod.id"
             class="card overflow-hidden transition-all duration-200 hover:border-line/80">
          <!-- Module Header -->
          <div class="cursor-pointer bg-surface/80 hover:bg-surface px-5 py-4 transition-colors select-none"
               @click="toggleModule(mod.id)">
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold transition-all shadow-xs"
                      :class="moduleProgress(mod).percent === 100 ? 'bg-emerald-600 text-white' : 'bg-white border border-line text-ink'">
                  <Check v-if="moduleProgress(mod).percent === 100" :size="14" />
                  <template v-else>{{ modIndex + 1 }}</template>
                </span>
                <div class="min-w-0">
                  <h3 class="text-sm sm:text-base font-bold text-ink truncate">
                    {{ mod.title }}
                  </h3>
                  <p v-if="mod.description" class="text-xs text-ink-muted line-clamp-1 mt-0.5">
                    {{ mod.description }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-3 shrink-0">
                <div class="text-end hidden sm:block">
                  <span class="text-xs font-bold" :class="moduleProgress(mod).percent === 100 ? 'text-emerald-700' : 'text-ink-muted'">
                    {{ moduleProgress(mod).done }}/{{ moduleProgress(mod).total }} درس
                  </span>
                  <div class="w-20 h-1.5 overflow-hidden rounded-full bg-stone-200 mt-1">
                    <div class="h-full rounded-full bg-emerald-600 transition-all duration-500"
                         :style="{ width: moduleProgress(mod).percent + '%' }" />
                  </div>
                </div>
                <ChevronDown :size="18" class="text-ink-muted transition-transform duration-200"
                             :class="{ 'rotate-180': isExpanded(mod.id) }" />
              </div>
            </div>
          </div>

          <!-- Module Lessons List -->
          <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
            <ul v-show="isExpanded(mod.id)" class="divide-y divide-line/60 bg-white">
              <li v-for="row in rows(mod)" :key="row.key">
                <!-- Lesson Row -->
                <RouterLink v-if="row.lesson"
                            :to="`/dashboard/courses/${course.slug}/lessons/${row.lesson.id}`"
                            class="group flex items-center gap-3.5 px-5 py-3.5 transition-all duration-150 hover:bg-stone-50"
                            :class="{ 'bg-primary-faint/30': isNextLesson(row.lesson) }">
                  <!-- Lesson Status Icon -->
                  <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-bold transition-transform duration-150 group-hover:scale-105"
                        :class="row.lesson.user_progress?.completed
                          ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                          : (isNextLesson(row.lesson) ? 'bg-primary text-white shadow-xs' : 'bg-surface text-ink-muted border border-line')">
                    <CheckCircle2 v-if="row.lesson.user_progress?.completed" :size="16" />
                    <Play v-else-if="isNextLesson(row.lesson)" :size="13" class="fill-current translate-x-[-0.5px]" />
                    <template v-else>{{ row.num }}</template>
                  </span>

                  <!-- Title and Info -->
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <p class="text-sm font-semibold text-ink group-hover:text-primary transition-colors truncate">
                        {{ row.lesson.title }}
                      </p>
                      <span v-if="isNextLesson(row.lesson)" class="badge-blue !text-[10px] !py-0 shrink-0">
                        الدرس التالي
                      </span>
                    </div>

                    <div class="flex items-center gap-3 mt-1 text-[11px] text-ink-faint">
                      <span v-if="row.lesson.duration_seconds" class="flex items-center gap-1">
                        <Clock :size="12" /> {{ formatDuration(row.lesson.duration_seconds) }}
                      </span>
                      <span v-if="row.lesson.user_progress?.last_position && !row.lesson.user_progress?.completed" class="text-primary font-medium">
                        توقفت عند {{ formatDuration(row.lesson.user_progress.last_position) }}
                      </span>
                      <span v-if="row.lesson.quiz" class="flex items-center gap-1 text-purple-700 font-medium">
                        <HelpCircle :size="12" /> اختبار مرفق
                      </span>
                    </div>
                  </div>

                  <!-- Badges / Pill -->
                  <div class="flex items-center gap-2 shrink-0">
                    <span v-if="row.lesson.is_preview" class="badge-green">معاينة مجانية</span>
                    <span v-if="!row.lesson.has_video" class="badge-amber">نصي</span>
                    <span class="text-xs text-ink-faint opacity-0 group-hover:opacity-100 transition-opacity hidden sm:inline-flex items-center gap-1 text-primary">
                      فتح الدرس <ChevronLeft :size="14" />
                    </span>
                  </div>
                </RouterLink>

                <!-- Project / Activity Row -->
                <div v-else class="flex items-center gap-3.5 px-5 py-3.5 transition-colors select-none"
                     :class="activityUnlocked(row.project, mod)
                       ? 'cursor-pointer bg-amber-500/5 hover:bg-amber-500/10'
                       : 'opacity-60 bg-stone-50 cursor-not-allowed'"
                     @click="activityUnlocked(row.project, mod) && openProject(row.project)">
                  <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs"
                        :class="mySubmission(row.project)
                          ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                          : (activityUnlocked(row.project, mod) ? 'bg-amber-100 text-amber-800' : 'bg-stone-100 text-ink-faint')">
                    <CheckCircle2 v-if="mySubmission(row.project)" :size="16" />
                    <Lock v-else-if="!activityUnlocked(row.project, mod)" :size="14" />
                    <FolderKanban v-else :size="15" />
                  </span>

                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <p class="text-sm font-semibold text-ink truncate">{{ row.project.title }}</p>
                      <span class="badge-amber !text-[10px] !py-0 shrink-0">مشروع عملي</span>
                    </div>
                    <p v-if="!activityUnlocked(row.project, mod)" class="text-[11px] text-ink-faint mt-0.5">
                      يُفتح بعد إكمال {{ row.project.after_lesson_id ? 'الدرس السابق' : 'جميع دروس الوحدة' }}
                    </p>
                    <p v-else-if="mySubmission(row.project)?.feedback" class="text-[11px] text-ink-muted mt-0.5 truncate">
                      ملاحظة المدرب: {{ mySubmission(row.project).feedback }}
                    </p>
                    <p v-else class="text-[11px] text-emerald-700 font-medium mt-0.5">
                      جاهز للتسليم الآن
                    </p>
                  </div>

                  <div class="shrink-0">
                    <StatusBadge v-if="mySubmission(row.project)" :status="mySubmission(row.project).status" />
                    <button v-else-if="activityUnlocked(row.project, mod)" class="btn-outline !py-1 !px-2.5 !text-xs">
                      تسليم
                    </button>
                  </div>
                </div>
              </li>

              <li v-if="!mod.lessons?.length && !moduleProjects(mod)?.length" class="px-5 py-6 text-center text-xs text-ink-faint">
                لا توجد دروس مضافة في هذه الوحدة بعد.
              </li>
            </ul>
          </transition>
        </div>
      </div>

      <!-- Sidebar: Projects, Quizzes, Course Instructor -->
      <aside class="space-y-5">
        <!-- Instructor Card -->
        <div v-if="course.instructor" class="card p-5">
          <h3 class="text-xs font-bold text-ink-faint uppercase tracking-wider mb-3">مدرب الكورس</h3>
          <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-full overflow-hidden bg-primary-faint text-primary flex items-center justify-center font-bold text-base shrink-0 border border-line">
              <img v-if="course.instructor.avatar" :src="course.instructor.avatar" :alt="course.instructor.name" class="h-full w-full object-cover" />
              <span v-else>{{ course.instructor.name?.charAt(0) }}</span>
            </div>
            <div class="min-w-0">
              <h4 class="text-sm font-bold text-ink truncate">{{ course.instructor.name }}</h4>
              <p class="text-xs text-ink-muted">مدرب معتمد في أكاديمية وافيكسا</p>
            </div>
          </div>
        </div>

        <!-- Standalone Course Projects -->
        <div v-if="courseProjects.length" class="card p-5 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="flex items-center gap-2 text-sm font-bold text-ink">
              <FolderKanban :size="16" class="text-primary" /> مشاريع الكورس
            </h3>
            <span class="badge-gray">{{ courseProjects.length }}</span>
          </div>

          <div class="space-y-2.5">
            <div v-for="p in courseProjects" :key="p.id" class="rounded-xl border border-line p-3.5 bg-surface/40 hover:bg-surface transition-colors">
              <p class="text-sm font-bold text-ink">{{ p.title }}</p>
              <p v-if="p.description" class="mt-1 line-clamp-2 text-xs text-ink-muted">{{ p.description }}</p>
              <div class="mt-3 flex items-center justify-between gap-2">
                <StatusBadge v-if="mySubmission(p)" :status="mySubmission(p).status" />
                <span v-else class="text-[11px] text-ink-faint">مشروع ختامي</span>
                <button class="btn-primary !py-1 !px-3 !text-xs" @click="openProject(p)">
                  {{ mySubmission(p) ? 'عرض التسليم' : 'تسليم المشروع' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Course Quizzes -->
        <div v-if="course.quizzes?.length" class="card p-5 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="flex items-center gap-2 text-sm font-bold text-ink">
              <FileQuestion :size="16" class="text-primary" /> الاختبارات والتقييمات
            </h3>
            <span class="badge-gray">{{ course.quizzes.length }}</span>
          </div>

          <div class="space-y-2">
            <div v-for="q in course.quizzes" :key="q.id" class="flex items-center justify-between gap-2 rounded-xl border border-line p-3 bg-surface/40">
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-ink">{{ q.title }}</p>
                <p class="text-[11px] text-ink-faint mt-0.5">النجاح من {{ q.passing_score }}% · {{ q.max_attempts }} محاولات</p>
              </div>
              <RouterLink :to="`/dashboard/quizzes/${q.id}`" class="btn-outline shrink-0 !px-3 !py-1 !text-xs font-semibold">
                بدء
              </RouterLink>
            </div>
          </div>
        </div>

        <!-- Certificate Status Widget -->
        <div class="card p-5 bg-gradient-to-br from-white to-surface border border-line">
          <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center shrink-0">
              <Award :size="20" />
            </div>
            <div>
              <h4 class="text-sm font-bold text-ink">شهادة إتمام معتمدة</h4>
              <p class="text-xs text-ink-muted">معتمدة برقم توثيقي من Wavixa</p>
            </div>
          </div>
          <p class="text-xs text-ink-muted leading-relaxed">
            عند إكمال 100% من دروس واختبارات الكورس، سيتم إصدار شهادتك تلقائيًا باللغتين العربية والإنجليزية.
          </p>
          <div class="mt-4 pt-3 border-t border-line/60 flex items-center justify-between">
            <span class="text-xs font-semibold text-ink-muted">الاستحقاق:</span>
            <span class="text-xs font-bold" :class="progress.percent >= 100 ? 'text-emerald-700' : 'text-amber-700'">
              {{ progress.percent >= 100 ? 'مستحق الآن ✅' : `متبقي ${100 - progress.percent}%` }}
            </span>
          </div>
        </div>
      </aside>
    </div>

    <!-- Project Submission Modal -->
    <Modal :open="!!activeProject" :title="activeProject?.title || ''" @close="activeProject = null">
      <div class="space-y-4">
        <p v-if="activeProject?.description" class="whitespace-pre-line text-sm text-ink-soft">{{ activeProject.description }}</p>
        <p v-if="activeProject?.instructions" class="whitespace-pre-line rounded-lg bg-surface p-3 text-sm text-ink-muted">{{ activeProject.instructions }}</p>

        <!-- Attached media from instructor -->
        <div v-if="activeProject?.attachment_type === 'image' && activeProject?.attachment_url" class="overflow-hidden rounded-lg border border-line">
          <img :src="activeProject.attachment_url" :alt="activeProject.title" class="w-full" />
        </div>
        <video v-else-if="activeProject?.attachment_type === 'video' && activeProject?.attachment_url"
               :src="activeProject.attachment_url" controls class="w-full rounded-lg border border-line bg-black" />
        <a v-else-if="activeProject?.attachment_type === 'link' && activeProject?.attachment_url"
           :href="activeProject.attachment_url" target="_blank" rel="noopener"
           class="flex items-center gap-2 rounded-lg border border-line p-3 text-sm text-primary hover:bg-primary-faint">
          <ExternalLink :size="15" /> فتح رابط المرفق
        </a>

        <div v-if="mySubmission(activeProject)" class="rounded-lg bg-surface p-3 text-sm">
          <div class="flex items-center gap-2">
            <span class="font-semibold text-ink">تسليمك الحالي:</span>
            <StatusBadge :status="mySubmission(activeProject).status" />
          </div>
          <p v-if="mySubmission(activeProject).feedback" class="mt-1.5 text-xs text-ink-muted">
            ملاحظة المدرب: {{ mySubmission(activeProject).feedback }}
          </p>
        </div>

        <FormField label="رابط المشروع (GitHub / Live Demo)" :error="errors.link_url?.[0]">
          <input v-model="submission.link_url" class="input" dir="ltr" placeholder="https://..." />
        </FormField>
        <FormField label="ملف التسليم (اختياري)" :error="errors.file?.[0]" hint="صورة / فيديو / ZIP / PDF — بحد أقصى 200MB">
          <input type="file" class="input !py-2" accept="image/*,video/*,.zip,.pdf,.txt,.md" @change="(e) => submission.file = e.target.files[0]" />
        </FormField>
        <FormField label="ملاحظات أو تعليق للتسليم" :error="errors.notes?.[0]">
          <textarea v-model="submission.notes" rows="3" class="input" placeholder="اكتب ملاحظاتك للمدرب…" />
        </FormField>
        <button class="btn-primary w-full !py-2.5" :disabled="submitting" @click="submitProject">
          <Loader2 v-if="submitting" :size="16" class="animate-spin" /> {{ mySubmission(activeProject) ? 'إعادة الإرسال' : 'تأكيد التسليم' }}
        </button>
      </div>
    </Modal>

    <!-- Certificate Modal -->
    <Modal :open="showCertModal" :title="course.certificate_title || course.title || 'شهادة إتمام الكورس'" max-width="max-w-5xl" @close="showCertModal = false">
      <div class="py-2">
        <WavixaCertificate
          :student-name="auth.user?.name"
          :course-title="course.title"
          :certificate-title="course.certificate_title"
          :instructor-name="course.instructor?.name"
          :certificate-number="certificate?.number || ('WXA-' + new Date().getFullYear() + '-' + String(course.id).padStart(6, '0'))"
          :issued-date="certificate?.issued_at ? new Date(certificate.issued_at).toLocaleDateString('ar-EG') : ''"
          :verification-token="certificate?.verification_token"
          :verify-url="certificate?.verify_url"
          :default-lang="course.certificate_language === 'en' ? 'en' : 'ar'"
        />
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { unwrapError } from '../../api/client';
import { auth } from '../../stores/auth';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import FormField from '../../components/FormField.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import EmptyState from '../../components/EmptyState.vue';
import WavixaCertificate from '../../components/WavixaCertificate.vue';
import {
  PlayCircle, CheckCircle2, FolderKanban, FileQuestion, Loader2, Lock,
  ExternalLink, GraduationCap, UserCircle, Layers, Clock, BookOpen, Award,
  ChevronDown, ChevronLeft, Check, Sparkles, Play, HelpCircle
} from '@lucide/vue';

const route = useRoute();
const course = ref(null);
const progress = ref({ percent: 0, completed_lessons: 0, total_lessons: 0 });
const certificate = ref(null);
const showCertModal = ref(false);
const loading = ref(true);

const expandedModules = ref(new Set());
const activeProject = ref(null);
const submission = ref({ link_url: '', notes: '', file: null });
const errors = ref({});
const submitting = ref(false);

const courseProjects = computed(() => (course.value?.projects || []).filter((p) => !p.module_id));

const levelLabel = computed(() => ({
  beginner: 'مبتدئ', intermediate: 'متوسط', advanced: 'متقدم', all_levels: 'كل المستويات',
}[course.value?.level] || 'كل المستويات'));

const totalDuration = computed(() => {
  const s = (course.value?.modules || [])
    .flatMap((m) => m.lessons || [])
    .reduce((sum, l) => sum + (l.duration_seconds || 0), 0);
  if (!s) return '';
  const h = Math.floor(s / 3600);
  const m = Math.round((s % 3600) / 60);
  return h ? `${h}س ${m}د` : `${m}د`;
});

// The next incomplete lesson in curriculum order
const nextLesson = computed(() => {
  for (const mod of course.value?.modules || []) {
    const l = (mod.lessons || []).find((x) => !x.user_progress?.completed);
    if (l) return l;
  }
  return null;
});

// First lesson if all are completed or no progress
const resumeTarget = computed(() => {
  if (nextLesson.value) return nextLesson.value;
  const firstMod = course.value?.modules?.[0];
  return firstMod?.lessons?.[0] || null;
});

function isNextLesson(lesson) {
  return nextLesson.value?.id === lesson.id;
}

function formatDuration(s) {
  if (!s) return '0:00';
  return `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
}

function moduleProgress(mod) {
  const lessons = mod.lessons || [];
  const total = lessons.length;
  const done = lessons.filter((l) => l.user_progress?.completed).length;
  return { done, total, percent: total ? Math.round((done / total) * 100) : 0 };
}

function moduleProjects(mod) {
  return (course.value?.projects || []).filter((p) => p.module_id === mod.id);
}

// Module Accordion
function isExpanded(modId) {
  return expandedModules.value.has(modId);
}

function toggleModule(modId) {
  if (expandedModules.value.has(modId)) {
    expandedModules.value.delete(modId);
  } else {
    expandedModules.value.add(modId);
  }
}

const allExpanded = computed(() => {
  const mods = course.value?.modules || [];
  return mods.length > 0 && mods.every((m) => expandedModules.value.has(m.id));
});

function toggleAllModules() {
  const mods = course.value?.modules || [];
  if (allExpanded.value) {
    expandedModules.value.clear();
  } else {
    mods.forEach((m) => expandedModules.value.add(m.id));
  }
}

// Interleave lessons and activities in display order
function rows(mod) {
  const out = [];
  const lessons = mod.lessons || [];
  const lessonIds = new Set(lessons.map((l) => l.id));
  let num = 0;
  for (const lesson of lessons) {
    out.push({ key: `l${lesson.id}`, lesson, num: ++num });
    for (const p of moduleProjects(mod).filter((x) => x.after_lesson_id === lesson.id)) {
      out.push({ key: `p${p.id}`, project: p });
    }
  }
  for (const p of moduleProjects(mod).filter((x) => !x.after_lesson_id || !lessonIds.has(x.after_lesson_id))) {
    out.push({ key: `p${p.id}`, project: p });
  }
  return out;
}

function activityUnlocked(p, mod) {
  const lessons = mod.lessons || [];
  if (p.after_lesson_id) {
    const l = lessons.find((x) => x.id === p.after_lesson_id);
    if (l) return !!l.user_progress?.completed;
  }
  return lessons.length > 0 && lessons.every((l) => l.user_progress?.completed);
}

function mySubmission(p) {
  return p?.submissions?.[0] || null;
}

function openProject(p) {
  activeProject.value = p;
  submission.value = { link_url: '', notes: '', file: null };
  errors.value = {};
}

async function submitProject() {
  submitting.value = true;
  errors.value = {};
  try {
    const fd = new FormData();
    if (submission.value.link_url) fd.append('link_url', submission.value.link_url);
    if (submission.value.notes) fd.append('notes', submission.value.notes);
    if (submission.value.file) fd.append('file', submission.value.file);
    const { data } = await api.post(`/projects/${activeProject.value.id}/submit`, fd);
    activeProject.value.submissions = [data.submission];
    toast('تم إرسال تسليمك للمراجعة.');
    activeProject.value = null;
  } catch (e) {
    const err = unwrapError(e);
    if (typeof err === 'object') errors.value = err;
    else toast(err, 'error');
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/me/courses/${route.params.slug}`);
    course.value = data.course;
    progress.value = data.progress;
    certificate.value = data.certificate || null;

    // Automatically expand the module containing the next lesson, or the first module
    const target = nextLesson.value;
    if (target) {
      const activeMod = (course.value.modules || []).find((m) =>
        (m.lessons || []).some((l) => l.id === target.id)
      );
      if (activeMod) {
        expandedModules.value.add(activeMod.id);
      }
    }
    if (expandedModules.value.size === 0 && course.value?.modules?.length) {
      expandedModules.value.add(course.value.modules[0].id);
    }
  } catch {
    course.value = null;
  } finally {
    loading.value = false;
  }
});
</script>
