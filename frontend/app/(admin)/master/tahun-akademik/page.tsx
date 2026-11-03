"use client";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { AcademicYear } from "@/types/master";
import { Badge } from "react-bootstrap";

/** Master Tahun Akademik (Phase 3) — hanya satu yang aktif (server-side). */

const tahunAkademikConfig: CrudPageConfig<AcademicYear> = {
  endpoint: "/academic-years",
  permission: "academic_years.read",
  title: "Tahun Akademik",
  subtitle: "Kelola tahun akademik — hanya satu yang berstatus aktif.",
  apiModuleLabel: "Tahun Akademik",
  columns: [
    {
      key: "year",
      label: "Tahun Akademik",
      className: "fw-medium",
      render: (row) => row.year,
    },
    {
      key: "is_active",
      label: "Status",
      render: (row) =>
        row.is_active ? (
          <Badge className="bg-success-subtle text-success">Aktif</Badge>
        ) : (
          <Badge className="bg-secondary-subtle text-secondary">Nonaktif</Badge>
        ),
    },
  ],
  formFields: [
    {
      name: "year",
      label: "Tahun",
      type: "text",
      required: true,
      colMd: 6,
      placeholder: "mis. 2026",
      helperText: "Format tahun akademik, mis. 2026 atau 2026/2027 sesuai konvensi kampus.",
    },
    {
      name: "is_active",
      label: "Jadikan tahun akademik aktif",
      type: "checkbox",
      colMd: 6,
      defaultValue: true,
      helperText: "Sistem hanya mengizinkan satu tahun akademik aktif (diatur oleh server).",
    },
  ],
};

const TahunAkademikPage = () => (
  <ResourceCrudPage config={tahunAkademikConfig} />
);

export default TahunAkademikPage;
