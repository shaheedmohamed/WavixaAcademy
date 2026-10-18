<template>
  <div class="container-site py-10 sm:py-14">
    <div class="max-w-2xl">
      <h1 class="text-2xl sm:text-3xl font-bold text-ink">المدربون</h1>
      <p class="mt-2 text-ink-muted">ممارسون حقيقيون يعلّمون من خبرة عملية، لا من شرائح عرض.</p>
    </div>

    <div v-if="loading" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 3" :key="i" class="card skeleton h-40" />
    </div>
    <div v-else-if="instructors.length" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="p in instructors" :key="p.id" class="card p-6">
        <div class="flex items-center gap-4">
          <img v-if="p.avatar || p.user?.avatar" :src="p.avatar || p.user.avatar" :alt="p.user?.name" class="h-14 w-14 rounded-full object-cover" />
          <div v-else class="flex h-14 w-14 items-center justify-center rounded-full bg-ink text-white text-lg font-bold">{{ p.user?.name?.charAt(0) }}</div>
          <div>
            <h3 class="font-bold text-ink">{{ p.user?.name }}</h3>
            <p class="text-sm text-ink-muted">{{ p.title }}</p>
          </div>
        </div>
        <p v-if="p.bio" class="mt-4 text-sm leading-relaxed text-ink-muted line-clamp-4">{{ p.bio }}</p>
      </div>
    </div>
    <EmptyState v-else title="لا يوجد مدربون معلنون بعد" class="mt-8" :icon="Users" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import EmptyState from '../../components/EmptyState.vue';
import { Users } from '@lucide/vue';

const instructors = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/instructors');
    instructors.value = data.instructors || [];
  } finally {
    loading.value = false;
  }
});
</script>
