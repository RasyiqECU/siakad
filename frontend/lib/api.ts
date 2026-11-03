import axios, { AxiosError, type InternalAxiosRequestConfig } from "axios";
import { TOKEN_KEY } from "@/context/constants";
import { clearAuth } from "@/lib/auth";

/**
 * Axios instance untuk seluruh komunikasi dengan backend SIMORA.
 * baseURL dari NEXT_PUBLIC_API_URL (lihat .env.local).
 */
const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  headers: { Accept: "application/json" },
});

api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  if (typeof window !== "undefined") {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    // Sesi berakhir (token revoke/expired) → bersihkan sesi & kembali ke login
    if (typeof window !== "undefined" && error.response?.status === 401) {
      clearAuth();
      const { pathname } = window.location;
      if (!pathname.startsWith("/login")) {
        window.location.href = `/login?from=${encodeURIComponent(pathname)}`;
      }
    }
    return Promise.reject(error);
  },
);

export default api;
