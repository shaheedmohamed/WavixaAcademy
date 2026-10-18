<template>
  <div class="relative w-full overflow-hidden rounded-xl2 bg-black aspect-video select-none"
       @contextmenu.prevent @dragstart.prevent>
    <!-- Loading / error overlay -->
    <div v-if="state === 'loading'" class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 text-white bg-ink">
      <Loader2 :size="32" class="animate-spin text-primary" />
      <p class="text-sm text-stone-300">جارٍ تجهيز جلسة التشغيل الآمنة…</p>
    </div>

    <div v-else-if="state === 'error'" class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 text-white bg-ink px-6 text-center">
      <ShieldAlert :size="36" class="text-amber" />
      <p class="text-sm font-medium">{{ errorMessage }}</p>
      <div class="flex gap-2">
        <button class="btn-outline !text-ink !text-xs" @click="$emit('reload')">إعادة المحاولة</button>
        <a v-if="whatsapp" :href="whatsapp" target="_blank" rel="noopener" class="btn-emerald !text-xs">
          <MessageCircle :size="14" /> تواصل عبر WhatsApp
        </a>
      </div>
    </div>

    <video
      ref="videoEl"
      class="h-full w-full"
      controls
      playsinline
      controlsList="nodownload noplaybackrate noremoteplayback"
      disablepictureinpicture
      disableremoteplayback
      @timeupdate="onTimeUpdate"
      @pause="saveCurrentPosition"
      @ended="onEnded"
    />

    <!-- Dynamic watermark -->
    <div v-if="watermark.enabled" class="watermark-layer" aria-hidden="true">
      <div class="watermark-item" :style="{ top: wmPos.top, insetInlineStart: wmPos.left }">
        <div>{{ watermark.lines[0] }}</div>
        <div class="text-[11px] opacity-80">{{ watermark.lines[1] }} · {{ watermark.lines[2] }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, computed } from 'vue';
import api from '../api/client';
import { getDeviceId } from '../utils/device';
import { whatsappUrl } from '../stores/settings';
import { Loader2, ShieldAlert, MessageCircle } from '@lucide/vue';

const props = defineProps({
  lessonId: { type: [Number, String], required: true },
  courseName: { type: String, default: '' },
});
const emit = defineEmits(['progress', 'completed', 'locked', 'reload']);

const videoEl = ref(null);
const state = ref('loading');
const errorMessage = ref('');
const watermark = reactive({ enabled: false, lines: [] });
const wmPos = reactive({ top: '12%', left: '8%' });

let player = null;
let session = null;
let heartbeatTimer = null;
let watermarkTimer = null;

const whatsapp = computed(() => whatsappUrl(props.courseName));

async function start() {
  try {
    const { data } = await api.post('/video/session', {
      lesson_id: props.lessonId,
      device_id: getDeviceId(),
    });
    session = data;

    Object.assign(watermark, data.watermark || { enabled: false, lines: [] });

    const shaka = await import('shaka-player/dist/shaka-player.compiled');
    if (!shaka.Player.isBrowserSupported()) {
      throw new Error('متصفحك لا يدعم تشغيل الفيديو المحمي.');
    }

    player = new shaka.Player();
    await player.attach(videoEl.value);
    player.configure({
      streaming: { bufferingGoal: 30, rebufferingGoal: 4 },
    });
    player.addEventListener('error', (e) => {
      state.value = 'error';
      errorMessage.value = 'تعذر تشغيل الفيديو. قد تكون صلاحية الجلسة انتهت.';
    });

    await player.load(data.manifest_url);

    const targetPos = Number(data.resume_position) || 0;
    if (targetPos > 2) {
      const applyResume = () => {
        try {
          if (videoEl.value && Math.abs(videoEl.value.currentTime - targetPos) > 1) {
            videoEl.value.currentTime = targetPos;
          }
        } catch { /* ignore */ }
      };
      applyResume();
      videoEl.value.addEventListener('loadedmetadata', applyResume, { once: true });
      videoEl.value.addEventListener('canplay', applyResume, { once: true });
    }

    state.value = 'ready';
    startHeartbeat(data.heartbeat_interval || 20);
    startWatermark(data.watermark?.interval_seconds || 45);
  } catch (e) {
    state.value = 'error';
    const status = e?.response?.status;
    if (status === 403) {
      errorMessage.value = e.response?.data?.message || 'هذا الكورس غير مفعل على حسابك.';
      emit('locked', errorMessage.value);
    } else if (status === 409) {
      errorMessage.value = e.response?.data?.message || 'يوجد تشغيل نشط على جهاز آخر.';
    } else if (status === 429) {
      errorMessage.value = e.response?.data?.message || 'وصلت لحد الأجهزة المسموح.';
    } else {
      errorMessage.value = e?.response?.data?.message || e?.message || 'تعذر بدء جلسة التشغيل.';
    }
  }
}

