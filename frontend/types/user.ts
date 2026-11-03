/** Tipe entitas pengguna, role & audit log — kontrak API_SPECIFICATION.md (Phase 2). */
import type { ApiMeta } from "@/types/api";

/** Referensi role ringkas (ada di User.roles). */
export type RoleRef = {
  id: number;
  name: string;
  display_name: string;
};

/** Baris GET /users (juga GET /users/{id}). */
export type User = {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  phone: string | null;
  is_active: boolean;
  roles: RoleRef[];
  created_at: string;
  deleted_at: string | null;
};

/** Item GET /users/{id}/reset-password payload & PATCH /users payload. */
export type UserPayload = {
  name?: string;
  email?: string;
  phone?: string | null;
  is_active?: boolean;
  roles?: string[];
};

/** Data profil: seperti AuthUser + detail null mahasiswa/dosen (bukan mereka). */
export type ProfileUser = {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  roles: string[];
  permissions: string[];
  student: unknown | null;
  lecturer: unknown | null;
};

/** Role dari GET /roles. */
export type Role = {
  id: number;
  name: string;
  display_name: string;
  permissions: { id: number; name: string }[];
};

/** Baris GET /audit-logs. */
export type AuditLogItem = {
  id: number;
  user: { id: number; name: string; email: string } | null;
  action: string;
  subject_type: string | null;
  subject_id: number | null;
  properties: Record<string, unknown> | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
};

/** Hasil endpoint terpaginasi: daftar item + meta total halaman. */
export type Paginated<TItem> = {
  data: TItem[];
  meta: ApiMeta;
};
