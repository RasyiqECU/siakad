/**
 * Konfigurasi engine CRUD Data Master (config-driven) — Phase 3.
 * Satu set konfigurasi mendefinisikan halaman CRUD: filter, kolom, form.
 */
import type { DataTableColumn } from "@/components/shared/DataTable";
import type { ZodTypeAny } from "zod";

/** Satu pilihan pada select statis. */
export type CrudOption = {
  value: string | number;
  label: string;
};

/**
 * Sumber opsi select: statis atau dari file-master endpoint lain.
 * Untuk `resource`:
 * - `paramFromField` memungkinkan cascade — value diambil dari field lain
 *   di form (atau field filter lain di filter bar).
 * - `numeric` = nilai opsi adalah angka (untuk FK) → discast saat submit.
 */
export type CrudOptionSource = {
  endpoint: string;
  /** param query konstan, mis. { per_page: 100 }. */
  params?: Record<string, string | number>;
  /** cascade: ambil param dari field lain pada form/filter yang sama. */
  paramFromField?: { field: string; param: string };
  /** label dari satu key (default "name"). */
  labelKey?: string;
  /** label gabungan, mis. ["code","name"] → "KODE — Nama". */
  labelParts?: string[];
  /** fungsi label manual (menimpa labelKey/labelParts). */
  labelOf?: (item: Record<string, unknown>) => string;
  /** nilai opsi numeric (FK id) → discast Number saat submit. */
  numeric?: boolean;
};

export type CrudOptionsInput =
  | { kind: "static"; options: CrudOption[] }
  | ({ kind: "resource" } & CrudOptionSource);

export type CrudFieldType =
  | "text"
  | "textarea"
  | "number"
  | "select"
  | "date"
  | "checkbox"
  | "color";

/** Definisi satu field pada form create/update. */
export type CrudFieldConfig = {
  name: string;
  label: string;
  type: CrudFieldType;
  colMd?: number;
  required?: boolean;
  min?: number;
  max?: number;
  /** tipe input html tambahan untuk `text`. */
  subtype?: "email" | "tel" | "password";
  options?: CrudOptionsInput;
  placeholder?: string;
  /** teks bantuan kecil di bawah input. */
  helperText?: string;
  /** judul seksi form (render tanpa input). */
  heading?: string;
  /** nilai default mode create (mis. checkbox is_active = true). */
  defaultValue?: unknown;
  /** override schema validasi per field. */
  zod?: ZodTypeAny;
  /** sembunyikan saat mode edit (mis. password akun). */
  hideOnEdit?: boolean;
};

/** Satu select di filter bar; key = nama param query filter. */
export type CrudFilterConfig = {
  key: string;
  /** teks option kosong, mis. "Semua Fakultas". */
  label: string;
  options?: CrudOptionsInput;
  colMd?: number;
};

export type CrudPageSize = "sm" | "lg" | "xl";

/** Konfigurasi seluruh halaman CRUD satu resource. */
export type CrudPageConfig<TRow> = {
  /** endpoint dasar, mis. /faculties. */
  endpoint: string;
  /** permission baca, mis. faculties.read ( tulis → ".write"). */
  permission: string;
  title: string;
  subtitle?: string;
  /** label modul untuk toast, modal & konfirmasi (mis. "Fakultas"). */
  apiModuleLabel: string;
  columns: DataTableColumn<TRow>[];
  formFields: CrudFieldConfig[];
  filterFields?: CrudFilterConfig[];
  /** identitas row untuk pesan konfirmasi hapus (default: name). */
  rowLabel?: (row: TRow) => string;
  perPage?: number;
  /** ukuran modal form ("lg" untuk resource maha fungsi). */
  formSize?: CrudPageSize;
};

/** state filter halaman: search + page + dropdown config (string kosong = tanpa filter). */
export type CrudFilterState = {
  search: string;
  page: number;
} & Record<string, string | number>;
