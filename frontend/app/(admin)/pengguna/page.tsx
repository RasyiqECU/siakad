"use client";
import PageHeader from "@/components/shared/PageHeader";
import DataTable, { type DataTableColumn } from "@/components/shared/DataTable";
import PaginationBar from "@/components/shared/PaginationBar";
import ConfirmDialog from "@/components/shared/ConfirmDialog";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import UserFormModal from "./components/UserFormModal";
import ResetPasswordModal from "./components/ResetPasswordModal";
import { useAuth } from "@/hooks/useAuth";
import { useRoles } from "@/hooks/useRoles";
import { useUsers, type UserFilters } from "@/hooks/useUsers";
import {
  activateUser,
  createUser,
  deactivateUser,
  destroyUser,
  resetUserPassword,
  updateUser,
} from "@/services/api/users";
import type { UserPayload } from "@/types/user";
import { getApiErrorMessage } from "@/lib/apiError";
import { formatDate } from "@/lib/format";
import type { User } from "@/types/user";
import { useEffect, useState, type ReactNode } from "react";
import { toast } from "react-toastify";
import {
  Badge,
  Button,
  Card,
  Col,
  Dropdown,
  Form,
  InputGroup,
  Row,
} from "react-bootstrap";
import { useQueryClient } from "@tanstack/react-query";

/**
 * Manajemen Pengguna (Phase 2) — CRUD, aktivasi & reset password.
 * Permission gate: users.read (super_admin selalu diizinkan).
 */

const ROLE_LABEL: Record<string, string> = {
  super_admin: "Super Admin",
  admin_akademik: "Admin Akademik",
  dosen: "Dosen",
  mahasiswa: "Mahasiswa",
  keuangan: "Bagian Keuangan",
  pimpinan: "Pimpinan",
};

