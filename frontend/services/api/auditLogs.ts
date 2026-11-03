import api from "@/lib/api";
import type { ApiResponse } from "@/types/api";
import type { AuditLogItem, Paginated } from "@/types/user";

/** Endpoint audit log — kontrak API_SPECIFICATION.md. */

export type AuditLogListParams = {
  userId?: number;
  /** created | updated | deleted */
  action?: string;
  page?: number;
  perPage?: number;
};

export async function listAuditLogs(
  params?: AuditLogListParams,
): Promise<Paginated<AuditLogItem>> {
  const query: Record<string, string | number> = {};
  if (params?.userId) query.user_id = params.userId;
  if (params?.action) query.action = params.action;
  if (params?.page) query.page = params.page;
  if (params?.perPage) query.per_page = params.perPage;

  const res = await api.get<ApiResponse<AuditLogItem[]>>("/audit-logs", {
    params: query,
  });
  return { data: res.data.data ?? [], meta: res.data.meta! };
}
