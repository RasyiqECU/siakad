"use client";
import PasswordFormInput from "@/components/form/PasswordFormInput";
import TextFormInput from "@/components/form/TextFormInput";
import { getApiErrorMessage } from "@/lib/apiError";
import { Button, Form } from "react-bootstrap";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useState } from "react";import { Modal } from "react-bootstrap";
import type { Role, User, UserPayload } from "@/types/user";

/**
 * Modal tambah/ubah pengguna (satu modal, dua mode).
 * create → password wajib (divalidasi manual, lihat onSubmit); edit → password dihilangkan.
 */
const userFormSchema = z.object({
  name: z.string().min(1, "Nama wajib diisi"),
  email: z
    .string()
    .min(1, "Email wajib diisi")
    .email("Format email tidak valid"),
  password: z.string().optional(),
  phone: z.string().optional(),
  is_active: z.boolean(),
  roles: z.array(z.string()).min(1, "Pilih minimal satu role"),
});

type FormValues = z.infer<typeof userFormSchema>;

const UserFormModal = ({
  show,
  onHide,
  editing,
  roles,
  submit,
}: {
  show: boolean;
  onHide: () => void;
  /** null = mode tambah. */
  editing: User | null;
  roles: Role[];
  /** Service yang dipanggil: createUser / updateUser (throw bila gagal). */
  submit: (payload: UserPayload & { password?: string }) => Promise<void>;
}) => {
  const [serverError, setServerError] = useState<string | null>(null);

  const {
    control,
    handleSubmit,
    reset,
    setError,
  } = useForm<FormValues>({
    resolver: zodResolver(userFormSchema),
    defaultValues: editing
      ? {
          name: editing.name,
          email: editing.email,
          phone: editing.phone ?? "",
          is_active: editing.is_active,
          roles: editing.roles.map((r) => r.name),
        }
      : {
          name: "",
          email: "",
          password: "",
          phone: "",
          is_active: true,
          roles: [],
        },
  });

  const onSubmit = handleSubmit(async (values) => {
    if (!editing && (!values.password || values.password.length < 8)) {
      setError("password", {
        type: "manual",
        message: "Password minimal 8 karakter",
      });
      return;
    }
    setServerError(null);
    try {
      await submit({
        name: values.name,
        email: values.email,
        password: editing ? undefined : values.password,
        phone: values.phone || undefined,
        is_active: values.is_active,
        roles: values.roles,
      });
      reset();
      onHide();
    } catch (error) {
      setServerError(getApiErrorMessage(error));
    }
  });

  const handleClose = () => {
    setServerError(null);
    onHide();
  };

  return (
    <Modal show={show} onHide={handleClose} centered backdrop="static">
      <Modal.Header closeButton>
        <Modal.Title className="fs-16 fw-semibold">
          {editing ? "Ubah Pengguna" : "Tambah Pengguna"}
        </Modal.Title>
      </Modal.Header>
      <Modal.Body>
        {serverError && (
          <div className="alert alert-danger py-2 fs-13" role="alert">
            {serverError}
          </div>
        )}
        <form onSubmit={onSubmit}>
          <div className="mb-3">
            <TextFormInput
              control={control}
              name="name"
              label="Nama Lengkap"
              placeholder="Masukkan nama lengkap"
            />
          </div>
          <div className="mb-3">
            <TextFormInput
              control={control}
              name="email"
              label="Email"
              type="email"
              placeholder="nama@kampus.ac.id"
            />
          </div>
          {!editing && (
            <div className="mb-3">
              <PasswordFormInput
                control={control}
                name="password"
                label="Password"
                placeholder="Minimal 8 karakter"
              />
            </div>
          )}
          <div className="mb-3">
            <TextFormInput
              control={control}
              name="phone"
              label="No. HP (opsional)"
              placeholder="08xxxxxxxxxx"
            />
          </div>

          <Form.Group className="mb-3">
            <Controller
              control={control}
              name="is_active"
              render={({ field }) => (
                <Form.Check
                  type="checkbox"
                  id="user-is-active"
                  label="Akun aktif (dapat login)"
                  checked={field.value}
                  onChange={(e) => field.onChange(e.target.checked)}
                />
              )}
            />
          </Form.Group>

          <Form.Group className="mb-3">
            <Form.Label>Role</Form.Label>
            <Controller
              control={control}
              name="roles"
              render={({ field, fieldState }) => (
                <>
                  <div className="d-flex flex-column gap-1">
                    {roles.map((role) => (
                      <Form.Check
                        key={role.id}
                        type="checkbox"
                        id={`role-${role.id}`}
                        name={field.name}
                        checked={field.value.includes(role.name)}
                        onChange={(e) =>
                          field.onChange(
                            e.target.checked
                              ? [...field.value, role.name]
                              : field.value.filter((r) => r !== role.name),
                          )
                        }
                        label={role.display_name}
                      />
                    ))}
                  </div>
                  {fieldState.error?.message && (
                    <div className="text-danger fs-13 mt-1">
                      {fieldState.error.message}
                    </div>
                  )}
                </>
              )}
            />
          </Form.Group>

          <div className="d-flex justify-content-end gap-2">
            <Button variant="secondary" onClick={handleClose}>
              Batal
            </Button>
            <Button variant="primary" type="submit">
              {editing ? "Simpan Perubahan" : "Simpan"}
            </Button>
          </div>
        </form>
      </Modal.Body>
    </Modal>
  );
};

export default UserFormModal;
