<template>
  <div>
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-ink">الإشعارات</h1>
        <p class="mt-1 text-sm text-ink-muted">{{ unread }} غير مقروءة</p>
      </div>
      <button v-if="unread > 0" class="btn-outline !text-xs" @click="markAll">تحديد الكل كمقروء</button>
    </div>

    <div v-if="loading" class="mt-6 space-y-3"><div v-for="i in 4" :key="i" class="card skeleton h-16" /></div>

    <div v-else-if="notifications.data?.length" class="mt-6 space-y-2.5">
      <div v-for="n in notifications.data" :key="n.id"
        class="card flex items-start gap-3 p-4"
        :class="{ 'border-primary/30 bg-primary-faint/40': !n.read_at }">
        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="n.read_at ? 'bg-line' : 'bg-primary'" />
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-ink">{{ n.data.title }}</p>
          <p class="mt-0.5 text-sm text-ink-muted">{{ n.data.body }}</p>
          <p class="mt-1 text-[11px] text-ink-faint">{{ new Date(n.created_at).toLocaleString('ar-EG') }}</p>
        </div>
        <RouterLink v-if="n.data.url" :to="n.data.url" class="btn-ghost !px-2.5 !py-1.5 shrink-0"><ExternalLink :size="15" /></RouterLink>
      </div>
      <Pagination :data="notifications" @change="load" />
    </div>

    <EmptyState v-else title="لا توجد إشعارات" class="mt-6" :icon="Bell" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import EmptyState from '../../components/EmptyState.vue';
import Pagination from '../../components/Pagination.vue';
import { Bell, ExternalLink } from '@lucide/vue';

const notifications = ref({});
const unread = ref(0);
const loading = ref(true);

async function load(page = 1) {
  loading.value = true;
  try {
    const { data } = await api.get('/me/notifications', { params: { page } });
    notifications.value = data.notifications;
    unread.value = data.unread;
  } finally {
    loading.value = false;
  }
}

async function markAll() {
  await api.post('/me/notifications/read', { all: true });
  unread.value = 0;
  load(notifications.value.current_page || 1);
}

onMounted(() => load());
</script>
