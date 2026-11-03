/**
 * Service CRUD Data Master (Phase 3) — kontrak generic CRUD per api/v1:
 * list GET/{base}?search&page&per_page&<filters>, item GET/{base}/{id},
 * create POST/{base}, update PATCH/{base}/{id}, delete DELETE/{base}/{id}.
 */
import api from "@/lib/api";
import type { ApiResponse, ApiMeta } from "@/types/api";
import type { CurriculumDetail } from "@/types/master";

export type ResourceListResult<TItem> = {
  data: TItem[];
  meta?: ApiMeta;
};

/** GET /{base} generic — param lewat query (search, page, per_page, filter...). */
export async function listResource<TItem = Record<string, unknown>>(
  endpoint: string,
  params?: Record<string, string | number | undefined>,
): Promise<ResourceListResult<TItem>> {
  const res = await api.get<ApiResponse<TItem[]>>(endpoint, { params });
  return { data: res.data.data ?? [], meta: res.data.meta };
}

export async function createResource(
  endpoint: string,
  payload: Record<string, unknown>,
): Promise<void> {
  await api.post(endpoint, payload);
}

export async function updateResource(
  endpoint: string,
  id: number | string,
  payload: Record<string, unknown>,
): Promise<void> {
  await api.patch(`${endpoint}/${id}`, payload);
}

export async function destroyResource(
  endpoint: string,
  id: number | string,
): Promise<void> {
  await api.delete(`${endpoint}/${id}`);
}

/** action khusus semester: single is_active server-side. */
export async function activateSemester(id: number | string): Promise<void> {
  await api.post(`/semesters/${id}/activate`);
}

// ===== CRUD nested di kurikulum (courses attach & prasyarat) =====

export async function getCurriculumDetail(
  id: number | string,
): Promise<CurriculumDetail> {
  const res = await api.get<ApiResponse<CurriculumDetail>>(`/curriculums/${id}`);
  return res.data.data as CurriculumDetail;
}

export async function addCurriculumCourse(
  curriculumId: number | string,
  payload: { course_id: number; semester: number; is_mandatory: boolean },
): Promise<void> {
  await api.post(`/curriculums/${curriculumId}/courses`, payload);
}

export async function removeCurriculumCourse(
  curriculumId: number | string,
  courseId: number | string,
): Promise<void> {
  await api.delete(`/curriculums/${curriculumId}/courses/${courseId}`);
}

export async function addCurriculumPrerequisite(
  curriculumId: number | string,
  payload: { curriculum_course_id: number; prerequisite_course_id: number },
): Promise<void> {
  await api.post(`/curriculums/${curriculumId}/prerequisites`, payload);
}

export async function removeCurriculumPrerequisite(
  curriculumId: number | string,
  prerequisiteCourseId: number | string,
): Promise<void> {
  await api.delete(
    `/curriculums/${curriculumId}/prerequisites/${prerequisiteCourseId}`,
  );
}
