import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

export function csrf() {
  return axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

export function unwrapError(error) {
  const res = error?.response;
  if (res?.data?.errors) {
    return res.data.errors;
  }
  return res?.data?.message || 'حدث خطأ غير متوقع. حاول مرة أخرى.';
}

export default api;
