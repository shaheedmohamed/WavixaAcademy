import { reactive } from 'vue';

let seq = 0;

export const toasts = reactive([]);

export function toast(message, type = 'success', duration = 4000) {
  const id = ++seq;
  toasts.push({ id, message, type });
  setTimeout(() => dismiss(id), duration);
}

export function dismiss(id) {
  const i = toasts.findIndex((t) => t.id === id);
  if (i > -1) toasts.splice(i, 1);
}
