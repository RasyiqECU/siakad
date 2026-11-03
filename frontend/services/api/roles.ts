import api from "@/lib/api";
import type { ApiResponse } from "@/types/api";
import type { Role } from "@/types/user";

/** Endpoint role & assignment permission — kontrak API_SPECIFICATION.md. */

export async function listRoles(): Promise<Role[]> {
  const res = await api.get<ApiResponse<Role[]>>("/roles");
  return res.data.data ?? [];
}

export async function updateRolePermissions(
  id: number,
  permissions: string[],
): Promise<void> {
  await api.put(`/roles/${id}/permissions`, { permissions });
}
