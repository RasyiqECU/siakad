"use client";
/**
 * Halaman CRUD resource Data Master yang config-driven:
 * PageHeader (+ tombol Tambah bila ada izin `.write`), filter bar (search
 * debounced + select dinamis), DataTable, PaginationBar, FormModal, ConfirmDialog.
 * Aksi per-baris: dropdown Ubah/Hapus + `extraActions` tambahan per resource
 * (mis. "Aktifkan" semester, "Kelola MK" kurikulum).
 */
import { useEffect, useState, type ReactNode } from "react";
import { Button, Card, Col, Dropdown, Form, InputGroup, Row } from "react-bootstrap";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import PageHeader from "@/components/shared/PageHeader";
import DataTable from "@/components/shared/DataTable";
import PaginationBar from "@/components/shared/PaginationBar";
import ConfirmDialog from "@/components/shared/ConfirmDialog";
import { useAuth } from "@/hooks/useAuth";
import FkLabel from "./FkLabel";
import FormModal from "./FormModal";
import { useResourceCrud, type CrudRowActionContext } from "./useResourceCrud";
import { labelOfItem, useResourceOptions } from "./options";
import type {
  CrudFilterConfig,
  CrudFilterState,
  CrudPageConfig,
} from "./types";

/** Kartu akses ditolak mirip halaman Pengguna/Audit Log. */
function AccessDeniedCard({ permission }: { permission: string }) {
  return (
    <Row className="justify-content-center pt-5">
      <Col md={6}>
        <Card className="p-5 text-center">
          <div className="avatar-xl mx-auto bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center mb-3">
            <IconifyIcon
              icon="ri:shield-user-line"
              className="fs-28 text-danger"
            />
          </div>
          <h4 className="fw-semibold mb-1">Akses ditolak</h4>
          <p className="text-muted mb-0 fs-14">
            Anda tidak memiliki izin <code>{permission}</code> untuk membuka
            halaman ini.
          </p>
        </Card>
      </Col>
    </Row>
  );
}

/** Select di filter bar: statis / resource (dengan cascade antar filter). */
const FilterSelect = ({
  config,
  value,
  onChange,
  filterValues,
}: {
  config: CrudFilterConfig;
  value: string | number;
  onChange: (value: string) => void;
  filterValues: CrudFilterState;
}) => {
  const optionsInput = config.options;
  const resourceSource =
    optionsInput?.kind === "resource" ? optionsInput : null;
  const depField = resourceSource?.paramFromField?.field;
  const depValue = depField ? filterValues[depField] : undefined;
  const depPresent =
    depValue !== "" && depValue !== undefined && depValue !== null;

  const params = resourceSource
    ? {
        per_page: 100,
        ...(resourceSource.params ?? {}),
        ...(resourceSource.paramFromField && depPresent
          ? { [resourceSource.paramFromField.param]: String(depValue) }
          : {}),
      }
    : undefined;
  const { rows } = useResourceOptions(
    resourceSource?.endpoint ?? "",
    params,
    Boolean(resourceSource) && (!depField || depPresent),
  );

  return (
    <Form.Select
      value={String(value ?? "")}
      onChange={(e) => onChange(e.target.value)}
      aria-label={config.label}
    >
      <option value="">{config.label}</option>
      {optionsInput?.kind === "static" &&
        optionsInput.options.map((opt) => (
          <option key={String(opt.value)} value={String(opt.value)}>
            {opt.label}
          </option>
        ))}
      {resourceSource &&
        rows.map((row) => (
          <option key={String(row.id)} value={String(row.id)}>
            {labelOfItem(row, resourceSource)}
          </option>
        ))}
    </Form.Select>
  );
};

