"use client";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { AcademicStatus } from "@/types/master";
import { Badge } from "react-bootstrap";

/** Master Status Akademik (Phase 3) — kode + color hex utk badge KRS/KHS. */

const statusAkademikConfig: CrudPageConfig<AcademicStatus> = {
  endpoint: "/academic-statuses",
  permission: "academic_statuses.read",
  title: "Status Akademik",
  subtitle: "Kelola status mahasiswa (aktif, cuti, DO, dsb.) beserta warna badge-nya.",
  apiModuleLabel: "Status Akademik",
  columns: [
    {
      key: "code",
      label: "Kode",
      className: "fw-medium",
    },
    { key: "name", label: "Nama Status" },
    {
      key: "color",
      label: "Warna",
      render: (row) => (
        <Badge
          className="border-0"
          style={
            row.color
              ? { backgroundColor: row.color, color: "#fff" }
              : undefined
          }
        >
          {row.color ?? "—"}
        </Badge>
      ),
    },
  ],
  formFields: [
    {
      name: "code",
      label: "Kode (mis. A, C, DO)",
      type: "text",
      required: true,
      colMd: 6,
    },
    { name: "name", label: "Nama Status", type: "text", required: true, colMd: 6 },
    {
      name: "color",
      label: "Warna Badge (opsional)",
      type: "color",
      colMd: 6,
      helperText: "Dipakai sebagai warna badge status di modul lain.",
    },
  ],
};

const StatusAkademikPage = () => (
  <ResourceCrudPage config={statusAkademikConfig} />
);

export default StatusAkademikPage;