const PenggunaPage = () => {
  const { user } = useAuth();
  const queryClient = useQueryClient();

  const allowed =
    Boolean(user) &&
    ((user!.permissions ?? []).includes("users.read") ||
      (user!.roles ?? []).includes("super_admin"));

  // ---- filter + debounced search ----
  const [searchInput, setSearchInput] = useState("");
  const [filters, setFilters] = useState<UserFilters>({
    search: "",
    role: "",
    status: "",
    page: 1,
  });

  useEffect(() => {
    const timer = setTimeout(() => {
      setFilters((prev) => ({
        ...prev,
        search: searchInput,
        page: 1,
      }));
    }, 400);
    return () => clearTimeout(timer);
  }, [searchInput]);

  const setFilter = (patch: Partial<UserFilters>) =>
    setFilters((prev) => ({ ...prev, page: 1, ...patch }));

  // ---- data ----
  const usersQuery = useUsers(filters, allowed);
  const rolesQuery = useRoles(allowed);

  // ---- modal & konfirmasi state ----
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<User | null>(null);
  const [resetTarget, setResetTarget] = useState<User | null>(null);
  const [confirm, setConfirm] = useState<{
    open: boolean;
    busy: boolean;
    title: string;
    message: ReactNode;
    variant: "danger" | "primary";
    onConfirm: () => Promise<void>;
  }>({
    open: false,
    busy: false,
    title: "",
    message: null,
    variant: "danger",
    onConfirm: async () => {},
  });
  const [busyId, setBusyId] = useState<number | null>(null);

  const invalidateUsers = () =>
    queryClient.invalidateQueries({ queryKey: ["users"] });

  const openConfirm = (
    title: string,
    message: ReactNode,
    variant: "danger" | "primary",
    run: () => Promise<void>,
  ) =>
    setConfirm({
      open: true,
      busy: false,
      title,
      message,
      variant,
      onConfirm: run,
    });

  /** Penjompo aksi cepat (aktivasi/nonaktif/hapus): hinder double-click + toast. */
  const runQuickAction = async (target: User, action: () => Promise<void>) => {
    setBusyId(target.id);
    try {
      await action();
      toast.success(
        target.is_active
          ? "Pengguna berhasil dinonaktifkan."
          : "Pengguna berhasil diaktifkan.",
      );
    } catch (error) {
      toast.error(getApiErrorMessage(error));
    } finally {
      setBusyId(null);
      void invalidateUsers();
    }
  };

  const handleToggleActive = (target: User) => {
    if (target.is_active) {
      openConfirm(
        "Nonaktifkan Pengguna",
        <>
          Nonaktifkan akun <strong>{target.name}</strong>? Pengguna tidak akan
          dapat <em>login</em> lagi sampai diaktifkan kembali.
        </>,
        "danger",
        async () => {
          setConfirm((c) => ({ ...c, busy: true }));
          try {
            await deactivateUser(target.id);
            toast.success("Pengguna berhasil dinonaktifkan.");
          } catch (error) {
            toast.error(getApiErrorMessage(error));
          } finally {
            setConfirm((c) => ({ ...c, open: false, busy: false }));
            void invalidateUsers();
          }
        },
      );
      return;
    }
    void runQuickAction(target, () => activateUser(target.id));
  };

  const handleDelete = (target: User) => {
    openConfirm(
      "Hapus Pengguna",
      <>
        Hapus permanen akun <strong>{target.name}</strong> ({target.email})?
        Tindakan ini tidak dapat dibatalkan.
      </>,
      "danger",
      async () => {
        setConfirm((c) => ({ ...c, busy: true }));
        try {
          await destroyUser(target.id);
          toast.success("Pengguna berhasil dihapus.");
        } catch (error) {
          toast.error(getApiErrorMessage(error));
        } finally {
          setConfirm((c) => ({ ...c, open: false, busy: false }));
          void invalidateUsers();
        }
      },
    );
  };

  const submitForm = async (payload: UserPayload & { password?: string }) => {
    if (editing) {
      await updateUser(editing.id, payload);
      toast.success("Pengguna berhasil diperbarui.");
    } else {
      await createUser(
        payload as UserPayload & { password: string; name: string; email: string },
      );
      toast.success("Pengguna berhasil ditambahkan.");
    }
    void invalidateUsers();
  };

  const columns: DataTableColumn<User>[] = [
    {
      key: "name",
      label: "Nama",
      render: (row) => (
        <div className="d-flex align-items-center gap-2">
          {row.avatar ? (
            <img
              src={row.avatar}
              alt={row.name}
              className="rounded-circle"
              width={34}
              height={34}
            />
          ) : (
            <span
              className="avatar-xs d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary fw-semibold"
              style={{ width: 34, height: 34 }}
            >
              {row.name.charAt(0).toUpperCase()}
            </span>
          )}
          <span className="fw-medium">{row.name}</span>
        </div>
      ),
    },
    { key: "email", label: "Email" },
    {
      key: "roles",
      label: "Role",
      render: (row) => (
        <div className="d-flex flex-wrap gap-1">
          {row.roles.map((role) => (
            <Badge key={role.id} className="bg-secondary-subtle text-secondary">
              {ROLE_LABEL[role.name] ?? role.display_name}
            </Badge>
          ))}
        </div>
      ),
    },
    {
      key: "is_active",
      label: "Status",
      render: (row) =>
        row.is_active ? (
          <Badge className="bg-success-subtle text-success">Aktif</Badge>
        ) : (
          <Badge className="bg-danger-subtle text-danger">Nonaktif</Badge>
        ),
    },
    {
      key: "created_at",
      label: "Dibuat",
      render: (row) => formatDate(row.created_at),
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

  const users = usersQuery.data?.data ?? null;
  const meta = usersQuery.data?.meta;
  const roles = rolesQuery.data ?? [];

  return (
    <>
      <PageHeader
        title="Manajemen Pengguna"
        subtitle="Kelola akun pengguna, role, dan status aktivasi."
      >
        <Button
          variant="primary"
          onClick={() => {
            setEditing(null);
            setFormOpen(true);
          }}
        >
          <IconifyIcon icon="ri:add-line" className="me-1" /> Tambah Pengguna
        </Button>
      </PageHeader>

      <Card className="mb-3">
        <Card.Body>
          <Row className="g-2">
            <Col md={5}>
              <InputGroup>
                <InputGroup.Text>
                  <IconifyIcon icon="ri:search-line" />
                </InputGroup.Text>
                <Form.Control
                  placeholder="Cari nama / email..."
                  value={searchInput}
                  onChange={(e) => setSearchInput(e.target.value)}
                />
              </InputGroup>
            </Col>
            <Col md={4}>
              <Form.Select
                value={filters.role}
                onChange={(e) => setFilter({ role: e.target.value })}
              >
                <option value="">Semua Role</option>
                {roles.map((role) => (
                  <option key={role.id} value={role.name}>
                    {role.display_name}
                  </option>
                ))}
              </Form.Select>
            </Col>
            <Col md={3}>
              <Form.Select
                value={filters.status}
                onChange={(e) => setFilter({ status: e.target.value })}
              >
                <option value="">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </Form.Select>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      <Card>
        <Card.Body>
          <DataTable<User>
            columns={columns}
            rows={users}
            isLoading={usersQuery.isLoading}
            emptyMessage={
              usersQuery.isError
                ? "Gagal memuat data pengguna. Coba muat ulang halaman."
                : "Belum ada pengguna yang cocok dengan filter."
            }
            actions={(row) => (
              <Dropdown align="end">
                <Dropdown.Toggle
                  as="a"
                  className="drop-arrow-none cursor-pointer text-muted"
                  style={{ cursor: "pointer" }}
                  role="button"
                  disabled={busyId === row.id}
                >
                  <IconifyIcon icon="ri:more-2-fill" />
                </Dropdown.Toggle>
                <Dropdown.Menu>
                  <Dropdown.Item
                    onClick={() => {
                      setEditing(row);
                      setFormOpen(true);
                    }}
                  >
                    <IconifyIcon icon="ri:pencil-line" className="me-2" />
                    Ubah
                  </Dropdown.Item>
                  <Dropdown.Item onClick={() => handleToggleActive(row)}>
                    <IconifyIcon
                      icon={row.is_active ? "ri:user-forbid-line" : "ri:user-follow-line"}
                      className="me-2"
                    />
                    {row.is_active ? "Nonaktifkan" : "Aktifkan"}
                  </Dropdown.Item>
                  <Dropdown.Item onClick={() => setResetTarget(row)}>
                    <IconifyIcon icon="ri:key-2-line" className="me-2" />
                    Reset Password
                  </Dropdown.Item>
                  <Dropdown.Divider />
                  <Dropdown.Item
                    className="text-danger"
                    onClick={() => handleDelete(row)}
                  >
                    <IconifyIcon icon="ri:delete-bin-line" className="me-2" />
                    Hapus
                  </Dropdown.Item>
                </Dropdown.Menu>
              </Dropdown>
            )}
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

      {formOpen && (
        <UserFormModal
          key={editing?.id ?? "create"}
          show={formOpen}
          onHide={() => setFormOpen(false)}
          editing={editing}
          roles={roles}
          submit={submitForm}
        />
      )}

      {resetTarget && (
        <ResetPasswordModal
          show={Boolean(resetTarget)}
          onHide={() => setResetTarget(null)}
          userName={resetTarget.name}
          submit={async (password) => {
            await resetUserPassword(resetTarget.id, password);
            toast.success("Password berhasil direset.");
          }}
        />
      )}

      <ConfirmDialog
        open={confirm.open}
        title={confirm.title}
        message={confirm.message}
        busy={confirm.busy}
        variant={confirm.variant}
        confirmLabel={confirm.variant === "danger" ? "Ya, Lanjutkan" : "Aktifkan"}
        onConfirm={confirm.onConfirm}
        onCancel={() => setConfirm((c) => ({ ...c, open: false }))}
      />
    </>
  );
};

export default PenggunaPage;