function startHeartbeat(interval) {
  heartbeatTimer = setInterval(async () => {
    if (!session || !videoEl.value) return;
    try {
      const { data } = await api.post('/video/heartbeat', {
        session_id: session.session_id,
        session_token: session.session_token,
        position_seconds: Math.floor(videoEl.value.currentTime || 0),
        duration_seconds: Math.floor(videoEl.value.duration || 0),
      });
      session.expires_at = data.expires_at;
      emit('progress', {
        position: Math.floor(videoEl.value.currentTime || 0),
        duration: Math.floor(videoEl.value.duration || 0),
      });
    } catch (e) {
      const status = e?.response?.status;
      if (status === 440 || status === 403) {
        stopPlayback(e.response?.data?.message || 'انتهت جلسة التشغيل.');
      }
    }
  }, interval * 1000);
}

function startWatermark(interval) {
  if (!watermark.enabled) return;
  const move = () => {
    wmPos.top = `${8 + Math.random() * 72}%`;
    wmPos.left = `${6 + Math.random() * 70}%`;
  };
  move();
  watermarkTimer = setInterval(move, interval * 1000);
}

function stopPlayback(message) {
  errorMessage.value = message;
  state.value = 'error';
  cleanup();
}

let lastSavedPos = 0;
let lastSaveTime = 0;

function onTimeUpdate() {
  if (!videoEl.value) return;
  const cur = Math.floor(videoEl.value.currentTime || 0);
  const now = Date.now();
  // Auto-save every 4 seconds or on significant progress
  if (now - lastSaveTime > 4000 && Math.abs(cur - lastSavedPos) >= 3) {
    saveCurrentPosition();
  }
}

async function saveCurrentPosition() {
  if (!videoEl.value || !props.lessonId) return;
  const cur = Math.floor(videoEl.value.currentTime || 0);
  const dur = Math.floor(videoEl.value.duration || 0);
  if (cur <= 0) return;
  lastSavedPos = cur;
  lastSaveTime = Date.now();
  try {
    await api.post('/me/progress', {
      lesson_id: props.lessonId,
      position_seconds: cur,
      duration_seconds: dur,
    });
  } catch { /* ignore */ }
}

function onEnded() {
  saveCurrentPosition();
  emit('completed');
}

async function endSession() {
  if (!session) return;
  try {
    await api.post('/video/session/end', {
      session_id: session.session_id,
      session_token: session.session_token,
    });
  } catch { /* session may already be gone */ }
}

function cleanup() {
  clearInterval(heartbeatTimer);
  clearInterval(watermarkTimer);
  heartbeatTimer = watermarkTimer = null;
}

onMounted(start);

onBeforeUnmount(async () => {
  saveCurrentPosition();
  cleanup();
  await endSession();
  if (player) {
    try { await player.destroy(); } catch { /* noop */ }
    player = null;
  }
});
</script>
