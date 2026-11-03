"use client";
import FkLabel from "@/components/shared/crud/FkLabel";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { Course } from "@/types/master";
import { Badge } from "react-bootstrap";

/** Master Mata Kuliah (Phase 3) — SKS 2–6, semester 1–14, wajib/pilihan. */

const COURSE_TYPE_BADGE: Record<string, string> = {
  wajib: "bg-primary-subtle text-primary",
  pilihan: "bg-warning-subtle text-warning",
};

const mataKuliahConfig: CrudPageConfig<Course> = {
  endpoint: "/courses",
  permission: "courses.read",
  title: "Mata Kuliah",
  subtitle: "Kelola daftar mata kuliah per program studi beserta bobot SKS.",
  apiModuleLabel: "Mata Kuliah",
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
    { key: "name", label: "Nama Mata Kuliah" },
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
      key: "sks",
      label: "SKS",
      className: "text-center",
      render: (row) => (
        <Badge className="bg-secondary-subtle text-secondary">{row.sks}</Badge>
      ),
    },
    {
      key: "semester",
      label: "Semester",
      className: "text-center",
      render: (row) => row.semester ?? "—",
    },
    {
      key: "course_type",
      label: "Jenis",
      render: (row) => (
        <Badge
          className={COURSE_TYPE_BADGE[row.course_type] ?? "bg-secondary-subtle text-secondary"}
        >
          {row.course_type === "wajib"
            ? "Wajib"
            : row.course_type === "pilihan"
              ? "Pilihan"
              : String(row.course_type)}
        </Badge>
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
      name: "code",
      label: "Kode MK",
      type: "text",
      required: true,
      colMd: 6,
      placeholder: "mis. TI-201",
    },
    {
      name: "name",
      label: "Nama Mata Kuliah",
      type: "text",
      required: true,
      colMd: 6,
    },
    {
      name: "sks",
      label: "Bobot SKS",
      type: "number",
      required: true,
      colMd: 6,
      min: 2,
      max: 6,
    },
    {
      name: "semester",
      label: "Semester",
      type: "number",
      required: true,
      colMd: 6,
      min: 1,
      max: 14,
      placeholder: "1–14",
    },
    {
      name: "course_type",
      label: "Jenis MK",
      type: "select",
      required: true,
      colMd: 6,
      options: {
        kind: "static",
        options: [
          { value: "wajib", label: "Wajib" },
          { value: "pilihan", label: "Pilihan" },
        ],
      },
    },
    {
      name: "description",
      label: "Deskripsi (opsional)",
      type: "textarea",
      colMd: 12,
    },
  ],
};

const MataKuliahPage = () => <ResourceCrudPage config={mataKuliahConfig} />;

export default MataKuliahPage;
