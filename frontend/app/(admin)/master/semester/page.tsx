"use client";
import FkLabel from "@/components/shared/crud/FkLabel";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { CrudRowActionContext } from "@/components/shared/crud/useResourceCrud";
import { activateSemester } from "@/services/api/master";
import type { Semester } from "@/types/master";
import { getApiErrorMessage } from "@/lib/apiError";
import { formatDate } from "@/lib/format";
import { toast } from "react-toastify";
import { Badge, Dropdown } from "react-bootstrap";
import IconifyIcon from "@/components/wrappers/IconifyIcon";

/** Master Semester (Phase 3) — semester aktif tunggal + aksi "Aktifkan". */

const semesterConfig: CrudPageConfig<Semester> = {
  endpoint: "/semesters",
  permission: "semesters.read",
  title: "Semester",
  subtitle:
    "Kelola semester (ganjil/genap) per tahun akademik, termasuk rentang tanggal KRS.",
  apiModuleLabel: "Semester",
  filterFields: [
    {
      key: "academic_year_id",
      label: "Semua Tahun Akademik",
      colMd: 5,
      options: { kind: "resource", endpoint: "/academic-years", labelKey: "year" },
    },
  ],
  columns: [
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
      key: "name",
      label: "Semester",
      render: (row) => {
        const semester = String(row.name);
        return (
          <Badge
            className={
              semester.toLowerCase() === "ganjil"
                ? "bg-primary-subtle text-primary"
                : "bg-info-subtle text-info"
            }
          >
            {semester.charAt(0).toUpperCase() + semester.slice(1)}
          </Badge>
        );
      },
    },
    {
      key: "start_date",
      label: "Mulai",
      render: (row) => <span className="text-nowrap">{formatDate(row.start_date)}</span>,
    },
    {
      key: "end_date",
      label: "Selesai",
      render: (row) => <span className="text-nowrap">{formatDate(row.end_date)}</span>,
    },
    {
      key: "krs_range",
      label: "Periode KRS",
      render: (row) =>
        row.krs_start || row.krs_end ? (
          <span className="text-nowrap fs-13">
            {formatDate(row.krs_start)} &ndash; {formatDate(row.krs_end)}
          </span>
        ) : (
          <span className="text-muted">—</span>
        ),
    },
    {
      key: "is_active",
      label: "Status",
      render: (row) =>
        row.is_active ? (
          <span className="badge bg-success-subtle text-success">Aktif</span>
        ) : (
          <span className="badge bg-secondary-subtle text-secondary">Nonaktif</span>
        ),
    },
  ],
  formFields: [
    {
      name: "academic_year_id",
      label: "Tahun Akademik",
      type: "select",
      required: true,
      colMd: 12,
      options: { kind: "resource", endpoint: "/academic-years", labelKey: "year" },
    },
    {
      name: "name",
      label: "Jenis Semester",
      type: "select",
      required: true,
      colMd: 6,
      options: {
        kind: "static",
        options: [
          { value: "ganjil", label: "Ganjil" },
          { value: "genap", label: "Genap" },
        ],
      },
    },
    {
      name: "is_active",
      label: "Jadikan semester aktif",
      type: "checkbox",
      colMd: 6,
      defaultValue: true,
      helperText:
        "Server memastikan hanya satu semester aktif pada waktu bersamaan.",
    },
    { name: "start_date", label: "Tanggal Mulai", type: "date", required: true, colMd: 6 },
    {
      name: "end_date",
      label: "Tanggal Selesai",
      type: "date",
      required: true,
      colMd: 6,
    },
    { name: "krs_start", label: "KRS Mulai (opsional)", type: "date", colMd: 6 },
    { name: "krs_end", label: "KRS Selesai (opsional)", type: "date", colMd: 6 },
  ],
};

/** extraActions "Aktifkan" — POST /semesters/{id}/activate via ConfirmDialog. */
const semesterExtraActions = (ctx: CrudRowActionContext<Semester>) => {
  if (ctx.row.is_active) return [];
  return [
    <Dropdown.Item
      key={`activate-${String(ctx.row.id)}`}
      disabled={ctx.busyId === ctx.row.id}
      onClick={() =>
        ctx.confirmAction({
          title: "Aktifkan Semester",
          message: (
            <>
              Aktifkan semester{" "}
              <strong>{String(ctx.row.name).toUpperCase()}</strong>? Semester
              lain yang sedang aktif akan otomatis dinonaktifkan oleh sistem.
            </>
          ),
          variant: "primary",
          confirmLabel: "Ya, Aktifkan",
          run: async () => {
            ctx.setBusyId(ctx.row.id);
            try {
              await activateSemester(ctx.row.id);
              toast.success("Semester berhasil diaktifkan.");
              ctx.refresh();
            } catch (error) {
              toast.error(getApiErrorMessage(error));
            } finally {
              ctx.setBusyId(null);
            }
          },
        })
      }
    >
      <IconifyIcon icon="ri:check-double-line" className="me-2" />
      Aktifkan
    </Dropdown.Item>,
  ];
};

const SemesterPage = () => (
  <ResourceCrudPage config={semesterConfig} extraActions={semesterExtraActions} />
);

export default SemesterPage;
