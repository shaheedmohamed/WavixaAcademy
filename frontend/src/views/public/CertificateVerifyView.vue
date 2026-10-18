<template>
  <div class="container-site py-12 max-w-4xl">
    <div v-if="loading" class="card p-8 text-center max-w-lg mx-auto"><div class="skeleton mx-auto h-6 w-2/3" /></div>

    <div v-else-if="result?.valid" class="space-y-6">
      <div class="card p-6 text-center max-w-2xl mx-auto border-emerald/30 bg-emerald/5">
        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-soft text-emerald border border-emerald/20 shadow-sm">
          <BadgeCheck :size="32" />
        </div>
        <h1 class="text-2xl font-bold text-ink font-amiri">شهادة إتمام معتمدة وموثقة</h1>
        <p class="mt-1 text-sm text-ink-muted">تم التحقق من صحة هذه الشهادة وسجلاتها الرسمية لدى أكاديمية Wavixa بنجاح.</p>

        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-start text-xs pt-4 border-t border-emerald/15">
          <div class="bg-white/80 p-2.5 rounded-lg border border-line/60">
            <span class="text-ink-muted block text-[11px]">الطالب:</span>
            <span class="font-bold text-ink mt-0.5 block truncate">{{ result.certificate.student_name }}</span>
          </div>
          <div class="bg-white/80 p-2.5 rounded-lg border border-line/60">
            <span class="text-ink-muted block text-[11px]">الكورس:</span>
            <span class="font-bold text-ink mt-0.5 block truncate">{{ result.certificate.course }}</span>
          </div>
          <div class="bg-white/80 p-2.5 rounded-lg border border-line/60">
            <span class="text-ink-muted block text-[11px]">رقم الشهادة:</span>
            <span class="font-bold text-ink mt-0.5 block font-mono" dir="ltr">{{ result.certificate.number }}</span>
          </div>
          <div class="bg-white/80 p-2.5 rounded-lg border border-line/60">
            <span class="text-ink-muted block text-[11px]">تاريخ المنح:</span>
            <span class="font-bold text-ink mt-0.5 block">{{ result.certificate.issued_at }}</span>
          </div>
        </div>
      </div>

      <!-- Full Certificate Display with Download Options -->
      <div class="card p-6">
        <WavixaCertificate
          :student-name="result.certificate.student_name"
          :course-title="result.certificate.course"
          :certificate-title="result.certificate.certificate_title"
          :instructor-name="result.certificate.instructor_name"
          :certificate-number="result.certificate.number"
          :issued-date="result.certificate.issued_at_formatted || result.certificate.issued_at"
          :verification-token="result.certificate.verification_token"
          :default-lang="result.certificate.certificate_language === 'en' ? 'en' : 'ar'"
        />
      </div>
    </div>

    <div v-else class="card p-8 text-center max-w-lg mx-auto">
      <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-danger-soft text-danger">
        <ShieldX :size="30" />
      </div>
      <h1 class="text-xl font-bold text-ink">شهادة غير صالحة</h1>
      <p class="mt-2 text-sm text-ink-muted">لم نتمكن من التحقق من هذه الشهادة. قد يكون الرابط غير صحيح أو تم إلغاء الشهادة.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import WavixaCertificate from '../../components/WavixaCertificate.vue';
import { BadgeCheck, ShieldX } from '@lucide/vue';

const route = useRoute();
const result = ref(null);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get(`/certificates/verify/${route.params.token}`);
    result.value = data;
  } catch {
    result.value = { valid: false };
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.font-amiri {
  font-family: 'Amiri', 'IBM Plex Sans Arabic', serif;
}
</style>
