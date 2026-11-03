"use client";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { Faculty } from "@/types/master";

/** Master Fakultas (Phase 3) — config-driven CRUD. */

const fakultasConfig: CrudPageConfig<Faculty> = {
  endpoint: "/faculties",
  permission: "faculties.read",
  title: "Fakultas",
  subtitle: "Kelola daftar fakultas universitas.",
  apiModuleLabel: "Fakultas",
  columns: [
    { key: "code", label: "Kode", className: "fw-medium", render: (row) => row.code ?? "—" },
    { key: "name", label: "Nama Fakultas" },
    {
      key: "description",
      label: "Deskripsi",
      render: (row) => row.description ?? <span className="text-muted">—</span>,
    },
  ],
  formFields: [
    {
      name: "code",
      label: "Kode Fakultas",
      type: "text",
      required: true,
      colMd: 6,
      placeholder: "mis. FT",
    },
    { name: "name", label: "Nama Fakultas", type: "text", required: true, colMd: 6 },
    {
      name: "description",
      label: "Deskripsi (opsional)",
      type: "textarea",
      placeholder: "Singkat fakultas...",
    },
  ],
};

const FakultasPage = () => <ResourceCrudPage config={fakultasConfig} />;

export default FakultasPage;
