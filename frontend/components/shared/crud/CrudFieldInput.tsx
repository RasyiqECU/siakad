"use client";
/**
 * Input satu field form CRUD — digenerate dari CrudFieldConfig.
 * Hook options (useQuery) hidup di level komponen ini, jadi tiap field punya
 * query sendiri (Rules of Hooks aman: satu komponen = satu field).
 * Cascade select: `paramFromField` mengambil nilai field lain dari form.
 */
import { useMemo } from "react";
import { useWatch, type Control } from "react-hook-form";
import { Controller } from "react-hook-form";
import { Form } from "react-bootstrap";
import { labelOfItem, useResourceOptions } from "./options";
import type { CrudFieldConfig, CrudOption } from "./types";
import type { CrudFormValues } from "./schema";

const CrudFieldInput = ({
  field,
  control,
  isInvalid,
}: {
  field: CrudFieldConfig;
  control: Control<CrudFormValues>;
  isInvalid?: boolean;
}) => {
  const values = useWatch({ control }) as CrudFormValues | undefined;
  const source = field.options;
  const resourceSource = source?.kind === "resource" ? source : null;

  const depField = resourceSource?.paramFromField?.field;
  const depValue = depField ? values?.[depField] : undefined;
  const depPresent =
    depValue !== "" && depValue !== undefined && depValue !== null;

  const params = useMemo<Record<string, string | number> | undefined>(() => {
    if (!resourceSource) return undefined;
    return {
      per_page: 100,
      ...(resourceSource.params ?? {}),
      ...(resourceSource.paramFromField && depPresent
        ? { [resourceSource.paramFromField.param]: String(depValue) }
        : {}),
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [resourceSource?.endpoint, resourceSource?.paramFromField, depPresent, depValue]);

  const optionsQuery = useResourceOptions(
    resourceSource?.endpoint ?? "",
    params,
    Boolean(resourceSource) && (!depField || depPresent),
  );

  const options: CrudOption[] = useMemo(() => {
    if (!source) return [];
    if (source.kind === "static") return source.options;
    return optionsQuery.rows.map((row) => ({
      value: row.id as string | number,
      label: labelOfItem(row, resourceSource),
    }));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [source, optionsQuery.rows]);

  const selectLoading =
    Boolean(resourceSource) && optionsQuery.isLoading && !optionsQuery.rows.length;

  return (
    <Controller<CrudFormValues>
      name={field.name}
      control={control}
      render={({ field: f }) => {
        const invalidClass = isInvalid ? "is-invalid" : "";
        switch (field.type) {
          case "textarea":
            return (
              <Form.Control
                {...f}
                as="textarea"
                rows={3}
                value={(f.value ?? "") as string}
                placeholder={field.placeholder}
                className={invalidClass}
              />
            );
          case "number":
            return (
              <Form.Control
                {...f}
                type="number"
                min={field.min}
                max={field.max}
                value={(f.value ?? "") as string}
                placeholder={field.placeholder}
                className={invalidClass}
              />
            );
          case "date":
            return (
              <Form.Control
                {...f}
                type="date"
                value={(f.value ?? "") as string}
                className={invalidClass}
              />
            );
          case "color":
            return (
              <Form.Control
                {...f}
                type="color"
                value={(f.value ?? "#0d6efd") as string}
                className={invalidClass}
              />
            );
          case "checkbox":
            return (
              <Form.Check
                type="checkbox"
                id={`crud-field-${field.name}`}
                label={field.label}
                checked={Boolean(f.value)}
                onChange={(e) => f.onChange(e.target.checked)}
              />
            );
          case "select":
            return (
              <Form.Select
                {...f}
                value={(f.value ?? "") as string}
                disabled={selectLoading}
                aria-label={field.label}
                className={invalidClass}
              >
                <option value="">
                  {depField && !depPresent
                    ? `Pilih ${field.label.toLowerCase()} terlebih dahulu…`
                    : `— Pilih ${field.label} —`}
                </option>
                {options.map((opt) => (
                  <option key={String(opt.value)} value={String(opt.value)}>
                    {opt.label}
                  </option>
                ))}
              </Form.Select>
            );
          default:
            return (
              <Form.Control
                {...f}
                type={field.subtype ?? "text"}
                value={(f.value ?? "") as string}
                placeholder={field.placeholder}
                className={invalidClass}
              />
            );
        }
      }}
    />
  );
};

export default CrudFieldInput;
