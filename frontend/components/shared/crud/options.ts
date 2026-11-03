"use client";
/**
 * Helper opsi select resource untuk engine CRUD — shared cache react-query
 * (queryKey `["crud-options", endpoint, params]`) supaya dropdown filter bar,
 * form, dan kolom lookup table memakai satu request yang sama.
 */
import { useQuery } from "@tanstack/react-query";
import { listResource } from "@/services/api/master";
import type { CrudOptionSource } from "./types";

export type ResourceRow = Record<string, unknown>;

/** Label item resource dari konfigurasi (labelOf > labelParts > labelKey > "name"). */
export function labelOfItem(
  item: ResourceRow,
  source?: Pick<
    CrudOptionSource,
    "labelKey" | "labelParts" | "labelOf"
  > | null,
): string {
  if (item == null) return "-";
  if (source?.labelOf) return source.labelOf(item);
  const parts =
    source?.labelParts ?? (source?.labelKey ? [source.labelKey] : ["name"]);
  const label = parts
    .map((key) => {
      const value = (item as ResourceRow)[key];
      return value == null ? "" : String(value);
    })
    .filter(Boolean)
    .join(" — ");
  return label || "-";
}

export function useResourceOptions(
  endpoint: string,
  params?: Record<string, string | number>,
  enabled = true,
) {
  const query = useQuery({
    queryKey: ["crud-options", endpoint, params ?? {}],
    queryFn: () => listResource<ResourceRow>(endpoint, params),
    enabled,
    staleTime: 60_000,
  });
  return {
    rows: query.data?.data ?? [],
    isLoading: query.isLoading,
  };
}
