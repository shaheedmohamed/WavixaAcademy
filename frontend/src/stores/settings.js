import { reactive } from 'vue';
import api from '../api/client';

export const settings = reactive({
  loaded: false,
  platform_name: 'Wavixa Academy',
  logo: null,
  whatsapp_number: '',
  support_email: '',
  footer_about: '',
  social_links: {},
});

export async function loadSettings(force = false) {
  if (settings.loaded && !force) return settings;
  const { data } = await api.get('/settings/public');
  Object.assign(settings, data, { loaded: true });
  return settings;
}

export function whatsappUrl(courseName) {
  if (!settings.whatsapp_number) return null;
  const template = settings.whatsapp_message_template || 'مرحبًا، أريد التسجيل في كورس: {course_name}';
  const text = template.replace('{course_name}', courseName || '');
  const number = String(settings.whatsapp_number).replace(/\D/g, '');
  return `https://wa.me/${number}?text=${encodeURIComponent(text)}`;
}
