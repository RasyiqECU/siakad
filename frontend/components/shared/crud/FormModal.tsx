"use client";
/**
 * Modal form engine CRUD: generate RHF + zodResolver dari CrudFieldConfig[].
 * - create → defaultValues dari config (defaultValue); edit → dari row.
 * - cascade reset: field dependen dikosongkan bila field prasyaratnya berubah.
 * - error server (422 dsb.) ditampilkan sebagai alert di atas form.
 */
import { useEffect, useMemo, useRef, useState } from "react";
import { useForm, useWatch, type Resolver } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Button, Col, Form, Modal, Row } from "react-bootstrap";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { getApiErrorMessage } from "@/lib/apiError";
import CrudFieldInput from "./CrudFieldInput";
import {
  buildDefaultValues,
  buildPayload,
  buildZodSchema,
  type CrudFormValues,
} from "./schema";
import type { CrudFieldConfig, CrudPageSize } from "./types";

const FormModal = <TRow extends Record<string, unknown>>({
  show,
  onHide,
  editing,
  fields,
  apiModuleLabel,
  formSize = "lg",
  submit,
}: {
  show: boolean;
  onHide: () => void;
  /** null ⇒ mode tambah. */
  editing: TRow | null;
  fields: CrudFieldConfig[];
  apiModuleLabel: string;
  formSize?: CrudPageSize;
  /** dipanggil dgn payload final; throw bila gagal (error ditampilkan). */
  submit: (payload: Record<string, unknown>) => Promise<void>;
}) => {
  const [serverError, setServerError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const visibleFields = useMemo(
    () =>
      fields.filter(
        (field) =>
          field.heading !== undefined ||
          !(field.hideOnEdit && Boolean(editing)),
      ),
    [fields, editing],
  );

  const schema = useMemo(() => buildZodSchema(visibleFields), [visibleFields]);
  const defaultValues = useMemo(
    () => buildDefaultValues(visibleFields, editing),
    [visibleFields, editing],
  );

  const {
    control,
    handleSubmit,
    reset,
    setValue,
  } = useForm<CrudFormValues>({
    resolver: zodResolver(schema) as unknown as Resolver<CrudFormValues>,
    defaultValues,
  });

  // reset form saat modal dibuka dgn row berbeda
  useEffect(() => {
    reset(defaultValues);
    setServerError(null);
  }, [defaultValues, reset]);

  // ---- cascade reset: kosongkan field dependen saat field prasyarat berubah ----
  const values = useWatch({ control }) as CrudFormValues | undefined;
  const depRef = useRef<Record<string, unknown>>({});
  useEffect(() => {
    if (!values) return;
    const deps = visibleFields.flatMap(
      (f) =>
        f.options?.kind === "resource" && f.options.paramFromField
          ? [f.options.paramFromField]
          : [],
    );
    for (const { field, param } of deps) {
      const prev = depRef.current[field];
      const next = values[field];
      depRef.current[field] = next;
      // skip mount pertama
      if (prev !== undefined && prev !== next) {
        const current = values[param];
        if (current !== "" && current !== undefined) setValue(param, "");
      }
    }
  }, [values, visibleFields, setValue]);

  const onSubmit = handleSubmit(async (raw) => {
    setServerError(null);
    setBusy(true);
    try {
      await submit(buildPayload(raw, visibleFields));
      reset(defaultValues);
      onHide();
    } catch (error) {
      setServerError(getApiErrorMessage(error));
    } finally {
      setBusy(false);
    }
  });

  const handleClose = () => {
    setServerError(null);
    onHide();
  };

  const renderField = (field: CrudFieldConfig) => {
    if (field.heading !== undefined) {
      return (
        <Col xs={12} key={field.name} className="mb-1">
          <div className="fs-13 fw-semibold text-muted text-uppercase border-bottom pb-1 mb-2">
            {field.heading}
          </div>
        </Col>
      );
    }
    return (
      <Col md={field.colMd ?? 12} key={field.name} className="mb-2">
        {field.type !== "checkbox" && (
          <Form.Label className="mb-1 fs-13 fw-medium">
            {field.label}
            {field.required && <span className="text-danger"> *</span>}
          </Form.Label>
        )}
        <CrudFieldInput
          field={field}
          control={control}
          isInvalid={Boolean(serverError)}
        />
        {field.helperText && (
          <div className="fs-12 text-muted mt-1">{field.helperText}</div>
        )}
      </Col>
    );
  };

  return (
    <Modal
      show={show}
      onHide={handleClose}
      centered
      backdrop="static"
      size={formSize}
    >
      <Modal.Header closeButton>
        <Modal.Title className="fs-16 fw-semibold">
          {editing ? `Ubah ${apiModuleLabel}` : `Tambah ${apiModuleLabel}`}
        </Modal.Title>
      </Modal.Header>
      <Form noValidate onSubmit={onSubmit}>
        <Modal.Body>
          {serverError && (
            <div className="alert alert-danger py-2 fs-13" role="alert">
              <IconifyIcon icon="ri:error-warning-line" className="me-1" />
              {serverError}
            </div>
          )}
          <Row className="g-1">{visibleFields.map(renderField)}</Row>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={handleClose} disabled={busy}>
            Batal
          </Button>
          <Button variant="primary" type="submit" disabled={busy}>
            {busy ? "Menyimpan..." : editing ? "Simpan Perubahan" : "Simpan"}
          </Button>
        </Modal.Footer>
      </Form>
    </Modal>
  );
};

export default FormModal;
