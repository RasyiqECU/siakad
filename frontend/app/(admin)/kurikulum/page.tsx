"use client";
import { useState } from "react";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { CrudRowActionContext } from "@/components/shared/crud/useResourceCrud";
import FkLabel from "@/components/shared/crud/FkLabel";
import CurriculumManageModal from "./components/CurriculumManageModal";
import type { Curriculum } from "@/types/master";
import { Badge, Dropdown } from "react-bootstrap";
import IconifyIcon from "@/components/wrappers/IconifyIcon";

/** Master Kurikulum (Phase 3) — list + aksi "Kelola MK" (tab MK & prasyarat). */

const kurikulumConfig: CrudPageConfig<Curriculum> = {
  endpoint: "/curriculums",
  permission: "curriculums.read",
  title: "Kurikulum",
  subtitle:
    "Kelola kurikulum per program studi: mata kuliah terpasang & prasyarat.",
  apiModuleLabel: "Kurikulum",
  filterFields: [
    {
      key: "study_program_id",
      label: "Semua Program Studi",
      colMd: 5,
      options: {
        kind: "resource",
        endpoint: "/study-programs",
        labelParts: ["code", "name"],
      },
    },
  ],
  columns: [
    {
      key: "code",
      label: "Kode",
      className: "fw-medium",
    },
    { key: "name", label: "Nama Kurikulum" },
    {
      key: "study_program_id",
      label: "Prodi",
      render: (row) => (
        <FkLabel
          endpoint="/study-programs"
          id={row.study_program_id}
          labelParts={["code", "name"]}
        />
      ),
    },
    {
      key: "academic_year_id",
      label: "Tahun Akademik",
      render: (row) => (
        <FkLabel
          endpoint="/academic-years"
          id={row.academic_year_id}
          labelKey="year"
        />
      ),
    },
    {
      key: "total_sks",
      label: "Total SKS",
      className: "text-center",
      render: (row) => (
        <Badge className="bg-primary-subtle text-primary">{row.total_sks}</Badge>
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
      name: "study_program_id",
      label: "Program Studi",
      type: "select",
      required: true,
      colMd: 12,
      options: {
        kind: "resource",
        endpoint: "/study-programs",
        labelParts: ["code", "name"],
      },
    },
    {
      name: "academic_year_id",
      label: "Tahun Akademik",
      type: "select",
      required: true,
      colMd: 12,
      options: { kind: "resource", endpoint: "/academic-years", labelKey: "year" },
    },
    {
      name: "code",
      label: "Kode Kurikulum",
      type: "text",
      required: true,
      colMd: 6,
      placeholder: "mis. TI-S1-2026",
    },
    { name: "name", label: "Nama Kurikulum", type: "text", required: true, colMd: 6 },
    {
      name: "total_sks",
      label: "Total SKS",
      type: "number",
      required: true,
      colMd: 6,
      min: 0,
      helperText: "Jumlah bobot SKS seluruh mata kuliah pada kurikulum.",
    },
    {
      name: "is_active",
      label: "Kurikulum aktif",
      type: "checkbox",
      colMd: 6,
      defaultValue: true,
    },
    {
      name: "description",
      label: "Deskripsi (opsional)",
      type: "textarea",
      colMd: 12,
    },
  ],
};

const KurikulumPage = () => {
  const [managed, setManaged] = useState<Curriculum | null>(null);

  const kurikulumExtraActions = (ctx: CrudRowActionContext<Curriculum>) => [
    <Dropdown.Item
      key={`manage-${String(ctx.row.id)}`}
      onClick={() => setManaged(ctx.row)}
    >
      <IconifyIcon icon="ri:book-2-line" className="me-2" />
      Kelola MK
    </Dropdown.Item>,
  ];

  return (
    <>
      <ResourceCrudPage
        config={kurikulumConfig}
        extraActions={kurikulumExtraActions}
      />
      <CurriculumManageModal
        show={Boolean(managed)}
        curriculumId={managed ? managed.id : null}
        onHide={() => setManaged(null)}
      />
    </>
  );
};

export default KurikulumPage;
