<template>
  <div class="container-site py-10 sm:py-14">
    <div class="max-w-2xl">
      <h1 class="text-2xl sm:text-3xl font-bold text-ink">الفعاليات</h1>
      <p class="mt-2 text-ink-muted">ورش عمل، جلسات مباشرة، ولقاءات أسئلة وأجوبة مع المدربين.</p>
    </div>

    <div class="mt-8 flex gap-2">
      <button class="btn text-sm" :class="filter === 'upcoming' ? 'bg-ink text-white' : 'btn-outline'" @click="filter = 'upcoming'; load(1)">القادمة</button>
      <button class="btn text-sm" :class="filter === 'all' ? 'bg-ink text-white' : 'btn-outline'" @click="filter = 'all'; load(1)">الكل</button>
    </div>

    <div v-if="loading" class="mt-8 space-y-4">
      <div v-for="i in 3" :key="i" class="card skeleton h-28" />
    </div>
    <template v-else>
      <div v-if="events.data?.length" class="mt-8 space-y-4">
        <EventCard v-for="e in events.data" :key="e.id" :event="e" />
      </div>
      <EmptyState v-else title="لا توجد فعاليات" subtitle="سيتم الإعلان عن الفعاليات الجديدة هنا أولًا." class="mt-8" :icon="CalendarDays" />
      <Pagination :data="events" @change="load" />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import EventCard from '../../components/EventCard.vue';
import EmptyState from '../../components/EmptyState.vue';
import Pagination from '../../components/Pagination.vue';
import { CalendarDays } from '@lucide/vue';

const events = ref({});
const loading = ref(true);
const filter = ref('upcoming');

async function load(page = 1) {
  loading.value = true;
  try {
    const { data } = await api.get('/events', { params: { page, filter: filter.value === 'upcoming' ? 'upcoming' : undefined } });
    events.value = data;
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>
