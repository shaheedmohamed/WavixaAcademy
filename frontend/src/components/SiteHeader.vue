<template>
  <header class="sticky top-0 z-50 border-b border-line bg-white/95 backdrop-blur">
    <div class="container-site flex h-16 items-center justify-between gap-4">
      <RouterLink to="/" class="flex items-center gap-2.5 shrink-0">
        <img v-if="settings.logo" :src="settings.logo" alt="logo" class="h-8 w-auto" />
        <div v-else class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink text-white font-bold text-sm">W</div>
        <span class="text-lg font-bold text-ink hidden sm:block">{{ settings.platform_name }}</span>
      </RouterLink>

      <nav class="hidden lg:flex items-center gap-1 text-sm font-medium text-ink-soft">
        <RouterLink v-for="l in links" :key="l.to" :to="l.to" class="rounded-lg px-3 py-2 hover:bg-stone-100 hover:text-ink transition-colors" active-class="!text-primary bg-primary-faint">
          {{ l.label }}
        </RouterLink>
      </nav>

      <div class="flex items-center gap-2">
        <template v-if="auth.user">
          <RouterLink :to="auth.user.is_admin ? '/admin' : '/dashboard'" class="btn-primary !py-2">
            <LayoutDashboard :size="16" />
            <span class="hidden sm:inline">{{ auth.user.is_admin ? $t('nav.admin') : $t('nav.dashboard') }}</span>
          </RouterLink>
        </template>
        <template v-else>
          <RouterLink to="/login" class="btn-ghost !py-2 hidden sm:inline-flex">{{ $t('nav.login') }}</RouterLink>
          <RouterLink to="/register" class="btn-primary !py-2">{{ $t('nav.register') }}</RouterLink>
        </template>
        <button class="lg:hidden btn-ghost !p-2" @click="mobileOpen = !mobileOpen" aria-label="menu">
          <Menu v-if="!mobileOpen" :size="22" />
          <X v-else :size="22" />
        </button>
      </div>
    </div>

    <Transition name="slide">
      <nav v-if="mobileOpen" class="lg:hidden border-t border-line bg-white">
        <div class="container-site py-3 flex flex-col">
          <RouterLink v-for="l in links" :key="l.to" :to="l.to"
            class="rounded-lg px-3 py-2.5 text-sm font-medium text-ink-soft hover:bg-stone-100"
            active-class="!text-primary bg-primary-faint"
            @click="mobileOpen = false">
            {{ l.label }}
          </RouterLink>
          <RouterLink v-if="!auth.user" to="/login" class="rounded-lg px-3 py-2.5 text-sm font-medium text-ink-soft hover:bg-stone-100" @click="mobileOpen = false">
            {{ $t('nav.login') }}
          </RouterLink>
        </div>
      </nav>
    </Transition>
  </header>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { auth } from '../stores/auth';
import { settings, loadSettings } from '../stores/settings';
import { LayoutDashboard, Menu, X } from '@lucide/vue';

const { t } = useI18n();
const mobileOpen = ref(false);

const links = [
  { to: '/', label: t('nav.home') },
  { to: '/courses', label: t('nav.courses') },
  { to: '/events', label: t('nav.events') },
  { to: '/instructors', label: t('nav.instructors') },
  { to: '/about', label: t('nav.about') },
  { to: '/faq', label: t('nav.faq') },
  { to: '/contact', label: t('nav.contact') },
];

onMounted(() => loadSettings());
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all .2s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
