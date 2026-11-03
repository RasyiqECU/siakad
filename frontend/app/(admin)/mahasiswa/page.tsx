"use client";
import FkLabel from "@/components/shared/crud/FkLabel";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { Student } from "@/types/master";
import { Badge } from "react-bootstrap";
import IconifyIcon from "@/components/wrappers/IconifyIcon";

/**
 * Master Mahasiswa (Phase 3) — biodata lengkap + data orang tua, dan akun
 * login dibuat otomatis (user_email). Angkatan = tahun masuk (`entry_year`).
 */
const RELIGIONS = [
  "Islam",
  "Kristen Protestan",
  "Kristen Katolik",
  "Hindu",
  "Buddha",
  "Konghucu",
].map((value) => ({ value, label: value }));

const currentYear = new Date().getFullYear();
const ENTRY_YEAR_OPTIONS = Array.from({ length: 11 }, (_, idx) => currentYear - 10 + idx)
  .map((year) => ({ value: year, label: String(year) }));

const mahasiswaConfig: CrudPageConfig<Student> = {
  endpoint: "/students",
  permission: "students.read",
  title: "Mahasiswa",
  subtitle:
    "Kelola data mahasiswa — akun login dibuat otomatis saat data disimpan.",
  apiModuleLabel: "Mahasiswa",
  formSize: "xl",
  filterFields: [
    {
      key: "study_program_id",
      label: "Semua Program Studi",
      colMd: 3,
      options: {
        kind: "resource",
        endpoint: "/study-programs",
        labelParts: ["code", "name"],
      },
    },
    {
      key: "entry_year",
      label: "Semua Angkatan",
      colMd: 2,
      options: { kind: "static", options: ENTRY_YEAR_OPTIONS },
    },
    {
      key: "academic_status_id",
      label: "Semua Status",
      colMd: 3,
      options: {
        kind: "resource",
        endpoint: "/academic-statuses",
        labelKey: "name",
      },
    },
  ],
  columns: [
    {
      key: "student_number",
      label: "NIM",
      className: "fw-medium",
    },
    { key: "name", label: "Nama Mahasiswa" },
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
      key: "entry_year",
      label: "Angkatan",
      className: "text-center",
      render: (row) => row.entry_year ?? "—",
    },
    {
      key: "academic_status_id",
      label: "Status",
      render: (row) => {
        const name =
          row.academic_status?.name ??
          (row.academic_status_id ? (
            <FkLabel
              endpoint="/academic-statuses"
              id={row.academic_status_id}
              labelKey="name"
            />
          ) : null);
        if (!name) return <span className="text-muted">—</span>;
        return (
          <Badge
            className={row.academic_status?.color ? "border-0" : "bg-secondary-subtle text-secondary"}
            style={
              row.academic_status?.color
                ? { backgroundColor: row.academic_status.color, color: "#fff" }
                : undefined
            }
          >
            {row.academic_status?.name ?? name}
          </Badge>
        );
      },
    },
    {
      key: "user_email",
      label: "Akun",
      render: (row) =>
        row.user?.email ?? row.user_email ? (
          <span className="fs-13">
            <IconifyIcon icon="ri:user-3-line" className="me-1 text-muted" />
            {row.user?.email ?? row.user_email}
          </span>
        ) : (
          <span className="text-muted fs-13">belum ada akun</span>
        ),
    },
  ],
  formFields: [
    { name: "_h_akun", label: "Akun Login", type: "text", heading: "Akun Login" },
    {
      name: "user_email",
      label: "Email Akun",
      type: "text",
      subtype: "email",
      required: true,
      colMd: 6,
      placeholder: "mahasiswa@kampus.ac.id",
      helperText: "Akun login dibuat otomatis oleh backend saat data disimpan.",
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
    { name: "_h_biodata", label: "Biodata", type: "text", heading: "Biodata" },
    {
      name: "student_number",
      label: "NIM",
      type: "text",
      required: true,
      colMd: 6,
    },
    { name: "name", label: "Nama Lengkap", type: "text", required: true, colMd: 6 },
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
      name: "entry_year",
      label: "Tahun Masuk (Angkatan)",
      type: "number",
      required: true,
      colMd: 6,
      min: 1990,
      max: 2100,
    },
    {
      name: "academic_status_id",
      label: "Status Akademik (opsional)",
      type: "select",
      colMd: 6,
      options: {
        kind: "resource",
        endpoint: "/academic-statuses",
        labelKey: "name",
      },
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
      name: "place_of_birth",
      label: "Tempat Lahir (opsional)",
      type: "text",
      colMd: 6,
    },
    { name: "date_of_birth", label: "Tanggal Lahir (opsional)", type: "date", colMd: 6 },
    {
      name: "religion",
      label: "Agama (opsional)",
      type: "select",
      colMd: 6,
      options: { kind: "static", options: RELIGIONS },
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
    { name: "_h_ortu", label: "Orang Tua / Wali", type: "text", heading: "Orang Tua / Wali" },
    {
      name: "father_name",
      label: "Nama Ayah (opsional)",
      type: "text",
      colMd: 6,
    },
    {
      name: "mother_name",
      label: "Nama Ibu (opsional)",
      type: "text",
      colMd: 6,
    },
    {
      name: "parent_phone",
      label: "No. HP Orang Tua (opsional)",
      type: "text",
      subtype: "tel",
      colMd: 6,
    },
  ],
};

const MahasiswaPage = () => <ResourceCrudPage config={mahasiswaConfig} />;

export default MahasiswaPage;