const ResourceCrudPage = <TRow extends { id: number | string }>({
  config,
  extraActions,
}: {
  config: CrudPageConfig<TRow>;
  /** menu item aksi tambahan per-baris dipakai sebelum Hapus. */
  extraActions?: (
    context: CrudRowActionContext<TRow>,
  ) => ReactNode | ReactNode[];
}) => {
  // ---- gating permission (super_admin bypass) — pola halaman Pengguna ----
  const { user } = useAuth();
  const writePermission = config.permission.replace(/\.[^.]+$/, ".write");
  const allowed =
    Boolean(user) &&
    ((user!.permissions ?? []).includes(config.permission) ||
      (user!.roles ?? []).includes("super_admin"));
  const canWrite =
    Boolean(user) &&
    ((user!.permissions ?? []).includes(writePermission) ||
      (user!.roles ?? []).includes("super_admin"));

  // ---- filter state (search + page + dropdown filter configs) ----
  const [searchInput, setSearchInput] = useState("");
  const [filters, setFilters] = useState<CrudFilterState>(() => ({
    search: "",
    page: 1,
    ...(Object.fromEntries(
      (config.filterFields ?? []).map((filter) => [filter.key, ""]),
    ) as Record<string, string | number>),
  }));

  useEffect(() => {
    const timer = setTimeout(() => {
      setFilters((prev) => ({ ...prev, search: searchInput, page: 1 }));
    }, 400);
    return () => clearTimeout(timer);
  }, [searchInput]);

  const setFilter = (patch: Record<string, string | number>) =>
    setFilters((prev) => ({ ...prev, page: 1, ...patch }));

  const crud = useResourceCrud<TRow>(config, filters, allowed);

  if (!allowed) {
    return <AccessDeniedCard permission={config.permission} />;
  }

  const filterFields = config.filterFields ?? [];
  const searchEnabled = true;

  return (
    <>
      <PageHeader title={config.title} subtitle={config.subtitle}>
        {canWrite && (
          <Button variant="primary" onClick={crud.openCreate}>
            <IconifyIcon icon="ri:add-line" className="me-1" /> Tambah{" "}
            {config.apiModuleLabel}
          </Button>
        )}
      </PageHeader>

      {(searchEnabled || filterFields.length > 0) && (
        <Card className="mb-3">
          <Card.Body>
            <Row className="g-2">
              <Col md={searchEnabled ? 4 : 3}>
                <InputGroup>
                  <InputGroup.Text>
                    <IconifyIcon icon="ri:search-line" />
                  </InputGroup.Text>
                  <Form.Control
                    placeholder={`Cari ${config.apiModuleLabel.toLowerCase()}...`}
                    value={searchInput}
                    onChange={(e) => setSearchInput(e.target.value)}
                  />
                </InputGroup>
              </Col>
              {filterFields.map((filter) => (
                <Col key={filter.key} md={filter.colMd ?? 4}>
                  <FilterSelect
                    config={filter}
                    value={filters[filter.key] ?? ""}
                    onChange={(value) => setFilter({ [filter.key]: value })}
                    filterValues={filters}
                  />
                </Col>
              ))}
            </Row>
          </Card.Body>
        </Card>
      )}

      <Card>
        <Card.Body>
          <DataTable<TRow>
            columns={config.columns}
            rows={crud.rows}
            isLoading={crud.isLoading}
            emptyMessage={
              crud.isError
                ? `Gagal memuat data ${config.apiModuleLabel.toLowerCase()}. Coba muat ulang halaman.`
                : `Belum ada ${config.apiModuleLabel.toLowerCase()} yang cocok dengan filter.`
            }
            actions={
              canWrite
                ? (row) => (
                    <Dropdown align="end">
                      <Dropdown.Toggle
                        as="a"
                        className="drop-arrow-none cursor-pointer text-muted"
                        style={{ cursor: "pointer" }}
                        role="button"
                        disabled={crud.busyId === row.id}
                      >
                        <IconifyIcon icon="ri:more-2-fill" />
                      </Dropdown.Toggle>
                      <Dropdown.Menu>
                        <Dropdown.Item onClick={() => crud.openEdit(row)}>
                          <IconifyIcon icon="ri:pencil-line" className="me-2" />
                          Ubah
                        </Dropdown.Item>
                        {extraActions &&
                          (() => {
                            const nodes = extraActions({
                              row,
                              busyId: crud.busyId,
                              setBusyId: crud.setBusyId,
                              confirmAction: crud.confirmAction,
                              refresh: crud.refresh,
                            });
                            return Array.isArray(nodes) ? nodes : [nodes];
                          })()}
                        <Dropdown.Divider />
                        <Dropdown.Item
                          className="text-danger"
                          onClick={() => crud.askDelete(row)}
                        >
                          <IconifyIcon
                            icon="ri:delete-bin-line"
                            className="me-2"
                          />
                          Hapus
                        </Dropdown.Item>
                      </Dropdown.Menu>
                    </Dropdown>
                  )
                : undefined
            }
          />
          {crud.meta && (
            <PaginationBar
              page={crud.meta.current_page}
              lastPage={crud.meta.last_page}
              total={crud.meta.total}
              perPage={crud.meta.per_page}
              onPageChange={(page) =>
                setFilters((prev) => ({ ...prev, page }))
              }
            />
          )}
        </Card.Body>
      </Card>

      {(crud.formOpen || crud.editing) && (
        <FormModal<TRow>
          key={crud.editing?.id ?? "create"}
          show={crud.formOpen}
          onHide={crud.closeForm}
          editing={(crud.editing ?? null) as TRow | null}
          fields={config.formFields}
          apiModuleLabel={config.apiModuleLabel}
          formSize={config.formSize}
          submit={crud.submit}
        />
      )}

      <ConfirmDialog
        open={crud.confirmState.open}
        title={crud.confirmState.title}
        message={crud.confirmState.message}
        busy={crud.confirmState.busy}
        variant={crud.confirmState.variant}
        confirmLabel={crud.confirmState.confirmLabel}
        onConfirm={crud.confirmState.onConfirm}
        onCancel={crud.closeConfirm}
      />
    </>
  );
};

export default ResourceCrudPage;
