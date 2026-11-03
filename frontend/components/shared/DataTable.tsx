"use client";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { Fragment, type ReactNode } from "react";
import { Placeholder, Table } from "react-bootstrap";

/**
 * Tabel data generik tema Adminto:
 * - skeleton 5 baris saat memuat
 * - baris kosong ramah (ikon + pesan) saat data null/[]
 * - optional kolom aksi per-baris (render akhir)
 */
export type DataTableColumn<TRow> = {
  key: string;
  label: string;
  render?: (row: TRow) => ReactNode;
  className?: string;
};

/** key stabil baris: pakai row.id bila ada, fallback index. */
const rowKey = <TRow,>(row: TRow, index: number): string | number => {
  const id = (row as { id?: unknown }).id;
  if (typeof id === "string" || typeof id === "number") return id;
  return index;
};

const DataTable = <TRow,>({
  columns,
  rows,
  isLoading,
  emptyMessage = "Tidak ada data untuk ditampilkan.",
  actions,
  actionsHeaderClassName = "text-end",
}: {
  columns: DataTableColumn<TRow>[];
  /** null/undefined = belum ada data; [] = sudah termuat kosong. */
  rows: TRow[] | null | undefined;
  isLoading: boolean;
  emptyMessage?: string;
  actions?: (row: TRow) => ReactNode;
  actionsHeaderClassName?: string;
}) => {
  const hasActions = Boolean(actions);
  const colSpan = columns.length + (hasActions ? 1 : 0);

  return (
    <Table className="table-hover table-wrap align-middle mb-0" responsive>
      <thead className="table-light">
        <tr>
          {columns.map((col) => (
            <th key={col.key} className={col.className}>
              {col.label}
            </th>
          ))}
          {hasActions && (
            <th className={actionsHeaderClassName}>Aksi</th>
          )}
        </tr>
      </thead>
      <tbody>
        {isLoading ? (
          Array.from({ length: 5 }).map((_, rowIdx) => (
            <tr key={`skeleton-${rowIdx}`}>
              {columns.map((col) => (
                <td key={col.key} className={col.className}>
                  <Placeholder as="p" animation="glow" className="mb-0">
                    <Placeholder xs={8} className="rounded" />
                  </Placeholder>
                </td>
              ))}
              {hasActions && (
                <td className={actionsHeaderClassName}>
                  <Placeholder as="p" animation="glow" className="mb-0">
                    <Placeholder xs={4} className="rounded" />
                  </Placeholder>
                </td>
              )}
            </tr>
          ))
        ) : !rows || rows.length === 0 ? (
          <tr>
            <td colSpan={colSpan} className="text-center py-5">
              <div className="avatar-lg mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-2">
                <IconifyIcon
                  icon="ri:inbox-line"
                  className="fs-24 text-muted"
                />
              </div>
              <p className="text-muted mb-0 fs-14">{emptyMessage}</p>
            </td>
          </tr>
        ) : (
          rows.map((row, rowIdx) => (
            <tr key={rowKey(row, rowIdx)}>
              {columns.map((col) => (
                <td key={col.key} className={col.className}>
                  {col.render
                    ? col.render(row)
                    : ((row as Record<string, unknown>)[col.key] as ReactNode) ??
                      "—"}
                </td>
              ))}
              {hasActions && (
                <td className={actionsHeaderClassName}>
                  <Fragment>{actions!(row)}</Fragment>
                </td>
              )}
            </tr>
          ))
        )}
      </tbody>
    </Table>
  );
};

export default DataTable;
