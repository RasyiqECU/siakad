import api from "@/lib/api";
import type { ApiResponse, AuthUser, LoginResponse } from "@/types/api";

/** Endpoint autentikasi — kontrak sesuai docs/API_SPECIFICATION.md §2. */

export async function login(
  email: string,
  password: string,
  deviceName = "browser",
): Promise<LoginResponse> {
  const res = await api.post<ApiResponse<LoginResponse>>("/auth/login", {
    email,
    password,
    device_name: deviceName,
  });
  const payload = res.data.data;
  if (!payload) throw new Error("Response login tidak valid.");
  return payload;
}

export async function fetchMe(): Promise<AuthUser> {
  const res = await api.get<ApiResponse<AuthUser>>("/auth/me");
  return res.data.data as AuthUser;
}

export async function logout(): Promise<void> {
  await api.post("/auth/logout");
}
