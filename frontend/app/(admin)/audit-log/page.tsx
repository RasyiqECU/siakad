"use client";
import PageHeader from "@/components/shared/PageHeader";
import DataTable, { type DataTableColumn } from "@/components/shared/DataTable";
import PaginationBar from "@/components/shared/PaginationBar";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { useAuth } from "@/hooks/useAuth";
import { useAuditLogs, type AuditLogFilters } from "@/hooks/useAuditLogs";
import { useUserOptions } from "@/hooks/useUsers";
import { formatDateTime } from "@/lib/format";
import type { AuditLogItem } from "@/types/user";
import { useState } from "react";
import { Badge, Card, Col, Form, Row } from "react-bootstrap";

/**
 * Audit Log (Phase 2) — riwayat aksi pengguna (created/updated/deleted).
 * Permission gate: users.read (super_admin selalu diizinkan).
 */

const ACTION_BADGE: Record<string, { text: string; className: string }> = {
  created: { text: "Created", className: "bg-success-subtle text-success" },
  updated: { text: "Updated", className: "bg-warning-subtle text-warning" },
  deleted: { text: "Deleted", className: "bg-danger-subtle text-danger" },
};

/** Pratinjau JSON perubahan, dipangkas agar muat di sel tabel. */
function previewJson(value: Record<string, unknown> | null): string {
  if (!value || Object.keys(value).length === 0) return "—";
  const text = JSON.stringify(value);
  return text.length > 96 ? `${text.slice(0, 96)}…` : text;
}

const AuditLogPage = () => {
  const { user } = useAuth();
  const [filters, setFilters] = useState<AuditLogFilters>({
    userId: "",
    action: "",
    page: 1,
  });

  const allowed =
    Boolean(user) &&
    ((user!.permissions ?? []).includes("users.read") ||
      (user!.roles ?? []).includes("super_admin"));

  const logsQuery = useAuditLogs(filters, allowed);
  const usersOptionsQuery = useUserOptions(100, allowed);

  const setFilter = (patch: Partial<AuditLogFilters>) =>
    setFilters((prev) => ({ ...prev, page: 1, ...patch }));

  const columns: DataTableColumn<AuditLogItem>[] = [
    {
      key: "created_at",
      label: "Waktu",
      render: (row) => (
        <span className="text-nowrap">{formatDateTime(row.created_at)}</span>
      ),
    },
    {
      key: "user",
      label: "User",
      render: (row) =>
        row.user ? (
          <div className="d-flex align-items-center gap-2">
            <span
              className="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-25 fw-semibold"
              style={{ width: 30, height: 30, fontSize: 13 }}
            >
              {row.user.name.charAt(0).toUpperCase()}
            </span>
            <div className="lh-sm">
              <div className="fw-medium">{row.user.name}</div>
              <div className="fs-12 text-muted">{row.user.email}</div>
            </div>
          </div>
        ) : (
          <span className="text-muted">Sistem</span>
        ),
    },
    {
      key: "action",
      label: "Aksi",
      render: (row) => {
        const badge = ACTION_BADGE[row.action] ?? {
          text: row.action,
          className: "bg-secondary-subtle text-secondary",
        };
        return <Badge className={badge.className}>{badge.text}</Badge>;
      },
    },
    {
      key: "subject",
      label: "Subjek",
      render: (row) =>
        row.subject_type ? (
          <span className="fs-13">
            {row.subject_type}
            {row.subject_id != null && (
              <span className="text-muted"> #{row.subject_id}</span>
            )}
          </span>
        ) : (
          "—"
        ),
    },
    {
      key: "properties",
      label: "Data Perubahan",
      render: (row) => (
        <code className="fs-12 text-wrap" style={{ wordBreak: "break-word" }}>
          {previewJson(row.properties)}
        </code>
      ),
    },
    {
      key: "ip_address",
      label: "IP",
      render: (row) => row.ip_address ?? "—",
    },
  ];

  if (!allowed) {
    return (
      <Row className="justify-content-center pt-5">
        <Col md={6}>
          <Card className="p-5 text-center">
            <div className="avatar-xl mx-auto bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center mb-3">
              <IconifyIcon icon="ri:shield-user-line" className="fs-28 text-danger" />
            </div>
            <h4 className="fw-semibold mb-1">Akses ditolak</h4>
            <p className="text-muted mb-0 fs-14">
              Anda tidak memiliki izin <code>users.read</code> untuk membuka
              halaman ini.
            </p>
          </Card>
        </Col>
      </Row>
    );
  }

  const logs = logsQuery.data?.data ?? null;
  const meta = logsQuery.data?.meta;

  return (
    <>
      <PageHeader
        title="Audit Log"
        subtitle="Jejak aktivitas pengguna: pembuatan, perubahan, dan penghapusan data."
      />

      <Card className="mb-3">
        <Card.Body>
          <Row className="g-2">
            <Col md={7}>
              <Form.Select
                value={filters.userId}
                onChange={(e) =>
                  setFilter({
                    userId: e.target.value === "" ? "" : Number(e.target.value),
                  })
                }
              >
                <option value="">Semua User</option>
                {(usersOptionsQuery.data?.data ?? []).map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name} ({u.email})
                  </option>
                ))}
              </Form.Select>
            </Col>
            <Col md={5}>
              <Form.Select
                value={filters.action}
                onChange={(e) => setFilter({ action: e.target.value })}
              >
                <option value="">Semua Aksi</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
              </Form.Select>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      <Card>
        <Card.Body>
          <DataTable<AuditLogItem>
            columns={columns}
            rows={logs}
            isLoading={logsQuery.isLoading}
            emptyMessage={
              logsQuery.isError
                ? "Gagal memuat audit log. Coba muat ulang halaman."
                : "Belum ada aktivitas yang tercatat."
            }
          />
          {meta && (
            <PaginationBar
              page={meta.current_page}
              lastPage={meta.last_page}
              total={meta.total}
              perPage={meta.per_page}
              onPageChange={(page) =>
                setFilters((prev) => ({ ...prev, page }))
              }
            />
          )}
        </Card.Body>
      </Card>
    </>
  );
};

export default AuditLogPage;
