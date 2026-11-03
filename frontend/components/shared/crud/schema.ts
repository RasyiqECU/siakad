/**
 * Schema zod dinamis + builder payload untuk FormModal engine CRUD.
 * RHF Form.Select/Control selalu menghasilkan string → nilai numeric FK
 * di-coerce dengan transform Number. Kolom kosong ("") diperlakukan
 * tidak dikirim (undefined → field tidak muncul di payload POST/PATCH).
 */
import z from "zod";
import type { CrudFieldConfig } from "./types";

/** sentinel string khusus "bukan angka" agar pesan salah tipe tak tertukar dg "wajib diisi". */
const NOT_A_NUMBER = "__bukan_angka__";

/** '' / null → undefined (tidak dikirim); selainnya Number() → NaN atau angka. */
const toNumberValue = (v: unknown): unknown => {
  if (v === "" || v === null || v === undefined) return undefined;
  if (typeof v === "number") return v;
  const n = Number(v);
  return Number.isNaN(n) ? NOT_A_NUMBER : n;
};

/** Field angka (input number): min/max check + required check. */
function numberFieldSchema(field: CrudFieldConfig): z.ZodTypeAny {
  const label = field.label;
  let schema = z.preprocess(toNumberValue, z.any());

  schema = schema.refine(
    (v) => v !== NOT_A_NUMBER,
    `${label} harus berupa angka valid.`,
  );
  if (field.required) {
    schema = schema.refine(
      (v) => v !== undefined,
      `${label} wajib diisi.`,
    );
  }
  if (field.min !== undefined) {
    const min = field.min;
    schema = schema.refine(
      (v) => v === undefined || (typeof v === "number" && v >= min),
      `${label} minimal ${min}.`,
    );
  }
  if (field.max !== undefined) {
    const max = field.max;
    schema = schema.refine(
      (v) => v === undefined || (typeof v === "number" && v <= max),
      `${label} maksimal ${max}.`,
    );
  }
  return schema;
}

/** text / textarea / date / color (+ email subtype). */
function stringFieldSchema(field: CrudFieldConfig): z.ZodTypeAny {
  const label = field.label;
  const requiredMsg = `${label} wajib diisi.`;

  if (field.required) {
    let schema = z.string().min(1, requiredMsg);
    if (field.subtype === "email") {
      schema = schema.email(`${label} tidak valid. Gunakan format email.`);
    }
    return schema;
  }

  const optionalSchema =
    field.subtype === "email"
      ? z
          .string()
          .email(`${label} tidak valid. Gunakan format email.`)
          .optional()
      : z.string().optional();
  return z.preprocess((v) => (v === "" ? undefined : v), optionalSchema);
}

/** select: enum string (labelKey "name" dsb.) atau FK numeric (`numeric: true`). */
function selectFieldSchema(field: CrudFieldConfig): z.ZodTypeAny {
  const source = field.options;
  const isNumeric =
    source?.kind === "resource"
      ? source.numeric !== false
      : Boolean(
          source?.kind === "static" &&
            (source as { numeric?: boolean }).numeric,
        );
  const label = field.label;
  const requiredMsg = `${label} wajib diisi.`;

  if (isNumeric) {
    let schema = z.preprocess((v) => (v === "" ? undefined : v), z.any());

    if (field.required) {
      schema = schema.refine(
        (v) => v !== undefined,
        `${label} wajib dipilih.`,
      );
    }
    // output Number bila terisi; optional kosong → undefined (tidak dikirim).
    return schema.transform((v) => (v == null ? undefined : Number(v)));
  }
  return stringFieldSchema(field);
}

/** Bangun z.object dari daftar field — output typed unknown per-field. */
export function buildZodSchema(fields: CrudFieldConfig[]) {
  const shape: Record<string, z.ZodTypeAny> = {};
  for (const field of fields) {
    if (field.heading !== undefined) continue;
    if (field.zod) {
      shape[field.name] = field.zod;
      continue;
    }
    switch (field.type) {
      case "checkbox":
        shape[field.name] = z.boolean();
        break;
      case "number":
        shape[field.name] = numberFieldSchema(field);
        break;
      case "select":
        shape[field.name] = selectFieldSchema(field);
        break;
      default:
        shape[field.name] = stringFieldSchema(field);
        break;
    }
  }
  return z.object(shape);
}

export type CrudFormValues = Record<string, unknown>;

/** defaultValues RHF dari row edit / default create. */
export function buildDefaultValues(
  fields: CrudFieldConfig[],
  editing: Record<string, unknown> | null | undefined,
): CrudFormValues {
  const values: Record<string, unknown> = {};
  for (const field of fields) {
    if (field.heading !== undefined) continue;
    const raw = editing
      ? (editing as CrudFormValues)[field.name]
      : undefined;
    if (field.type === "checkbox") {
      values[field.name] = raw == null
        ? Boolean(field.defaultValue)
        : Boolean(raw);
      continue;
    }
    const fallback =
      field.defaultValue === undefined
        ? ""
        : field.type === "number" || field.type === "select"
          ? String(field.defaultValue)
          : field.defaultValue;
    values[field.name] = raw == null || raw === "" ? fallback : String(raw);
  }
  return values;
}

/** Susun payload POST/PATCH: drop undefined/'' ; checkbox selalu boolean. */
export function buildPayload(
  values: CrudFormValues,
  fields: CrudFieldConfig[],
): Record<string, unknown> {
  const payload: Record<string, unknown> = {};
  for (const field of fields) {
    if (field.heading !== undefined) continue;
    const value = values[field.name];
    if (field.type === "checkbox") {
      payload[field.name] = Boolean(value);
      continue;
    }
    if (value === "" || value == null) continue;
    if (field.type === "number" || field.type === "select") {
      const num = Number(value);
      if (!Number.isNaN(num)) {
        payload[field.name] =
          field.type === "select" && field.options?.kind !== "resource"
            ? value
            : num;
        continue;
      }
    }
    payload[field.name] = value;
  }
  return payload;
}
