<template>
  <div class="certificate-wrapper flex flex-col items-center gap-4 w-full">
    <!-- Top Bar: Language Switcher & Actions -->
    <div v-if="showActions" class="flex flex-wrap items-center justify-between gap-3 w-full max-w-4xl print:hidden px-1">
      <!-- Language Selector -->
      <div class="flex items-center gap-2 bg-surface p-1 rounded-xl border border-line">
        <span class="text-xs text-ink-muted px-2 font-medium">لغة الشهادة:</span>
        <button
          type="button"
          class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all"
          :class="currentLang === 'ar' ? 'bg-primary text-white shadow-sm' : 'text-ink-soft hover:text-ink hover:bg-white'"
          @click="currentLang = 'ar'"
        >
          العربية (Arabic)
        </button>
        <button
          type="button"
          class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all"
          :class="currentLang === 'en' ? 'bg-primary text-white shadow-sm' : 'text-ink-soft hover:text-ink hover:bg-white'"
          @click="currentLang = 'en'"
        >
          English (الإنجليزية)
        </button>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-2">
        <button class="btn-primary !px-4 !py-2 !text-xs flex items-center gap-2 shadow-sm" :disabled="downloading" @click="downloadPdf">
          <Loader2 v-if="downloading === 'pdf'" :size="15" class="animate-spin" />
          <Download v-else :size="15" />
          تحميل كـ PDF ({{ currentLang === 'ar' ? 'عربي' : 'English' }})
        </button>
        <button class="btn-outline !px-3.5 !py-2 !text-xs flex items-center gap-2 shadow-sm bg-white" :disabled="downloading" @click="downloadImage">
          <Loader2 v-if="downloading === 'png'" :size="15" class="animate-spin" />
          <ImageIcon v-else :size="15" />
          صورة PNG
        </button>
        <button class="btn-ghost !px-3 !py-2 !text-xs flex items-center gap-1.5 text-ink-muted hover:text-ink" @click="printCertificate">
          <Printer :size="15" />
          طباعة
        </button>
      </div>
    </div>

    <!-- Scalable Outer Container for responsiveness -->
    <div class="certificate-viewport w-full overflow-x-auto p-1 flex justify-center">
      <div
        ref="certNode"
        class="wavixa-certificate-card relative bg-[#FCFBF7] text-[#1E293B] shadow-2xl overflow-hidden select-none"
        :class="{ 'is-preview': isPreview, 'lang-en': currentLang === 'en', 'lang-ar': currentLang === 'ar' }"
        :dir="currentLang === 'ar' ? 'rtl' : 'ltr'"
        style="width: 1000px; height: 700px; min-width: 1000px; min-height: 700px;"
      >
        <!-- Background Guilloche / Watermark Pattern -->
        <div class="absolute inset-0 pointer-events-none opacity-[0.035] mix-blend-multiply flex items-center justify-center">
          <svg width="700" height="700" viewBox="0 0 100 100" class="w-[580px] h-[580px]">
            <circle cx="50" cy="50" r="48" fill="none" stroke="#000" stroke-width="0.5" stroke-dasharray="2,2" />
            <circle cx="50" cy="50" r="40" fill="none" stroke="#000" stroke-width="0.8" />
            <polygon points="50,5 63,38 98,38 70,59 81,92 50,72 19,92 30,59 2,38 37,38" fill="none" stroke="#000" stroke-width="0.5" />
            <polygon points="50,15 60,40 86,40 65,56 73,81 50,66 27,81 35,56 14,40 40,40" fill="none" stroke="#000" stroke-width="0.3" />
          </svg>
        </div>

        <!-- Watermark Monogram in Center -->
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-[0.03]">
          <span class="text-[340px] font-black tracking-tighter font-serif text-[#065F46]">W</span>
        </div>

        <!-- Preview Ribbon -->
        <div v-if="isPreview" class="absolute top-7 -end-12 rotate-45 z-30 bg-amber-500 text-white font-bold text-xs py-1 px-14 shadow-md uppercase tracking-wider">
          {{ currentLang === 'ar' ? 'معاينة تجريبية' : 'PREVIEW DEMO' }}
        </div>

        <!-- Outer Ornamental Gold Border -->
        <div class="absolute inset-4 border-[3px] border-[#C5A059] rounded-sm pointer-events-none z-10" />
        <!-- Inner Emerald Border -->
        <div class="absolute inset-6 border border-[#065F46]/40 rounded-sm pointer-events-none z-10" />
        <!-- Fine Gold Inset Line -->
        <div class="absolute inset-[26px] border border-[#D4AF37]/50 rounded-sm pointer-events-none z-10" />

        <!-- Corner Flourishes (SVG Gold Ornaments) -->
        <svg class="absolute top-5 start-5 w-14 h-14 text-[#C5A059] z-20 pointer-events-none" viewBox="0 0 100 100" fill="currentColor">
          <path d="M0,0 L40,0 C20,10 10,20 0,40 Z" />
          <path d="M5,5 L30,5 C18,12 12,18 5,30 Z" opacity="0.6" />
          <circle cx="10" cy="10" r="3" />
          <circle cx="25" cy="7" r="1.5" />
          <circle cx="7" cy="25" r="1.5" />
        </svg>
        <svg class="absolute top-5 end-5 w-14 h-14 text-[#C5A059] z-20 pointer-events-none rotate-90" viewBox="0 0 100 100" fill="currentColor">
          <path d="M0,0 L40,0 C20,10 10,20 0,40 Z" />
          <path d="M5,5 L30,5 C18,12 12,18 5,30 Z" opacity="0.6" />
          <circle cx="10" cy="10" r="3" />
          <circle cx="25" cy="7" r="1.5" />
          <circle cx="7" cy="25" r="1.5" />
        </svg>
        <svg class="absolute bottom-5 start-5 w-14 h-14 text-[#C5A059] z-20 pointer-events-none -rotate-90" viewBox="0 0 100 100" fill="currentColor">
          <path d="M0,0 L40,0 C20,10 10,20 0,40 Z" />
          <path d="M5,5 L30,5 C18,12 12,18 5,30 Z" opacity="0.6" />
          <circle cx="10" cy="10" r="3" />
          <circle cx="25" cy="7" r="1.5" />
          <circle cx="7" cy="25" r="1.5" />
        </svg>
        <svg class="absolute bottom-5 end-5 w-14 h-14 text-[#C5A059] z-20 pointer-events-none rotate-180" viewBox="0 0 100 100" fill="currentColor">
          <path d="M0,0 L40,0 C20,10 10,20 0,40 Z" />
          <path d="M5,5 L30,5 C18,12 12,18 5,30 Z" opacity="0.6" />
          <circle cx="10" cy="10" r="3" />
          <circle cx="25" cy="7" r="1.5" />
          <circle cx="7" cy="25" r="1.5" />
        </svg>

        <!-- ======================= ARABIC VERSION ======================= -->
        <div v-if="currentLang === 'ar'" class="relative z-20 h-full flex flex-col justify-between px-16 py-10 text-center">
          <!-- Top Header -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-center gap-3">
              <div class="h-[1px] w-20 bg-gradient-to-r from-transparent via-[#C5A059] to-[#C5A059]" />
              <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#065F46] to-[#047857] flex items-center justify-center shadow-md border border-[#D4AF37]/70">
                  <span class="text-white font-black text-base font-serif">W</span>
                </div>
                <div class="text-start leading-none">
                  <span class="text-sm font-black tracking-widest text-[#065F46] uppercase font-sans">WAVIXA ACADEMY</span>
                  <p class="text-[10px] text-[#C5A059] font-bold tracking-wider">أكاديمية وافيكسا للتعليم والتدريب</p>
                </div>
              </div>
              <div class="h-[1px] w-20 bg-gradient-to-l from-transparent via-[#C5A059] to-[#C5A059]" />
            </div>

            <div class="pt-2">
              <h1 class="text-3xl font-extrabold text-[#0F172A] tracking-normal font-amiri">
                شَهَادَةُ إِتْمَامٍ وَتَفَوُّقٍ مُعْتَمَدَة
              </h1>
              <p class="text-xs uppercase tracking-[0.25em] font-semibold text-[#856404] mt-0.5">
                CERTIFICATE OF ACCOMPLISHMENT
              </p>
            </div>
          </div>

          <!-- Mid Section -->
          <div class="my-auto space-y-3.5 max-w-2xl mx-auto w-full">
            <p class="text-xs text-[#475569] font-medium font-amiri">
              تَشْهَدُ إِدَارَةُ أَكَادِيمِيَّةِ WAVIXA بِأَنَّ الطَّالِبَ / الطَّالِبَةَ:
            </p>

            <div class="relative py-1">
              <h2 class="text-3xl sm:text-4xl font-bold text-[#065F46] font-amiri tracking-wide">
                {{ studentName || 'اسم الطالب الثلاثي' }}
              </h2>
              <div class="mx-auto mt-2 h-[2px] w-48 bg-gradient-to-r from-transparent via-[#C5A059] to-transparent" />
            </div>

            <p class="text-xs text-[#475569] leading-relaxed font-amiri">
              قَدْ أَتَمَّ(تْ) بِنَجَاحٍ وَاجْتِيَازٍ مُتَمَيِّزٍ كَافَّةَ الْمُتَطَلَّبَاتِ التَّعْلِيمِيَّةِ وَالتَّطْبِيقِيَّةِ لِبَرْنَامَجِ:
            </p>

            <div class="inline-block px-7 py-2.5 rounded-md bg-gradient-to-r from-[#065F46]/8 via-[#065F46]/12 to-[#065F46]/8 border border-[#065F46]/25 shadow-sm">
              <h3 class="text-xl sm:text-2xl font-black text-[#0F172A] font-amiri">
                {{ certificateTitle || courseTitle || 'عنوان الكورس التدريبي' }}
              </h3>
            </div>

            <p class="text-[11px] text-[#64748B] max-w-lg mx-auto leading-relaxed">
              وفق أعلى معايير الجودة الأكاديمية والمهنية المقررة من المنصة، وبعد إنجاز كافة الدروس والمشاريع التطبيقية والاختبارات بنجاح.
            </p>
          </div>

          <!-- Signatures & Official Seal -->
          <div class="pt-2">
            <div class="grid grid-cols-3 items-end gap-4 text-center">
              <!-- Right Signature: Instructor -->
              <div class="flex flex-col items-center">
                <div class="h-10 flex items-center justify-center">
                  <span class="font-serif italic text-base text-[#1E293B] font-bold tracking-wider -rotate-3 select-none">
                    {{ instructorName || 'المدرب المعتمد' }}
                  </span>
                </div>
                <div class="w-36 h-[1.5px] bg-[#94A3B8]/60 my-1" />
                <p class="text-xs font-bold text-[#1E293B] font-amiri">مُدَرِّبُ الكُورْسِ</p>
                <p class="text-[10px] text-[#64748B]">{{ instructorName || 'المدرب المعتمد' }}</p>
              </div>

              <!-- Center: Official Golden Medallion Seal -->
              <div class="flex flex-col items-center justify-center">
                <div class="relative w-24 h-24 flex items-center justify-center">
                  <svg class="w-24 h-24 drop-shadow-md text-[#D4AF37]" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="46" fill="#FBF5E6" stroke="#C5A059" stroke-width="2.5" />
                    <circle cx="50" cy="50" r="39" fill="none" stroke="#B8860B" stroke-width="1" stroke-dasharray="2,2" />
                    <path d="M35,84 L22,98 L37,92 L45,98 L42,85" fill="#C5A059" />
                    <path d="M65,84 L78,98 L63,92 L55,98 L58,85" fill="#B8860B" />
                    <circle cx="50" cy="50" r="32" fill="#065F46" />
                    <circle cx="50" cy="50" r="30" fill="none" stroke="#FDE68A" stroke-width="1" />
                    <polygon points="50,26 53,35 63,35 55,41 58,50 50,44 42,50 45,41 37,35 47,35" fill="#FDE68A" />
                    <text x="50" y="62" font-size="7" font-weight="bold" fill="#FDE68A" text-anchor="middle" font-family="sans-serif">WAVIXA</text>
                    <text x="50" y="70" font-size="5" fill="#FFFFFF" text-anchor="middle" font-family="sans-serif">VERIFIED</text>
                  </svg>
                </div>
                <p class="text-[10px] font-bold text-[#C5A059] tracking-wider uppercase mt-0.5">الاعتماد الرسمي</p>
              </div>

              <!-- Left Signature: Academy Management -->
              <div class="flex flex-col items-center">
                <div class="h-10 flex items-center justify-center">
                  <span class="font-serif italic text-base text-[#065F46] font-bold tracking-widest -rotate-2 select-none">
                    Wavixa Academy
                  </span>
                </div>
                <div class="w-36 h-[1.5px] bg-[#94A3B8]/60 my-1" />
                <p class="text-xs font-bold text-[#1E293B] font-amiri">إِدَارَةُ الأَكَادِيمِيَّةِ</p>
                <p class="text-[10px] text-[#64748B]">Wavixa Digital Academy</p>
              </div>
            </div>

            <!-- Footer Meta Bar -->
            <div class="mt-4 pt-2.5 border-t border-[#E2E8F0] flex items-center justify-between text-[10px] text-[#64748B]">
              <div class="flex items-center gap-1.5" dir="ltr">
                <span class="font-semibold text-[#065F46]">Serial:</span>
                <span class="font-mono text-[#0F172A] font-bold">{{ certificateNumber || 'WXA-2026-000033' }}</span>
              </div>
              <div class="flex items-center gap-1">
                <span>تاريخ المنح:</span>
                <span class="font-semibold text-[#0F172A]">{{ issuedDate || todayFormatted }}</span>
              </div>
              <div class="flex items-center gap-1.5" dir="ltr">
                <span class="font-semibold text-[#065F46]">Verify:</span>
                <span class="text-[#0F172A] truncate max-w-[220px]">
                  academy.wavixa.net/certificates/{{ (verificationToken || 'wx-sample-token').substring(0, 14) }}...
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================= ENGLISH VERSION ======================= -->
        <div v-else class="relative z-20 h-full flex flex-col justify-between px-16 py-10 text-center font-serif">
          <!-- Top Header -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-center gap-3">
              <div class="h-[1px] w-20 bg-gradient-to-r from-transparent via-[#C5A059] to-[#C5A059]" />
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#065F46] to-[#047857] flex items-center justify-center shadow-md border border-[#D4AF37]/70">
                  <span class="text-white font-black text-base font-serif">W</span>
                </div>
                <div class="text-start leading-none">
                  <span class="text-sm font-black tracking-widest text-[#065F46] uppercase font-sans">WAVIXA ACADEMY</span>
                  <p class="text-[9px] text-[#C5A059] font-bold tracking-widest uppercase">ACADEMY OF DIGITAL EXCELLENCE</p>
                </div>
              </div>
              <div class="h-[1px] w-20 bg-gradient-to-l from-transparent via-[#C5A059] to-[#C5A059]" />
            </div>

            <div class="pt-2">
              <h1 class="text-3xl font-black text-[#0F172A] tracking-[0.12em] uppercase font-cinzel">
                CERTIFICATE OF ACCOMPLISHMENT
              </h1>
              <p class="text-[11px] uppercase tracking-[0.3em] font-semibold text-[#856404] mt-0.5 font-sans">
                OFFICIALLY CONFERRED FOR PROFESSIONAL DISTINCTION
              </p>
            </div>
          </div>

          <!-- Mid Section -->
          <div class="my-auto space-y-3 max-w-2xl mx-auto w-full">
            <p class="text-xs uppercase tracking-widest text-[#64748B] font-semibold font-sans">
              THIS IS TO OFFICIALLY CERTIFY THAT
            </p>

            <div class="relative py-1">
              <h2 class="text-3xl sm:text-4xl font-bold text-[#065F46] font-cinzel tracking-wider capitalize">
                {{ studentName || 'Student Full Name' }}
              </h2>
              <div class="mx-auto mt-2 h-[2px] w-52 bg-gradient-to-r from-transparent via-[#C5A059] to-transparent" />
            </div>

            <p class="text-xs text-[#475569] leading-relaxed font-sans">
              has successfully completed all academic requirements, practical projects, and demonstrated competence in:
            </p>

            <div class="inline-block px-7 py-2.5 rounded-md bg-gradient-to-r from-[#065F46]/8 via-[#065F46]/12 to-[#065F46]/8 border border-[#065F46]/25 shadow-sm">
              <h3 class="text-xl sm:text-2xl font-bold text-[#0F172A] font-cinzel tracking-wide">
                {{ certificateTitle || courseTitle || 'Course Title' }}
              </h3>
            </div>

            <p class="text-[11px] text-[#64748B] max-w-lg mx-auto leading-relaxed font-sans">
              Conferred in recognition of dedication and technical proficiency, meeting all rigorous curriculum and practical assessment standards.
            </p>
          </div>

          <!-- Signatures & Official Seal -->
          <div class="pt-2">
            <div class="grid grid-cols-3 items-end gap-4 text-center">
              <!-- Left Signature: Instructor -->
              <div class="flex flex-col items-center">
                <div class="h-10 flex items-center justify-center">
                  <span class="font-serif italic text-base text-[#1E293B] font-bold tracking-wider -rotate-2 select-none">
                    {{ instructorName || 'Course Instructor' }}
                  </span>
                </div>
                <div class="w-36 h-[1.5px] bg-[#94A3B8]/60 my-1" />
                <p class="text-xs font-bold text-[#1E293B] font-cinzel tracking-wider uppercase">Course Instructor</p>
                <p class="text-[10px] text-[#64748B] font-sans">{{ instructorName || 'Certified Instructor' }}</p>
              </div>

              <!-- Center: Official Golden Medallion Seal -->
              <div class="flex flex-col items-center justify-center">
                <div class="relative w-24 h-24 flex items-center justify-center">
                  <svg class="w-24 h-24 drop-shadow-md text-[#D4AF37]" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="46" fill="#FBF5E6" stroke="#C5A059" stroke-width="2.5" />
                    <circle cx="50" cy="50" r="39" fill="none" stroke="#B8860B" stroke-width="1" stroke-dasharray="2,2" />
                    <path d="M35,84 L22,98 L37,92 L45,98 L42,85" fill="#C5A059" />
                    <path d="M65,84 L78,98 L63,92 L55,98 L58,85" fill="#B8860B" />
                    <circle cx="50" cy="50" r="32" fill="#065F46" />
                    <circle cx="50" cy="50" r="30" fill="none" stroke="#FDE68A" stroke-width="1" />
                    <polygon points="50,26 53,35 63,35 55,41 58,50 50,44 42,50 45,41 37,35 47,35" fill="#FDE68A" />
                    <text x="50" y="62" font-size="7" font-weight="bold" fill="#FDE68A" text-anchor="middle" font-family="sans-serif">WAVIXA</text>
                    <text x="50" y="70" font-size="5" fill="#FFFFFF" text-anchor="middle" font-family="sans-serif">VERIFIED</text>
                  </svg>
                </div>
                <p class="text-[9px] font-bold text-[#C5A059] tracking-widest uppercase mt-0.5 font-sans">Official Seal</p>
              </div>

              <!-- Right Signature: Academy Director -->
              <div class="flex flex-col items-center">
                <div class="h-10 flex items-center justify-center">
                  <span class="font-serif italic text-base text-[#065F46] font-bold tracking-widest -rotate-2 select-none">
                    Wavixa Academy
                  </span>
                </div>
                <div class="w-36 h-[1.5px] bg-[#94A3B8]/60 my-1" />
                <p class="text-xs font-bold text-[#1E293B] font-cinzel tracking-wider uppercase">Academy Director</p>
                <p class="text-[10px] text-[#64748B] font-sans">Wavixa Digital Academy</p>
              </div>
            </div>

            <!-- Footer Meta Bar -->
            <div class="mt-4 pt-2.5 border-t border-[#E2E8F0] flex items-center justify-between text-[10px] text-[#64748B] font-sans">
              <div class="flex items-center gap-1.5">
                <span class="font-semibold text-[#065F46]">Certificate ID:</span>
                <span class="font-mono text-[#0F172A] font-bold">{{ certificateNumber || 'WXA-2026-000033' }}</span>
              </div>
              <div class="flex items-center gap-1">
                <span>Conferral Date:</span>
                <span class="font-semibold text-[#0F172A]">{{ issuedDate || todayFormattedEn }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="font-semibold text-[#065F46]">Verification:</span>
                <span class="text-[#0F172A] truncate max-w-[220px]">
                  academy.wavixa.net/certificates/{{ (verificationToken || 'wx-sample-token').substring(0, 14) }}...
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import html2canvas from 'html2canvas';
import { jsPDF } from 'jspdf';
import { Download, ImageIcon, Printer, Loader2 } from '@lucide/vue';
import { toast } from '../stores/toast';

const props = defineProps({
  studentName: { type: String, default: '' },
  courseTitle: { type: String, default: '' },
  certificateTitle: { type: String, default: '' },
  instructorName: { type: String, default: '' },
  certificateNumber: { type: String, default: '' },
  issuedDate: { type: String, default: '' },
  verificationToken: { type: String, default: '' },
  verifyUrl: { type: String, default: '' },
  isPreview: { type: Boolean, default: false },
  showActions: { type: Boolean, default: true },
  defaultLang: { type: String, default: 'ar' },
});

const currentLang = ref(props.defaultLang === 'en' ? 'en' : 'ar');
watch(() => props.defaultLang, (v) => {
  if (v === 'ar' || v === 'en') {
    currentLang.value = v;
  }
});

const certNode = ref(null);
const downloading = ref(null);

const todayFormatted = computed(() => {
  const d = new Date();
  return `${d.getFullYear()}/${String(d.getMonth() + 1).padStart(2, '0')}/${String(d.getDate()).padStart(2, '0')}`;
});

const todayFormattedEn = computed(() => {
  const d = new Date();
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
});

async function captureCanvas() {
  if (!certNode.value) throw new Error('الشهادة غير جاهزة للعرض.');
  return await html2canvas(certNode.value, {
    scale: 2.5,
    useCORS: true,
    allowTaint: true,
    backgroundColor: '#FCFBF7',
    logging: false,
  });
}

async function downloadPdf() {
  downloading.value = 'pdf';
  try {
    const canvas = await captureCanvas();
    const imgData = canvas.toDataURL('image/jpeg', 0.95);
    const pdf = new jsPDF({
      orientation: 'landscape',
      unit: 'mm',
      format: 'a4',
    });
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = pdf.internal.pageSize.getHeight();
    pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
    const langSuffix = currentLang.value === 'en' ? 'EN' : 'AR';
    const fileName = `Wavixa-Certificate-${props.certificateNumber || 'Preview'}-${langSuffix}.pdf`;
    pdf.save(fileName);
    toast(currentLang.value === 'ar' ? 'تم تحميل الشهادة بصيغة PDF بنجاح.' : 'Certificate downloaded as PDF successfully.');
  } catch (err) {
    console.error(err);
    toast('حدث خطأ أثناء إنشاء ملف الـ PDF.', 'error');
  } finally {
    downloading.value = null;
  }
}

async function downloadImage() {
  downloading.value = 'png';
  try {
    const canvas = await captureCanvas();
    const link = document.createElement('a');
    const langSuffix = currentLang.value === 'en' ? 'EN' : 'AR';
    link.download = `Wavixa-Certificate-${props.certificateNumber || 'Preview'}-${langSuffix}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
    toast(currentLang.value === 'ar' ? 'تم تحميل الشهادة كصورة عالية الجودة.' : 'Certificate downloaded as image successfully.');
  } catch (err) {
    console.error(err);
    toast('حدث خطأ أثناء تحميل الصورة.', 'error');
  } finally {
    downloading.value = null;
  }
}

function printCertificate() {
  window.print();
}
</script>

<style scoped>
.font-amiri {
  font-family: 'Amiri', 'IBM Plex Sans Arabic', serif, system-ui;
}
.font-cinzel {
  font-family: 'Cinzel', 'Playfair Display', 'Times New Roman', serif, system-ui;
}

@media print {
  .certificate-viewport {
    overflow: visible !important;
    padding: 0 !important;
  }
  .wavixa-certificate-card {
    box-shadow: none !important;
    border: none !important;
    page-break-inside: avoid;
    width: 100% !important;
    max-width: 100% !important;
  }
}
</style>
