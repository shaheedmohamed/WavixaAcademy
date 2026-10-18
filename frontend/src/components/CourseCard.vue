<template>
  <RouterLink :to="`/courses/${course.slug}`" class="card group flex flex-col overflow-hidden hover:shadow-pop transition-shadow duration-200">
    <div class="relative aspect-[16/9] bg-stone-100 overflow-hidden">
      <img v-if="course.thumbnail" :src="course.thumbnail" :alt="course.title" class="h-full w-full object-cover group-hover:scale-[1.02] transition-transform duration-300" loading="lazy" />
      <div v-else class="flex h-full items-center justify-center text-ink-faint">
        <GraduationCap :size="40" />
      </div>
      <span v-if="course.is_featured" class="absolute top-3 start-3 badge-amber">مميز</span>
    </div>
    <div class="flex flex-1 flex-col p-5">
      <div class="mb-2 flex items-center gap-2">
        <span class="badge-blue">{{ levelLabel }}</span>
        <span class="text-xs text-ink-faint">{{ course.lessons_count ?? 0 }} درسًا</span>
      </div>
      <h3 class="text-base font-bold text-ink leading-snug group-hover:text-primary transition-colors">{{ course.title }}</h3>
      <p class="mt-1.5 text-sm text-ink-muted leading-relaxed line-clamp-2">{{ course.short_description }}</p>
      <div class="mt-auto pt-4 flex items-center justify-between border-t border-line/60 mt-4">
        <div class="flex items-center gap-2 text-xs text-ink-muted">
          <UserCircle :size="16" />
          <span>{{ course.instructor?.name || 'فريق المنصة' }}</span>
        </div>
        <span class="text-sm font-bold text-ink">{{ priceLabel }}</span>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue';
import { GraduationCap, UserCircle } from '@lucide/vue';

const props = defineProps({ course: Object });

const levelLabel = computed(() => ({
  beginner: 'مبتدئ', intermediate: 'متوسط', advanced: 'متقدم', all_levels: 'كل المستويات',
}[props.course.level] || 'كل المستويات'));

const priceLabel = computed(() => {
  const price = Number(props.course.price || 0);
  return price > 0 ? `${price} ${props.course.currency || 'EGP'}` : 'مجاني';
});
</script>
