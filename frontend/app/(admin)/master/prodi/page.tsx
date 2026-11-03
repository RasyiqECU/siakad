"use client";
import FkLabel from "@/components/shared/crud/FkLabel";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { DegreeLevel, StudyProgram } from "@/types/master";
import { Badge } from "react-bootstrap";

/** Master Program Studi (Phase 3) — cascade fakultas → jurusan. */

const DEGREE_OPTIONS = ["D3", "D4", "S1", "S2", "S3"].map((value) => ({
  value,
  label: `${value} (${value === "D3" ? "Diploma 3" : value === "D4" ? "Diploma 4" : value === "S1" ? "Sarjana" : value === "S2" ? "Magister" : "Doktor"})`,
}));

const prodiConfig: CrudPageConfig<StudyProgram> = {
  endpoint: "/study-programs",
  permission: "study_programs.read",
  title: "Program Studi",
  subtitle: "Kelola daftar program studi dalam satu jurusan fakultas.",
  apiModuleLabel: "Program Studi",
  filterFields: [
    { key: "faculty_id", label: "Semua Fakultas", colMd: 6,
      options: { kind: "resource", endpoint: "/faculties", labelKey: "name" },
    },
    { key: "department_id", label: "Semua Jurusan", colMd: 6,
      options: {
        kind: "resource",
        endpoint: "/departments",
        paramFromField: { field: "faculty_id", param: "faculty_id" },
        labelParts: ["code", "name"],
      },
    },
  ],
  columns: [
    { key: "code", label: "Kode", className: "fw-medium" },
    { key: "name", label: "Nama Program Studi" },
    {
      key: "department_id",
      label: "Jurusan",
      render: (row) => (
        <FkLabel
          endpoint="/departments"
          id={row.department_id}
          labelParts={["code", "name"]}
        />
      ),
    },
    {
      key: "degree",
      label: "Jenjang",
      render: (row) => (
        <Badge className="bg-secondary-subtle text-secondary">
          {row.degree as DegreeLevel}
        </Badge>
      ),
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
      name: "faculty_id",
      label: "Fakultas",
      type: "select",
      required: true,
      colMd: 12,
      options: { kind: "resource", endpoint: "/faculties", labelKey: "name" },
    },
    {
      name: "department_id",
      label: "Jurusan",
      type: "select",
      required: true,
      colMd: 12,
      options: {
        kind: "resource",
        endpoint: "/departments",
        paramFromField: { field: "faculty_id", param: "faculty_id" },
        labelParts: ["code", "name"],
      },
    },
    {
      name: "code",
      label: "Kode Program Studi",
      type: "text",
      required: true,
      colMd: 6,
      placeholder: "mis. TI-S1",
    },
    {
      name: "name",
      label: "Nama Program Studi",
      type: "text",
      required: true,
      colMd: 6,
    },
    {
      name: "degree",
      label: "Jenjang",
      type: "select",
      required: true,
      colMd: 6,
      options: { kind: "static", options: DEGREE_OPTIONS },
    },
    {
      name: "is_active",
      label: "Program studi aktif",
      type: "checkbox",
      colMd: 6,
      defaultValue: true,
    },
  ],
};

const ProdiPage = () => <ResourceCrudPage config={prodiConfig} />;

export default ProdiPage;
