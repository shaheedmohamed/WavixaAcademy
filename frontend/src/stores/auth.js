import { reactive, computed } from 'vue';
import api, { csrf } from '../api/client';
import { getDeviceId } from '../utils/device';

export const auth = reactive({
  user: null,
  ready: false,
});

export const isLoggedIn = computed(() => !!auth.user);
export const isAdmin = computed(() => !!auth.user?.is_admin);
export const isInstructor = computed(() => !!auth.user?.roles?.includes('instructor'));

export async function fetchMe() {
  try {
    const { data } = await api.get('/auth/me');
    auth.user = data.user;
  } catch {
    auth.user = null;
  } finally {
    auth.ready = true;
  }
  return auth.user;
}

export async function login(email, password) {
  await csrf();
  const { data } = await api.post('/auth/login', { email, password });
  auth.user = data.user;
  return data.user;
}

export async function register(payload) {
  await csrf();
  const { data } = await api.post('/auth/register', payload);
  auth.user = data.user;
  return data.user;
}

export async function logout() {
  try {
    await api.post('/auth/logout');
  } finally {
    auth.user = null;
  }
}

export { getDeviceId };
