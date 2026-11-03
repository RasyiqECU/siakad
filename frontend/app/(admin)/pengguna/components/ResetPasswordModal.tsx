"use client";
import PasswordFormInput from "@/components/form/PasswordFormInput";
import { getApiErrorMessage } from "@/lib/apiError";
import { Button, Modal } from "react-bootstrap";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useState } from "react";

const resetSchema = z
  .object({
    password: z.string().min(8, "Password minimal 8 karakter"),
    password_confirmation: z.string().min(1, "Konfirmasi password wajib diisi"),
  })
  .refine((v) => v.password === v.password_confirmation, {
    message: "Konfirmasi password tidak sama",
    path: ["password_confirmation"],
  });

type ResetFields = z.infer<typeof resetSchema>;

/** Modal reset password pengguna (password baru + konfirmasi). */
const ResetPasswordModal = ({
  show,
  onHide,
  userName,
  submit,
}: {
  show: boolean;
  onHide: () => void;
  userName: string;
  /** Service PATCH /users/{id}/reset-password (throw bila gagal). */
  submit: (password: string) => Promise<void>;
}) => {
  const [serverError, setServerError] = useState<string | null>(null);
  const {
    control,
    handleSubmit,
    reset,
  } = useForm<ResetFields>({
    resolver: zodResolver(resetSchema),
    defaultValues: { password: "", password_confirmation: "" },
  });

  const onSubmit = handleSubmit(async (values) => {
    setServerError(null);
    try {
      await submit(values.password);
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
        <Modal.Title className="fs-16 fw-semibold">Reset Password</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <p className="text-muted fs-13">
          Atur password baru untuk <strong>{userName}</strong>.
        </p>
        {serverError && (
          <div className="alert alert-danger py-2 fs-13" role="alert">
            {serverError}
          </div>
        )}
        <form onSubmit={onSubmit}>
          <div className="mb-3">
            <PasswordFormInput
              control={control}
              name="password"
              label="Password Baru"
              placeholder="Minimal 8 karakter"
            />
          </div>
          <div className="mb-3">
            <PasswordFormInput
              control={control}
              name="password_confirmation"
              label="Konfirmasi Password"
              placeholder="Ulangi password baru"
            />
          </div>
          <div className="d-flex justify-content-end gap-2">
            <Button variant="secondary" onClick={handleClose}>
              Batal
            </Button>
            <Button variant="primary" type="submit">
              Simpan
            </Button>
          </div>
        </form>
      </Modal.Body>
    </Modal>
  );
};

export default ResetPasswordModal;
