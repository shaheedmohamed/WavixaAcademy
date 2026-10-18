<template>
  <span :class="klass">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ status: String });

const MAP = {
  active: ['badge-green', 'نشط'],
  pending: ['badge-amber', 'قيد الانتظار'],
  suspended: ['badge-red', 'موقوف'],
  expired: ['badge-gray', 'منتهي'],
  cancelled: ['badge-gray', 'ملغي'],
  completed: ['badge-blue', 'مكتمل'],
  published: ['badge-green', 'منشور'],
  draft: ['badge-gray', 'مسودة'],
  archived: ['badge-gray', 'مؤرشف'],
  ready: ['badge-green', 'جاهز'],
  importing: ['badge-blue', 'جارٍ الاستيراد'],
  processing: ['badge-blue', 'قيد المعالجة'],
  failed: ['badge-red', 'فشل'],
  submitted: ['badge-blue', 'مُرسل'],
  under_review: ['badge-amber', 'قيد المراجعة'],
  approved: ['badge-green', 'مقبول'],
  needs_changes: ['badge-amber', 'يحتاج تعديلات'],
  rejected: ['badge-red', 'مرفوض'],
  registered: ['badge-blue', 'مسجّل'],
  attended: ['badge-green', 'حضر'],
  open: ['badge-red', 'مفتوح'],
  resolved: ['badge-green', 'تمت المعالجة'],
  terminated: ['badge-gray', 'مُنهى'],
  low: ['badge-gray', 'منخفض'],
  medium: ['badge-amber', 'متوسط'],
  high: ['badge-red', 'مرتفع'],
};

const klass = computed(() => MAP[props.status]?.[0] || 'badge-gray');
const label = computed(() => MAP[props.status]?.[1] || props.status);
</script>
