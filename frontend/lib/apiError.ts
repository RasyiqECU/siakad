import type { AxiosError } from "axios";
import type { ApiResponse } from "@/types/api";

/** Ambil pesan error yang ramah dari respons backend (envelope ApiResponse) / axios. */
export function getApiErrorMessage(
  error: unknown,
  fallback = "Terjadi kesalahan. Coba lagi beberapa saat lagi.",
): string {
  const axiosError = error as AxiosError<ApiResponse<unknown>>;
  const data = axiosError?.response?.data;

  if (data?.errors) {
    const firstField = Object.values(data.errors).find(
      (messages) => messages && messages.length > 0,
    );
    if (firstField?.[0]) return firstField[0];
  }
  if (data?.message) return data.message;

  if (axiosError && !axiosError.response) {
    return "Tidak dapat menghubungi server. Pastikan API berjalan.";
  }
  return fallback;
}
