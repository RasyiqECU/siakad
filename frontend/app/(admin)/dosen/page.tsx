"use client";
import FkLabel from "@/components/shared/crud/FkLabel";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { Lecturer } from "@/types/master";
import { Badge } from "react-bootstrap";
import IconifyIcon from "@/components/wrappers/IconifyIcon";

/**
 * Master Dosen (Phase 3) — CRUD + pembuatan akun login otomatis (user_email).
 * Nilai enum mengikuti App\Enums\AcademicPosition & EmploymentStatus backend:
 * Tenaga_Pengajar | Asisten_Ahli | Lektor | Lektor_Kepala | Guru_Besar ; tetap | non_tetap.
 */
const ACADEMIC_POSITIONS = [
  { value: "Tenaga_Pengajar", label: "Tenaga Pengajar" },
  { value: "Asisten_Ahli", label: "Asisten Ahli" },
  { value: "Lektor", label: "Lektor" },
  { value: "Lektor_Kepala", label: "Lektor Kepala" },
  { value: "Guru_Besar", label: "Guru Besar" },
];

const POSITION_LABELS: Record<string, string> = Object.fromEntries(
  ACADEMIC_POSITIONS.map((p) => [p.value, p.label]),
);

const EMPLOYMENT_STATUSES = [
  { value: "tetap", label: "Tetap" },
  { value: "non_tetap", label: "Tidak Tetap" },
];

const EMPLOYMENT_LABELS: Record<string, string> = Object.fromEntries(
  EMPLOYMENT_STATUSES.map((p) => [p.value, p.label]),
);

const EDUCATION_DEGREES = ["S2", "S3"].map((value) => ({
  value,
  label: value,
}));

const lecturerColumns = (userEmail: (row: Lecturer) => string) => [
  {
    key: "lecturer_number" as const,
    label: "NIP/NIDN",
    className: "fw-medium",
  },
  { key: "name" as const, label: "Nama Dosen" },
  {
    key: "study_program_id" as const,
    label: "Prodi",
    render: (row: Lecturer) => (
      <FkLabel
        endpoint="/study-programs"
        id={row.study_program_id}
        labelParts={["code", "name"]}
      />
    ),
  },
  {
    key: "academic_position" as const,
    label: "Jabatan Akademik",
    render: (row: Lecturer) => POSITION_LABELS[row.academic_position] ?? row.academic_position ?? "—",
  },
  {
    key: "employment_status" as const,
    label: "Status Pegawai",
    render: (row: Lecturer) =>
      row.employment_status ? (
        <Badge className="bg-secondary-subtle text-secondary">
          {EMPLOYMENT_LABELS[row.employment_status] ?? row.employment_status}
        </Badge>
      ) : (
        <span className="text-muted">—</span>
      ),
  },
  {
    key: "user_email" as const,
    label: "Akun",
    render: (row: Lecturer) =>
      userEmail(row) ? (
        <span className="fs-13">
          <IconifyIcon icon="ri:user-3-line" className="me-1 text-muted" />
          {userEmail(row)}
        </span>
      ) : (
        <span className="text-muted fs-13">belum ada akun</span>
      ),
  },
];

const dosenConfig: CrudPageConfig<Lecturer> = {
  endpoint: "/lecturers",
  permission: "lecturers.read",
  title: "Dosen",
  subtitle: "Kelola data dosen — akun login dibuat otomatis oleh sistem.",
  apiModuleLabel: "Dosen",
  formSize: "lg",
  filterFields: [
    {
      key: "study_program_id",
      label: "Semua Program Studi",
      colMd: 4,
      options: {
        kind: "resource",
        endpoint: "/study-programs",
        labelParts: ["code", "name"],
      },
    },
    {
      key: "academic_position",
      label: "Semua Jabatan",
      colMd: 4,
      options: { kind: "static", options: ACADEMIC_POSITIONS },
    },
  ],
  columns: lecturerColumns((row) => row.user?.email ?? row.user_email ?? ""),
  formFields: [
    { name: "_h_akun", label: "Akun Login", type: "text", heading: "Akun Login" },
    {
      name: "user_email",
      label: "Email Akun",
      type: "text",
      subtype: "email",
      required: true,
      colMd: 6,
      placeholder: "dosen@kampus.ac.id",
      helperText: "Akun login dibuat otomatis oleh backend saat data dosen disimpan.",
    },
    {
      name: "user_name",
      label: "Nama Akun (opsional)",
      type: "text",
      colMd: 6,
      helperText: "Kosongkan untuk memakai nama dosen.",
    },
    {
      name: "user_password",
      label: "Password Akun (opsional)",
      type: "text",
      subtype: "password",
      colMd: 6,
      helperText: "Kosongkan untuk password default dari backend.",
      hideOnEdit: true,
    },
    { name: "_h_data", label: "Data Dosen", type: "text", heading: "Data Dosen" },
    {
      name: "lecturer_number",
      label: "NIP / NIDN",
      type: "text",
      required: true,
      colMd: 6,
    },
    { name: "name", label: "Nama Lengkap Dosen", type: "text", required: true, colMd: 6 },
    {
      name: "study_program_id",
      label: "Program Studi",
      type: "select",
      required: true,
      colMd: 6,
      options: {
        kind: "resource",
        endpoint: "/study-programs",
        labelParts: ["code", "name"],
      },
    },
    {
      name: "academic_position",
      label: "Jabatan Akademik",
      type: "select",
      required: true,
      colMd: 6,
      options: { kind: "static", options: ACADEMIC_POSITIONS },
    },
    {
      name: "employment_status",
      label: "Status Pegawai",
      type: "select",
      required: true,
      colMd: 6,
      options: { kind: "static", options: EMPLOYMENT_STATUSES },
    },
    {
      name: "education_degree",
      label: "Pendidikan Terakhir (opsional)",
      type: "select",
      colMd: 6,
      options: { kind: "static", options: EDUCATION_DEGREES },
    },
    {
      name: "gender",
      label: "Jenis Kelamin",
      type: "select",
      required: true,
      colMd: 6,
      options: {
        kind: "static",
        options: [
          { value: "L", label: "Laki-laki" },
          { value: "P", label: "Perempuan" },
        ],
      },
    },
    {
      name: "phone",
      label: "No. HP (opsional)",
      type: "text",
      subtype: "tel",
      colMd: 6,
      placeholder: "08xxxxxxxxxx",
    },
    {
      name: "address",
      label: "Alamat (opsional)",
      type: "textarea",
      colMd: 12,
    },
  ],
};

const DosenPage = () => <ResourceCrudPage config={dosenConfig} />;

export default DosenPage;
