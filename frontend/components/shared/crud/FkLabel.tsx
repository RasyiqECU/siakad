"use client";
/**
 * Label relasi FK di sel tabel: fetch list resource (cache bersama
 * useResourceOptions) lalu tampilkan nama sesuai konfigurasi.
 * Render "…", "—", atau "#id" bila data tidak ada — tidak pernah throw.
 */
import { labelOfItem, useResourceOptions } from "./options";
import type { CrudOptionSource } from "./types";

const FkLabel = ({
  endpoint,
  id,
  params,
  labelKey,
  labelParts,
  labelOf,
  className,
}: {
  endpoint: string;
  id: number | string | null | undefined;
  params?: Record<string, string | number>;
  labelKey?: string;
  labelParts?: string[];
  labelOf?: CrudOptionSource["labelOf"];
  className?: string;
}) => {
  const { rows, isLoading } = useResourceOptions(endpoint, params);
  if (id == null || id === "") {
    return <span className="text-muted">—</span>;
  }
  if (isLoading) {
    return <span className="text-muted fs-13">&hellip;</span>;
  }
  const row = rows.find((r) => String(r.id) === String(id));
  if (!row) return <span className="text-muted fs-13">#{String(id)}</span>;
  return (
    <span className={className}>
      {labelOfItem(row, { labelKey, labelParts, labelOf })}
    </span>
  );
};

export default FkLabel;
