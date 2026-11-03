"use client";
/**
 * Hook engine CRUD: list query (react-query) + create/update/delete mutations +
 * state modal form & ConfirmDialog — dipakai ResourceCrudPage.
 */
import { useState, type ReactNode } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "react-toastify";
import { getApiErrorMessage } from "@/lib/apiError";
import type { ApiMeta } from "@/types/api";
import {
  createResource,
  destroyResource,
  listResource,
  updateResource,
} from "@/services/api/master";
import type {
  CrudFilterState,
  CrudPageConfig,
} from "./types";

export type CrudConfirmState = {
  open: boolean;
  busy: boolean;
  title: string;
  message: ReactNode;
  variant: "danger" | "primary" | "warning";
  confirmLabel?: string;
  onConfirm: () => Promise<void>;
};

/** Context untuk extraActions per-row (mis. "Aktifkan" semester / "Kelola MK"). */
export type CrudRowActionContext<TRow> = {
  row: TRow;
  busyId: string | number | null;
  setBusyId: (id: string | number | null) => void;
  /** tampilkan ConfirmDialog lalu jalankan `run` */
  confirmAction: (opts: {
    title: string;
    message: ReactNode;
    variant?: "danger" | "primary" | "warning";
    confirmLabel?: string;
    run: () => Promise<void>;
  }) => void;
  refresh: () => void;
};

export function useResourceCrud<TRow extends { id: number | string }>(
  config: CrudPageConfig<TRow>,
  filters: CrudFilterState,
  readAllowed: boolean,
) {
  const queryClient = useQueryClient();

  // ============ list query ============
  const toQuery = (f: CrudFilterState) => {
    const query: Record<string, string | number | undefined> = {
      search: f.search || undefined,
      page: f.page,
      per_page: config.perPage ?? 15,
    };
    for (const filter of config.filterFields ?? []) {
      const value = (f as CrudFilterState)[filter.key];
      if (value !== "" && value !== undefined && value !== null) {
        query[filter.key] = value;
      }
    }
    return query;
  };

  const listQuery = useQuery({
    queryKey: ["crud", config.endpoint, filters],
    queryFn: () => listResource<TRow>(config.endpoint, toQuery(filters)),
    enabled: readAllowed,
    placeholderData: (prev) => prev,
  });

  const refresh = () =>
    void queryClient.invalidateQueries({ queryKey: ["crud", config.endpoint] });

  // ============ mutations ============
  const createMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) =>
      createResource(config.endpoint, payload),
  });
  const updateMutation = useMutation({
    mutationFn: ({
      id,
      payload,
    }: {
      id: number | string;
      payload: Record<string, unknown>;
    }) => updateResource(config.endpoint, id, payload),
  });
  const deleteMutation = useMutation({
    mutationFn: (id: number | string) => destroyResource(config.endpoint, id),
  });

  const submit = async (payload: Record<string, unknown>): Promise<void> => {
    if (editing) {
      await updateMutation.mutateAsync({
        id: editing.id as number,
        payload,
      });
      toast.success(`${config.apiModuleLabel} berhasil diperbarui.`);
    } else {
      await createMutation.mutateAsync(payload);
      toast.success(`${config.apiModuleLabel} berhasil ditambahkan.`);
    }
    refresh();
  };

  // ============ form modal state ============
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<TRow | null>(null);

  const openCreate = () => {
    setEditing(null);
    setFormOpen(true);
  };
  const openEdit = (row: TRow) => {
    setEditing(row);
    setFormOpen(true);
  };
  const closeForm = () => setFormOpen(false);

  // ============ confirm dialog state ============
  const [confirmState, setConfirmState] = useState<CrudConfirmState>({
    open: false,
    busy: false,
    title: "",
    message: null,
    variant: "danger",
    onConfirm: async () => {},
  });

  const confirmAction = (opts: {
    title: string;
    message: ReactNode;
    variant?: "danger" | "primary" | "warning";
    confirmLabel?: string;
    run: () => Promise<void>;
  }) =>
    setConfirmState({
      open: true,
      busy: false,
      title: opts.title,
      message: opts.message,
      variant: opts.variant ?? "danger",
      confirmLabel: opts.confirmLabel,
      onConfirm: opts.run,
    });

  const closeConfirm = () =>
    setConfirmState((prev) => ({ ...prev, open: false, busy: false }));

  // ============ aksi per-baris ============
  const [busyId, setBusyId] = useState<string | number | null>(null);

  const defaultRowLabel = (row: TRow): string => {
    const name = (row as Record<string, unknown>).name;
    return name ? String(name) : `${config.apiModuleLabel} #${row.id}`;
  };

  const askDelete = (row: TRow) => {
    const target = (config.rowLabel ?? defaultRowLabel)(row);
    confirmAction({
      title: `Hapus ${config.apiModuleLabel}`,
      message: (
        <>
          Hapus data <strong>{target}</strong>? Data akan ditandai terhapus
          (soft delete) dan tidak muncul lagi pada daftar.
        </>
      ),
      variant: "danger",
      run: async () => {
        setConfirmState((c) => ({ ...c, busy: true }));
        try {
          await deleteMutation.mutateAsync(row.id as number);
          toast.success(`${config.apiModuleLabel} berhasil dihapus.`);
        } catch (error) {
          toast.error(getApiErrorMessage(error));
        } finally {
          setConfirmState((c) => ({ ...c, open: false, busy: false }));
          refresh();
        }
      },
    });
  };

  return {
    rows: listQuery.data?.data ?? null,
    meta: listQuery.data?.meta as ApiMeta | undefined,
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    refresh,
    formOpen,
    editing,
    openCreate,
    openEdit,
    closeForm,
    submit,
    busyId,
    setBusyId,
    askDelete,
    confirmState,
    confirmAction,
    closeConfirm,
  };
}

export type CrudPageApi<TRow extends { id: number | string }> = ReturnType<
  typeof useResourceCrud<TRow>
>;
