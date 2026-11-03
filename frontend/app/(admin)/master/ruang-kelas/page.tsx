"use client";
import FkLabel from "@/components/shared/crud/FkLabel";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { ClassRoom } from "@/types/master";
import { Badge } from "react-bootstrap";

/** Master Ruang Kelas (Phase 3) — filter per gedung. */

const ROOM_TYPE_BADGE: Record<string, string> = {
  teori: "bg-primary-subtle text-primary",
  laboratorium: "bg-warning-subtle text-warning",
  seminar: "bg-info-subtle text-info",
};

const ruangKelasConfig: CrudPageConfig<ClassRoom> = {
  endpoint: "/class-rooms",
  permission: "class_rooms.read",
  title: "Ruang Kelas",
  subtitle: "Kelola daftar ruang kuliah beserta kapasitas & tipe ruangnya.",
  apiModuleLabel: "Ruang Kelas",
  filterFields: [
    {
      key: "building_id",
      label: "Semua Gedung",
      colMd: 5,
      options: { kind: "resource", endpoint: "/buildings", labelKey: "name" },
    },
  ],
  columns: [
    { key: "code", label: "Kode", className: "fw-medium" },
    { key: "name", label: "Nama Ruang" },
    {
      key: "building_id",
      label: "Gedung",
      render: (row) => (
        <FkLabel endpoint="/buildings" id={row.building_id} labelKey="name" />
      ),
    },
    {
      key: "capacity",
      label: "Kapasitas",
      className: "text-center",
      render: (row) => row.capacity ?? "—",
    },
    {
      key: "room_type",
      label: "Tipe",
      render: (row) => (
        <Badge className={ROOM_TYPE_BADGE[String(row.room_type)] ?? "bg-secondary-subtle text-secondary"}>
          {String(row.room_type).charAt(0).toUpperCase() + String(row.room_type).slice(1)}
        </Badge>
      ),
    },
  ],
  formFields: [
    {
      name: "building_id",
      label: "Gedung",
      type: "select",
      required: true,
      colMd: 12,
      options: { kind: "resource", endpoint: "/buildings", labelKey: "name" },
    },
    {
      name: "code",
      label: "Kode Ruang",
      type: "text",
      required: true,
      colMd: 6,
      placeholder: "mis. A-101",
    },
    { name: "name", label: "Nama Ruang", type: "text", required: true, colMd: 6 },
    {
      name: "capacity",
      label: "Kapasitas",
      type: "number",
      required: true,
      colMd: 6,
      min: 1,
      placeholder: "mis. 40",
    },
    {
      name: "room_type",
      label: "Tipe Ruang",
      type: "select",
      required: true,
      colMd: 6,
      options: {
        kind: "static",
        options: [
          { value: "teori", label: "Teori" },
          { value: "laboratorium", label: "Laboratorium" },
          { value: "seminar", label: "Seminar" },
        ],
      },
    },
  ],
};

const RuangKelasPage = () => <ResourceCrudPage config={ruangKelasConfig} />;

export default RuangKelasPage;
