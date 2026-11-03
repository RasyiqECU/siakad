"use client";
import ResourceCrudPage from "@/components/shared/crud/ResourceCrudPage";
import type { CrudPageConfig } from "@/components/shared/crud/types";
import type { Building } from "@/types/master";

/** Master Gedung (Phase 3). */

const gedungConfig: CrudPageConfig<Building> = {
  endpoint: "/buildings",
  permission: "buildings.read",
  title: "Gedung",
  subtitle: "Kelola daftar gedung kampus.",
  apiModuleLabel: "Gedung",
  columns: [
    { key: "code", label: "Kode", className: "fw-medium" },
    { key: "name", label: "Nama Gedung" },
  ],
  formFields: [
    {
      name: "code",
      label: "Kode Gedung",
      type: "text",
      required: true,
      colMd: 6,
      placeholder: "mis. A",
    },
    { name: "name", label: "Nama Gedung", type: "text", required: true, colMd: 6 },
  ],
};

const GedungPage = () => <ResourceCrudPage config={gedungConfig} />;

export default GedungPage;
