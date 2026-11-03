"use client";
import { useQuery } from "@tanstack/react-query";
import { listAuditLogs } from "@/services/api/auditLogs";

/** Bentuk filter halaman Audit Log (string kosong / 0 = tanpa filter). */
export type AuditLogFilters = {
  userId: number | "";
  action: string;
  page: number;
};

function toParams(filters: AuditLogFilters) {
  return {
    userId: filters.userId === "" ? undefined : filters.userId,
    action: filters.action || undefined,
    page: filters.page || undefined,
    perPage: 15,
  };
}

export function useAuditLogs(filters: AuditLogFilters, enabled = true) {
  return useQuery({
    queryKey: ["audit-logs", filters],
    queryFn: () => listAuditLogs(toParams(filters)),
    enabled,
    placeholderData: (prev) => prev,
  });
}
