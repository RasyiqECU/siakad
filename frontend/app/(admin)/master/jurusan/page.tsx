"use client";
import FkLabel from "@/components/shared/crud/FkLabel";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { Department } from "@/types/master";

/** Master Jurusan (Phase 3) — filter per fakultas + FK label. */

const jurusanConfig: CrudPageConfig<Department> = {
  endpoint: "/departments",
  permission: "departments.read",
  title: "Jurusan",
  subtitle: "Kelola daftar jurusan beserta fakultas induknya.",
  apiModuleLabel: "Jurusan",
  filterFields: [
    { key: "faculty_id", label: "Semua Fakultas", colMd: 4,
      options: {
        kind: "resource",
        endpoint: "/faculties",
        labelKey: "name",
      },
    },
  ],
  columns: [
    { key: "code", label: "Kode", className: "fw-medium" },
    { key: "name", label: "Nama Jurusan" },
    {
      key: "faculty_id",
      label: "Fakultas",
      render: (row) => (
        <FkLabel endpoint="/faculties" id={row.faculty_id} labelKey="name" />
      ),
    },
  ],
  formFields: [
    {
      name: "faculty_id",
      label: "Fakultas",
      type: "select",
      required: true,
      colMd: 12,
      options: { kind: "resource", endpoint: "/faculties", labelKey: "name" },
    },
    {
      name: "code",
      label: "Kode Jurusan",
      type: "text",
      required: true,
      colMd: 6,
      placeholder: "mis. TI",
    },
    { name: "name", label: "Nama Jurusan", type: "text", required: true, colMd: 6 },
  ],
};

const JurusanPage = () => <ResourceCrudPage config={jurusanConfig} />;

export default JurusanPage;
