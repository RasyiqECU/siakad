import type { ReactNode } from "react";

/**
 * Kepala halaman admin: judul (h4 fw-semibold) + deskripsi opsional
 * + slot aksi di sisi kanan (children).
 */
const PageHeader = ({
  title,
  subtitle,
  children,
}: {
  title: string;
  subtitle?: string;
  children?: ReactNode;
}) => (
  <div className="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h4 className="fw-semibold mb-1">{title}</h4>
      {subtitle && <p className="text-muted mb-0 fs-14">{subtitle}</p>}
    </div>
    {children && <div className="d-flex gap-2 flex-wrap">{children}</div>}
  </div>
);

export default PageHeader;
