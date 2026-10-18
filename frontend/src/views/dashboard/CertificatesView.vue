<template>
  <div>
    <h1 class="text-xl sm:text-2xl font-bold text-ink">شهاداتي</h1>
    <p class="mt-1 text-sm text-ink-muted">شهادات الإتمام الموثقة والمعتمدة من منصة Wavixa.</p>

    <div v-if="loading" class="mt-6 grid gap-4 sm:grid-cols-2"><div v-for="i in 2" :key="i" class="card skeleton h-32" /></div>

    <div v-else-if="certificates.length" class="mt-6 grid gap-4 sm:grid-cols-2">
      <div v-for="c in certificates" :key="c.id" class="card p-6 flex flex-col justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-soft text-amber border border-amber/20 shadow-sm">
            <Award :size="24" />
          </div>
          <div class="min-w-0 flex-1">
            <span class="badge-green mb-1">شهادة معتمدة</span>
            <h3 class="font-bold text-ink text-base leading-snug">{{ c.course?.certificate_title || c.course?.title }}</h3>
            <p v-if="c.instructor_name" class="text-xs text-ink-muted mt-0.5">المدرب: {{ c.instructor_name }}</p>
            <p class="mt-0.5 text-xs text-ink-faint">صدرت في {{ new Date(c.issued_at).toLocaleDateString('ar-EG') }}</p>
            <p class="mt-1 text-xs font-mono text-ink-muted" dir="ltr">{{ c.number }}</p>
          </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-2 pt-4 border-t border-line">
          <button class="btn-primary flex-1 !py-2 !text-xs flex items-center justify-center gap-1.5" @click="selectedCert = c">
            <Eye :size="14" /> عرض وتحميل الشهادة
          </button>
          <a :href="c.verify_url" target="_blank" rel="noopener" class="btn-outline !py-2 !text-xs flex items-center gap-1">
            <ExternalLink :size="13" /> رابط التحقق
          </a>
          <button class="btn-ghost !py-2 !text-xs text-ink-muted hover:text-ink" @click="copy(c.verify_url)" title="نسخ رابط التحقق">
            <Copy :size="14" />
          </button>
        </div>
      </div>
    </div>

    <EmptyState v-else title="لا توجد شهادات بعد" subtitle="أكمل كورسًا بالكامل (دروس + مشاريع + اختبارات) لتحصل على شهادتك المعتمدة تلقائيًا." class="mt-6" :icon="Award" />

    <!-- Certificate Modal View -->
    <Modal :open="!!selectedCert" :title="selectedCert?.course?.certificate_title || selectedCert?.course?.title || 'شهادة إتمام معتمدة'" max-width="max-w-5xl" @close="selectedCert = null">
      <div v-if="selectedCert" class="py-2">
        <WavixaCertificate
          :student-name="selectedCert.student_name || auth.user?.name"
          :course-title="selectedCert.course?.title"
          :certificate-title="selectedCert.course?.certificate_title"
          :instructor-name="selectedCert.instructor_name || selectedCert.course?.instructor?.name"
          :certificate-number="selectedCert.number"
          :issued-date="new Date(selectedCert.issued_at).toLocaleDateString('ar-EG')"
          :verification-token="selectedCert.verification_token"
          :verify-url="selectedCert.verify_url"
          :default-lang="selectedCert.course?.certificate_language === 'en' ? 'en' : 'ar'"
        />
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/client';
import { auth } from '../../stores/auth';
import { toast } from '../../stores/toast';
import Modal from '../../components/Modal.vue';
import EmptyState from '../../components/EmptyState.vue';
import WavixaCertificate from '../../components/WavixaCertificate.vue';
import { Award, Copy, Eye, ExternalLink } from '@lucide/vue';

const certificates = ref([]);
const loading = ref(true);
const selectedCert = ref(null);

async function copy(url) {
  try {
    await navigator.clipboard.writeText(url);
    toast('تم نسخ رابط التحقق.');
  } catch {
    toast('تعذر النسخ.', 'error');
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/me/certificates');
    certificates.value = data.certificates || [];
  } finally {
    loading.value = false;
  }
});
</script>
