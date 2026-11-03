/** Kontrak response standar backend SIMORA (lihat docs/API_SPECIFICATION.md §1). */

export type ApiMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
};

export type ApiLinks = {
  first: string;
  last: string;
  prev: string | null;
  next: string | null;
};

export type ApiResponse<TData> = {
  success: boolean;
  message: string;
  data: TData | null;
  meta?: ApiMeta;
  links?: ApiLinks;
  errors?: Record<string, string[]>;
};

/** Data user dari GET /auth/me & POST /auth/login (tanpa student/lecturer detail — Phase 2+). */
export type AuthUser = {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  roles: string[];
  permissions: string[];
};

export type LoginResponse = {
  token: string;
  user: AuthUser;
};
