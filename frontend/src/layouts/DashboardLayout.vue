<template>
  <div class="min-h-screen bg-surface flex">
    <!-- Sidebar (desktop) -->
    <aside class="hidden lg:flex w-64 shrink-0 flex-col border-e border-line bg-white fixed inset-y-0 start-0">
      <div class="flex h-16 items-center gap-2.5 px-5 border-b border-line">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink text-white font-bold text-xs">W</div>
        <span class="font-bold text-ink">{{ settings.platform_name }}</span>
      </div>
      <nav class="flex-1 overflow-y-auto p-3 space-y-0.5">
        <RouterLink v-for="item in menu" :key="item.to" :to="item.to"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink-soft hover:bg-stone-100 transition-colors"
          active-class="!bg-primary-faint !text-primary" :end="item.to === '/dashboard'">
          <component :is="item.icon" :size="18" />
          {{ item.label }}
        </RouterLink>
      </nav>
      <div class="border-t border-line p-3">
        <RouterLink to="/" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink-muted hover:bg-stone-100">
          <Globe :size="18" /> الموقع العام
        </RouterLink>
        <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-danger hover:bg-danger-soft" @click="doLogout">
          <LogOut :size="18" /> تسجيل الخروج
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 lg:ms-64 flex flex-col min-w-0">
      <!-- Topbar -->
      <header class="sticky top-0 z-40 flex h-16 items-center gap-3 border-b border-line bg-white/95 backdrop-blur px-4 sm:px-6">
        <button class="lg:hidden btn-ghost !p-2" @click="mobileNav = true" aria-label="menu"><Menu :size="20" /></button>
        <div class="flex-1 min-w-0">
          <p class="text-sm text-ink-faint">أهلًا بك،</p>
          <h1 class="text-sm font-bold text-ink -mt-0.5 truncate">{{ auth.user?.name }}</h1>
        </div>
        <RouterLink to="/dashboard/notifications" class="relative btn-ghost !p-2.5" aria-label="notifications">
          <Bell :size="19" />
          <span v-if="unread > 0" class="absolute top-1.5 end-1.5 flex h-2 w-2 rounded-full bg-danger" />
        </RouterLink>
        <RouterLink to="/dashboard/profile" class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-faint text-primary font-bold text-sm">
          {{ initials }}
        </RouterLink>
      </header>

      <main class="flex-1 p-4 sm:p-6 max-w-6xl w-full mx-auto">
        <router-view />
      </main>
    </div>

    <!-- Mobile nav drawer -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="mobileNav" class="fixed inset-0 z-[80] bg-ink/40 lg:hidden" @click="mobileNav = false" />
      </Transition>
      <Transition name="drawer">
        <aside v-if="mobileNav" class="fixed inset-y-0 start-0 z-[81] w-72 bg-white flex flex-col lg:hidden">
          <div class="flex h-16 items-center justify-between px-5 border-b border-line">
            <span class="font-bold text-ink">{{ settings.platform_name }}</span>
            <button class="btn-ghost !p-2" @click="mobileNav = false"><X :size="20" /></button>
          </div>
          <nav class="flex-1 overflow-y-auto p-3 space-y-0.5">
            <RouterLink v-for="item in menu" :key="item.to" :to="item.to"
              class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink-soft hover:bg-stone-100"
              active-class="!bg-primary-faint !text-primary" :end="item.to === '/dashboard'"
              @click="mobileNav = false">
              <component :is="item.icon" :size="18" /> {{ item.label }}
            </RouterLink>
          </nav>
          <div class="border-t border-line p-3 space-y-0.5">
            <RouterLink to="/" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink-muted hover:bg-stone-100" @click="mobileNav = false">
              <Globe :size="18" /> الموقع العام
            </RouterLink>
            <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-danger hover:bg-danger-soft" @click="doLogout">
              <LogOut :size="18" /> تسجيل الخروج
            </button>
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
import api from '../api/client';
import {
  LayoutDashboard, BookOpen, CalendarDays, Award, Bell, UserCircle,
  LogOut, Menu, X, Globe,
} from '@lucide/vue';

const router = useRouter();
const mobileNav = ref(false);
const unread = ref(0);

const menu = [
  { to: '/dashboard', label: 'الرئيسية', icon: LayoutDashboard },
  { to: '/dashboard/courses', label: 'كورساتي', icon: BookOpen },
  { to: '/dashboard/events', label: 'فعالياتي', icon: CalendarDays },
  { to: '/dashboard/certificates', label: 'شهاداتي', icon: Award },
  { to: '/dashboard/notifications', label: 'الإشعارات', icon: Bell },
  { to: '/dashboard/profile', label: 'حسابي', icon: UserCircle },
];

const initials = computed(() => (auth.user?.name || '؟').trim().charAt(0));

async function doLogout() {
  await logout();
  router.push('/');
}

onMounted(async () => {
  loadSettings();
  try {
    const { data } = await api.get('/me/notifications');
    unread.value = data.unread || 0;
  } catch { /* ignore */ }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.drawer-enter-active, .drawer-leave-active { transition: transform .25s ease; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }
</style>
