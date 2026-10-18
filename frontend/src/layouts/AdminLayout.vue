<template>
  <div class="admin-scope min-h-screen bg-surface flex">
    <!-- Sidebar (desktop) -->
    <aside class="hidden lg:flex w-64 shrink-0 flex-col bg-ink text-slate-300 fixed inset-y-0 start-0">
      <div class="flex h-16 items-center gap-2.5 px-5 border-b border-white/10">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-ink font-bold text-xs">W</div>
        <div>
          <p class="text-sm font-bold text-white leading-tight">{{ settings.platform_name }}</p>
          <p class="text-[11px] text-slate-400">لوحة الإدارة</p>
        </div>
      </div>
      <nav class="flex-1 overflow-y-auto p-3 space-y-4 text-sm">
        <div v-for="group in menu" :key="group.label">
          <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ group.label }}</p>
          <RouterLink v-for="item in group.items" :key="item.to" :to="item.to"
            class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium hover:bg-white/10 hover:text-white transition-colors"
            active-class="!bg-primary !text-white" :end="item.to === '/admin'">
            <component :is="item.icon" :size="17" />
            {{ item.label }}
          </RouterLink>
        </div>
      </nav>
      <div class="border-t border-white/10 p-3 space-y-0.5">
        <RouterLink to="/dashboard" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-white/10">
          <GraduationCap :size="17" /> لوحة الطالب
        </RouterLink>
        <RouterLink to="/" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-white/10">
          <Globe :size="17" /> الموقع العام
        </RouterLink>
        <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-300 hover:bg-white/10" @click="doLogout">
          <LogOut :size="17" /> تسجيل الخروج
        </button>
      </div>
    </aside>

    <div class="flex-1 lg:ms-64 flex flex-col min-w-0">
      <header class="sticky top-0 z-40 flex h-16 items-center gap-3 border-b border-line bg-white/95 backdrop-blur px-4 sm:px-6">
        <button class="lg:hidden btn-ghost !p-2" @click="mobileNav = true"><Menu :size="20" /></button>
        <h1 class="text-sm font-bold text-ink">لوحة الإدارة</h1>
        <div class="flex-1" />
        <span class="badge-blue hidden sm:inline-flex">{{ roleLabel }}</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-ink text-white text-sm font-bold">{{ initials }}</div>
      </header>
      <main class="flex-1 p-4 sm:p-6 w-full max-w-7xl mx-auto">
        <router-view />
      </main>
    </div>

    <!-- Mobile drawer -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="mobileNav" class="fixed inset-0 z-[80] bg-ink/40 lg:hidden" @click="mobileNav = false" />
      </Transition>
      <Transition name="drawer">
        <aside v-if="mobileNav" class="admin-scope fixed inset-y-0 start-0 z-[81] w-72 bg-ink text-slate-300 flex flex-col lg:hidden">
          <div class="flex h-16 items-center justify-between px-5 border-b border-white/10">
            <span class="font-bold text-white">لوحة الإدارة</span>
            <button class="text-slate-400 hover:text-white" @click="mobileNav = false"><X :size="20" /></button>
          </div>
          <nav class="flex-1 overflow-y-auto p-3 space-y-4 text-sm">
            <div v-for="group in menu" :key="group.label">
              <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ group.label }}</p>
              <RouterLink v-for="item in group.items" :key="item.to" :to="item.to"
                class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium hover:bg-white/10"
                active-class="!bg-primary !text-white" :end="item.to === '/admin'"
                @click="mobileNav = false">
                <component :is="item.icon" :size="17" /> {{ item.label }}
              </RouterLink>
            </div>
          </nav>
          <div class="border-t border-white/10 p-3">
            <RouterLink to="/" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-white/10" @click="mobileNav = false"><Globe :size="17" /> الموقع العام</RouterLink>
            <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-300 hover:bg-white/10" @click="doLogout"><LogOut :size="17" /> تسجيل الخروج</button>
          </div>
        </aside>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { auth, logout } from '../stores/auth';
import { settings, loadSettings } from '../stores/settings';
import {
  LayoutDashboard, Users, BookOpen, KeyRound, FolderKanban, HelpCircle,
  CalendarDays, Award, Megaphone, ShieldAlert, ScrollText, Settings,
  LogOut, Menu, X, Globe, GraduationCap, FileQuestion, Video, QrCode,
  UserPlus,
} from '@lucide/vue';

const router = useRouter();
const mobileNav = ref(false);

const menu = [
  {
    label: 'عام',
    items: [
      { to: '/admin', label: 'لوحة المتابعة', icon: LayoutDashboard },
    ],
  },
  {
    label: 'التعلم',
    items: [
      { to: '/admin/students', label: 'الطلاب', icon: Users },
      { to: '/admin/instructors', label: 'المدربون', icon: GraduationCap },
      { to: '/admin/instructors/create', label: 'إضافة مدرب', icon: UserPlus },
      { to: '/admin/courses', label: 'الكورسات', icon: BookOpen },
      { to: '/admin/video-imports', label: 'استيراد الفيديو', icon: Video },
      { to: '/admin/enrollments', label: 'الاشتراكات', icon: KeyRound },
    ],
  },
  {
    label: 'التقييم',
    items: [
      { to: '/admin/submissions', label: 'تسليمات المشاريع', icon: FolderKanban },
      { to: '/admin/quizzes', label: 'الاختبارات', icon: FileQuestion },
    ],
  },
  {
    label: 'الفعاليات',
    items: [
      { to: '/admin/events', label: 'الفعاليات', icon: CalendarDays },
      { to: '/admin/checkin', label: 'تسجيل الحضور', icon: QrCode },
    ],
  },
  {
    label: 'المنصة',
    items: [
      { to: '/admin/certificates', label: 'الشهادات', icon: Award },
      { to: '/admin/announcements', label: 'الإعلانات', icon: Megaphone },
      { to: '/admin/faqs', label: 'الأسئلة الشائعة', icon: HelpCircle },
    ],
  },
  {
    label: 'الأمان',
    items: [
      { to: '/admin/security', label: 'مركز الأمان', icon: ShieldAlert },
      { to: '/admin/audit-logs', label: 'سجل العمليات', icon: ScrollText },
      { to: '/admin/settings', label: 'الإعدادات', icon: Settings },
    ],
  },
];

const initials = computed(() => (auth.user?.name || '؟').trim().charAt(0));
const roleLabel = computed(() => ({
  super_admin: 'مدير عام', content_manager: 'مدير محتوى', instructor: 'مدرب', support: 'دعم',
}[auth.user?.roles?.[0]] || 'مشرف'));

async function doLogout() {
  await logout();
  router.push('/');
}

onMounted(() => loadSettings());
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.drawer-enter-active, .drawer-leave-active { transition: transform .25s ease; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }
</style>
