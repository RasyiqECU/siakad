"use client";
import { useQuery } from "@tanstack/react-query";
import { listUsers, type UserListParams } from "@/services/api/users";
import type { User } from "@/types/user";

/** Bentuk filter dari halaman Manajemen Pengguna (string kosong = tanpa filter). */
export type UserFilters = {
  search: string;
  role: string;
  /** "" = Semua, "aktif", "nonaktif" */
  status: string;
  page: number;
};

function toParams(filters: UserFilters): UserListParams {
  return {
    search: filters.search || undefined,
    role: filters.role || undefined,
    isActive:
      filters.status === "aktif"
        ? true
        : filters.status === "nonaktif"
          ? false
          : undefined,
    page: filters.page,
    perPage: 15,
  };
}

export function useUsers(filters: UserFilters, enabled = true) {
  return useQuery({
    queryKey: ["users", filters],
    queryFn: () => listUsers(toParams(filters)),
    enabled,
    placeholderData: (prev) => prev,
  });
}

/** Daftar ringkas 100 user pertama — untuk dropdown filter (mis. filter audit log). */
export function useUserOptions(perPage = 100, enabled = true) {
  return useQuery({
    queryKey: ["users", { options: perPage }],
    queryFn: () => listUsers({ perPage }),
    enabled,
    staleTime: 60_000,
  });
}

export type { User };
