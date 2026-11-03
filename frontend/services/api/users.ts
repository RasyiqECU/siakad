import api from "@/lib/api";
import type { ApiResponse } from "@/types/api";
import type { Paginated, User, UserPayload } from "@/types/user";

/** Endpoint manajemen pengguna — kontrak API_SPECIFICATION.md. */

export type UserListParams = {
  search?: string;
  role?: string;
  /** true → hanya aktif, false → hanya nonaktif. */
  isActive?: boolean;
  page?: number;
  perPage?: number;
};

function toQuery(params?: UserListParams) {
  const query: Record<string, string | number> = {};
  if (params?.search) query.search = params.search;
  if (params?.role) query.role = params.role;
  if (params?.isActive !== undefined) query.is_active = params.isActive ? 1 : 0;
  if (params?.page) query.page = params.page;
  if (params?.perPage) query.per_page = params.perPage;
  return query;
}

export async function listUsers(
  params?: UserListParams,
): Promise<Paginated<User>> {
  const res = await api.get<ApiResponse<User[]>>("/users", {
    params: toQuery(params),
  });
  return { data: res.data.data ?? [], meta: res.data.meta! };
}

export async function getUser(id: number): Promise<User> {
  const res = await api.get<ApiResponse<User>>(`/users/${id}`);
  return res.data.data as User;
}

export async function createUser(
  payload: Required<Pick<UserPayload, "name" | "email">> &
    UserPayload & { password: string },
): Promise<void> {
  await api.post("/users", {
    phone: null,
    ...payload,
  });
}

export async function updateUser(
  id: number,
  payload: UserPayload,
): Promise<void> {
  await api.patch(`/users/${id}`, payload);
}

export async function destroyUser(id: number): Promise<void> {
  await api.delete(`/users/${id}`);
}

export async function activateUser(id: number): Promise<void> {
  await api.patch(`/users/${id}/activate`);
}

export async function deactivateUser(id: number): Promise<void> {
  await api.patch(`/users/${id}/deactivate`);
}

export async function resetUserPassword(
  id: number,
  password: string,
): Promise<void> {
  await api.patch(`/users/${id}/reset-password`, { password });
}
