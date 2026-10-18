<template>
  <div class="container-site py-12 sm:py-16 max-w-3xl">
    <h1 class="text-2xl sm:text-3xl font-bold text-ink">من نحن</h1>
    <div class="mt-6 space-y-5 text-ink-soft leading-relaxed">
      <p>
        {{ settings.platform_name }} منصة تعليمية عربية وُلدت من فكرة بسيطة:
        المشكلة في تعلم البرمجة ليست نقص المحتوى — بل نقص التطبيق الحقيقي.
      </p>
      <p>
        لذلك بنينا منهجًا يقوم على ثلاثة أركان: <strong>دروس مركزة</strong> تشرح المفهوم،
        <strong>مشاريع حقيقية</strong> تُراجع من مدربين، و<strong>مجتمع فعاليات</strong>
        يبقيك على اتصال مباشر بمن سبقوك على الطريق.
      </p>
      <p>
        لا نؤمن بالشهادات التي تُمنح لمجرد المشاهدة. شهاداتنا تصدر فقط بعد إكمال المحتوى
        واجتياز المشاريع والاختبارات — وكل شهادة تحمل رقم تحقق عامًا يمكن لأي جهة التأكد منه.
      </p>
      <h2 class="pt-4 text-xl font-bold text-ink">ماذا نقدم؟</h2>
      <ul class="list-disc space-y-2 ps-6">
        <li>كورسات برمجة منظمة في وحدات ودروس متدرجة.</li>
        <li>فيديوهات محمية بصلاحيات وصول ومتابعة تقدم دقيقة.</li>
        <li>مشاريع تطبيقية مع مراجعة وملاحظات مكتوبة.</li>
        <li>اختبارات قصيرة لقياس الفهم.</li>
        <li>فعاليات وجلسات مباشرة مع تسجيل حضور رقمي.</li>
        <li>شهادات إتمام موثقة قابلة للتحقق.</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { settings, loadSettings } from '../../stores/settings';
onMounted(() => loadSettings());
</script>
