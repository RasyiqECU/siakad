import { TOKEN_COOKIE, TOKEN_KEY } from "@/context/constants";
import type { AuthUser } from "@/types/api";

/** Simpan hasil autentikasi: token di localStorage (untuk axios) + cookie (untuk proxy.ts). */
export function saveSession(token: string, user: AuthUser): void {
  localStorage.setItem(TOKEN_KEY, token);
  document.cookie = `${TOKEN_COOKIE}=${encodeURIComponent(token)}; path=/; SameSite=Lax`;
  saveUser(user);
}

function saveUser(user: AuthUser): void {
  localStorage.setItem("simora_user", JSON.stringify(user));
}

/**
 * Pembaruan parsial user ter-cache (localStorage "simora_user") tanpa menggeser token,
 * dipakai setelah PATCH /profile agar label sidebar/TopBar langsung ikut berubah.
 */
export function updateCachedUser(
  patch: Partial<Omit<AuthUser, "id">>,
): AuthUser | null {
  if (typeof window === "undefined") return null;
  const current = getCachedUser();
  if (!current) return null;
  const updated = { ...current, ...patch };
  saveUser(updated);
  return updated;
}

/** Ambil user yang ter-cache (untuk tampilan awal sebelum /auth/me selesai). */
export function getCachedUser(): AuthUser | null {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem("simora_user");
  if (!raw) return null;
  try {
    return JSON.parse(raw) as AuthUser;
  } catch {
    return null;
  }
}

export function clearAuth(): void {
  if (typeof window === "undefined") return;
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem("simora_user");
  document.cookie = `${TOKEN_COOKIE}=; path=/; max-age=0`;
}

export function hasToken(): boolean {
  return typeof window !== "undefined" && Boolean(localStorage.getItem(TOKEN_KEY));
}
