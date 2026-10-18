<template>
  <RouterLink :to="`/events/${event.slug}`" class="card group flex flex-col sm:flex-row overflow-hidden hover:shadow-pop transition-shadow">
    <div class="sm:w-44 shrink-0 bg-ink text-white flex sm:flex-col items-center justify-center gap-1 p-4 sm:py-6">
      <span class="text-2xl sm:text-3xl font-bold">{{ day }}</span>
      <span class="text-sm text-stone-300">{{ month }}</span>
      <span class="text-xs text-stone-400">{{ time }}</span>
    </div>
    <div class="flex-1 p-5">
      <div class="mb-1.5 flex items-center gap-2">
        <span class="badge-blue">{{ typeLabel }}</span>
        <span v-if="event.location" class="text-xs text-ink-faint flex items-center gap-1"><MapPin :size="12" />{{ event.location }}</span>
        <span v-else class="text-xs text-ink-faint flex items-center gap-1"><Video :size="12" />أونلاين</span>
      </div>
      <h3 class="text-base font-bold text-ink group-hover:text-primary transition-colors">{{ event.title }}</h3>
      <p class="mt-1 text-sm text-ink-muted line-clamp-2">{{ event.description }}</p>
      <div class="mt-3 flex items-center gap-3 text-xs text-ink-faint">
        <span v-if="event.speaker">المتحدث: {{ event.speaker }}</span>
        <span v-if="event.course" class="badge-gray">{{ event.course.title }}</span>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue';
import { MapPin, Video } from '@lucide/vue';

const props = defineProps({ event: Object });

const d = computed(() => new Date(props.event.start_at));
const day = computed(() => d.value.toLocaleDateString('ar-EG', { day: 'numeric' }));
const month = computed(() => d.value.toLocaleDateString('ar-EG', { month: 'long' }));
const time = computed(() => d.value.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }));

const typeLabel = computed(() => ({
  workshop: 'ورشة عمل', live_session: 'جلسة مباشرة', webinar: 'ويبينار',
  qna: 'أسئلة وأجوبة', meetup: 'لقاء', graduation: 'تخرج', other: 'فعالية',
}[props.event.type] || 'فعالية'));
</script>
