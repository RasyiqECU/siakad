"use client";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { Button, Modal } from "react-bootstrap";

/**
 * Modal konfirmasi generik (hapus/nonaktifkan/aksi berisiko).
 * onConfirm dipanggil async — tombol disabled selama busy.
 */
const ConfirmDialog = ({
  open,
  title,
  message,
  onConfirm,
  onCancel,
  busy = false,
  variant = "danger",
  confirmLabel = "Ya, Lanjutkan",
  cancelLabel = "Batal",
}: {
  open: boolean;
  title: string;
  message: React.ReactNode;
  onConfirm: () => void | Promise<void>;
  onCancel: () => void;
  busy?: boolean;
  variant?: "danger" | "primary" | "warning";
  confirmLabel?: string;
  cancelLabel?: string;
}) => (
  <Modal show={open} onHide={onCancel} centered backdrop="static">
    <Modal.Header closeButton>
      <Modal.Title className="fs-16 fw-semibold">{title}</Modal.Title>
    </Modal.Header>
    <Modal.Body>
      <div className="d-flex gap-3 align-items-start">
        <div
          className={`avatar-sm flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle ${
            variant === "danger"
              ? "bg-danger-subtle"
              : variant === "warning"
                ? "bg-warning-subtle"
                : "bg-primary-subtle"
          }`}
        >
          <IconifyIcon
            icon="ri:alert-line"
            className={`fs-18 ${
              variant === "danger"
                ? "text-danger"
                : variant === "warning"
                  ? "text-warning"
                  : "text-primary"
            }`}
          />
        </div>
        <div className="fs-14">{message}</div>
      </div>
    </Modal.Body>
    <Modal.Footer>
      <Button variant="secondary" onClick={onCancel} disabled={busy}>
        {cancelLabel}
      </Button>
      <Button
        variant={variant === "warning" ? "warning" : variant}
        onClick={() => void onConfirm()}
        disabled={busy}
      >
        {busy ? "Memproses..." : confirmLabel}
      </Button>
    </Modal.Footer>
  </Modal>
);

export default ConfirmDialog;
