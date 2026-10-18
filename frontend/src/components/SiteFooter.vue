<template>
  <footer class="border-t border-line bg-white mt-16">
    <div class="container-site py-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
      <div>
        <div class="flex items-center gap-2.5 mb-4">
          <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink text-white font-bold text-sm">W</div>
          <span class="text-lg font-bold text-ink">{{ settings.platform_name }}</span>
        </div>
        <p class="text-sm text-ink-muted leading-relaxed">{{ settings.footer_about }}</p>
      </div>

      <div>
        <h4 class="mb-4 text-sm font-bold text-ink">المنصة</h4>
        <ul class="space-y-2.5 text-sm text-ink-muted">
          <li><RouterLink to="/courses" class="hover:text-ink">الكورسات</RouterLink></li>
          <li><RouterLink to="/events" class="hover:text-ink">الفعاليات</RouterLink></li>
          <li><RouterLink to="/instructors" class="hover:text-ink">المدربون</RouterLink></li>
          <li><RouterLink to="/certificates/verify" class="hover:text-ink">التحقق من الشهادات</RouterLink></li>
        </ul>
      </div>

      <div>
        <h4 class="mb-4 text-sm font-bold text-ink">الدعم</h4>
        <ul class="space-y-2.5 text-sm text-ink-muted">
          <li><RouterLink to="/faq" class="hover:text-ink">الأسئلة الشائعة</RouterLink></li>
          <li><RouterLink to="/contact" class="hover:text-ink">تواصل معنا</RouterLink></li>
          <li><RouterLink to="/about" class="hover:text-ink">من نحن</RouterLink></li>
        </ul>
      </div>

      <div>
        <h4 class="mb-4 text-sm font-bold text-ink">قانوني</h4>
        <ul class="space-y-2.5 text-sm text-ink-muted">
          <li><RouterLink to="/terms" class="hover:text-ink">الشروط والأحكام</RouterLink></li>
          <li><RouterLink to="/privacy" class="hover:text-ink">سياسة الخصوصية</RouterLink></li>
        </ul>
        <a v-if="settings.support_email" :href="'mailto:' + settings.support_email" class="mt-4 block text-sm text-primary">{{ settings.support_email }}</a>
      </div>
    </div>

    <div class="border-t border-line">
      <div class="container-site flex flex-col sm:flex-row items-center justify-between gap-2 py-4 text-xs text-ink-faint">
        <span>© {{ new Date().getFullYear() }} {{ settings.platform_name }}. جميع الحقوق محفوظة.</span>
        <span>صُنع بإتقان للتعلم الحقيقي.</span>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { settings } from '../stores/settings';
</script>
