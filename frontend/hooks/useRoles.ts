"use client";
import { useQuery } from "@tanstack/react-query";
import { listRoles } from "@/services/api/roles";

/** Semua role (untuk dropdown filter & checkbox form pengguna). */
export function useRoles(enabled = true) {
  return useQuery({
    queryKey: ["roles"],
    queryFn: listRoles,
    enabled,
    staleTime: 5 * 60_000,
  });
}
