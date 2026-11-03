"use client";
/**
 * Modal "Kelola MK" kurikulum (Phase 3) — dua tab:
 * 1. Mata Kuliah: daftar course terpasang (pivot) + form attach (course dari
 *    /courses?study_program_id=<prodi kurikulum>, semester, wajib) + hapus.
 * 2. Prasyarat: pilih course terpasang → lihat prasyaratnya → tambah/hapus.
 * STRUKTUR data prasyarat API bisa bervariasi → normalizer defensif.
 */
import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "react-toastify";
import {
  Badge,
  Button,
  Col,
  Form,
  Modal,
  Row,
  Tab,
  Tabs,
  Table,
} from "react-bootstrap";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import ConfirmDialog from "@/components/shared/ConfirmDialog";
import { getApiErrorMessage } from "@/lib/apiError";
import { labelOfItem, useResourceOptions } from "@/components/shared/crud/options";
import {
  addCurriculumCourse,
  addCurriculumPrerequisite,
  getCurriculumDetail,
  removeCurriculumCourse,
  removeCurriculumPrerequisite,
} from "@/services/api/master";
import type {
  CurriculumCourseRef,
  CurriculumPrerequisiteRef,
} from "@/types/master";

type ManageModalProps = {
  show: boolean;
  curriculumId: number | null;
  onHide: () => void;
};

/** Baris prasyarat hasil normalizer — render tab Prasyarat. */
type PrereqRow = {
  /** id pivot course utk baris ini (sebagai mata kuliah "induk"). */
  curriculumCourseId: number | string | null;
  /** id course prasyarat (dipakai di DELETE /prerequisites/{id}). */
  prerequisiteCourseId: number | string | null;
  label: string;
};

const asId = (value: unknown): number | string | null => {
  if (value === null || value === undefined || value === "") return null;
  return value as number | string;
};

const nested = (value: unknown): Record<string, unknown> | null =>
  value && typeof value === "object"
    ? (value as Record<string, unknown>)
    : null;

/** Bentuk API prasyarat bervariasi → normalisasi 3 kemungkinan shape. */
function normalizePrereq(
  row: CurriculumPrerequisiteRef,
  courses: CurriculumCourseRef[],
): PrereqRow {
  const curriculumCourseId =
    asId(row.curriculum_course_id) ??
    asId(row.curriculumCourseId) ??
    asId(nested(row.curriculum_course)?.id) ??
    asId(row.pivot_id);

  const prerequisiteCourseId =
    asId(row.prerequisite_course_id) ??
    asId(row.prerequisiteCourseId) ??
    asId(nested(row.prerequisite_course)?.id) ??
    asId(nested(row.prerequisite)?.id);

  // Label: nested course → daftar course terpasang (by id/ course_id) → fallback.
  let label: string | null = null;
  const nestedPrereq = nested(row.prerequisite_course) ?? nested(row.prerequisite);
  if (nestedPrereq && (nestedPrereq.name || nestedPrereq.code)) {
    label = [nestedPrereq.code, nestedPrereq.name]
      .filter(Boolean)
      .map(String)
      .join(" — ");
  }
  if (!label && prerequisiteCourseId != null) {
    const attached = courses.find(
      (c) =>
        String(c.id) === String(prerequisiteCourseId) ||
        String(c.course_id) === String(prerequisiteCourseId),
    );
    if (attached?.course) {
      label = [attached.course.code, attached.course.name]
        .filter(Boolean)
        .join(" — ");
    }
  }

  return {
    curriculumCourseId,
    prerequisiteCourseId,
    label: label ?? `#${String(prerequisiteCourseId ?? "?")}`,
  };
}

const CurriculumManageModal = ({
  show,
  curriculumId,
  onHide,
}: ManageModalProps) => {
  const queryClient = useQueryClient();
  const [tabKey, setTabKey] = useState("courses");

  // ---- detail kurikulum ----
  const detailQuery = useQuery({
    queryKey: ["curriculum-detail", curriculumId],
    queryFn: () => getCurriculumDetail(curriculumId as number),
    enabled: Boolean(curriculumId),
  });
  const detail = detailQuery.data ?? null;
  const courses = detail?.courses ?? [];
  const prereqs = useMemo(() => {
    const raw = detail?.prerequisites ?? [];
    return raw.map((row) => normalizePrereq(row, courses));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [detailQuery.data]);

  const invalidate = () => {
    void queryClient.invalidateQueries({
      queryKey: ["curriculum-detail", curriculumId],
    });
    void queryClient.invalidateQueries({ queryKey: ["crud", "/curriculums"] });
  };

  // ---- tab 1: attach course ----
  const [attachCourseId, setAttachCourseId] = useState("");
  const [attachSemester, setAttachSemester] = useState("");
  const [attachMandatory, setAttachMandatory] = useState(true);

  const courseOptions = useResourceOptions(
    "/courses",
    detail?.study_program_id
      ? { per_page: 100, study_program_id: detail.study_program_id }
      : { per_page: 100 },
    show,
  );
  const attachedCourseIds = new Set(courses.map((c) => String(c.course_id)));
  const attachOptions = courseOptions.rows.filter(
    (row) => !attachedCourseIds.has(String(row.id)),
  );

  const addCourseMutation = useMutation({
    mutationFn: ({
      id,
      payload,
    }: {
      id: number;
      payload: {
        course_id: number;
        semester: number;
        is_mandatory: boolean;
      };
    }) => addCurriculumCourse(id, payload),
  });
  const removeCourseMutation = useMutation({
    mutationFn: ({
      id,
      courseId,
    }: {
      id: number;
      courseId: number | string;
    }) => removeCurriculumCourse(id, courseId),
  });

  const handleAttachCourse = async () => {
    if (!curriculumId || !attachCourseId) {
      toast.error("Pilih mata kuliah terlebih dahulu.");
      return;
    }
    const semester = Number(attachSemester);
    if (Number.isNaN(semester) || semester < 1) {
      toast.error("Semester harus berupa angka (1–14).");
      return;
    }
    try {
      await addCourseMutation.mutateAsync({
        id: curriculumId,
        payload: {
          course_id: Number(attachCourseId),
          semester,
          is_mandatory: attachMandatory,
        },
      });
      toast.success("Mata kuliah ditambahkan ke kurikulum.");
      setAttachCourseId("");
      setAttachSemester("");
      setAttachMandatory(true);
      invalidate();
    } catch (error) {
      toast.error(getApiErrorMessage(error));
    }
  };

  const [courseDeleteTarget, setCourseDeleteTarget] =
    useState<CurriculumCourseRef | null>(null);

  const handleRemoveCourse = async () => {
    if (!curriculumId || !courseDeleteTarget) return;
    try {
      await removeCourseMutation.mutateAsync({
        id: curriculumId,
        // Backend: DELETE /curriculums/{id}/courses/{courseId} pakai course_id
        // (bukan id pivot), sesuai CurriculumController::detachCourse.
        courseId: courseDeleteTarget.course_id,
      });
      toast.success("Mata kuliah dihapus dari kurikulum.");
      setCourseDeleteTarget(null);
      invalidate();
    } catch (error) {
      toast.error(getApiErrorMessage(error));
      setCourseDeleteTarget(null);
    }
  };

  // ---- tab 2: prasyarat ----
  const [selectedPivotId, setSelectedPivotId] = useState("");
  const selectedCourse = useMemo(
    () =>
      courses.find((c) => String(c.id) === String(selectedPivotId)) ?? null,
    [courses, selectedPivotId],
  );
  const courseNameId = (row: CurriculumCourseRef): string => {
    if (row.course) return `${row.course.code} — ${row.course.name}`;
    return `Course #${String(row.course_id)}`;
  };
  const prereqList = prereqs.filter(
    (p) =>
      selectedPivotId !== "" &&
      String(p.curriculumCourseId) === String(selectedPivotId),
  );
  const existingPrereqIds = new Set(
    prereqList.map((p) => String(p.prerequisiteCourseId)),
  );
  const addableOptions = courses.filter(
    (c) =>
      String(c.id) !== String(selectedPivotId) &&
      !existingPrereqIds.has(String(c.course_id)) &&
      !existingPrereqIds.has(String(c.id)),
  );

  const [prereqCourseId, setPrereqCourseId] = useState("");

  const addPrereqMutation = useMutation({
    mutationFn: ({
      id,
      payload,
    }: {
      id: number;
      payload: { curriculum_course_id: number; prerequisite_course_id: number };
    }) => addCurriculumPrerequisite(id, payload),
  });
  const removePrereqMutation = useMutation({
    mutationFn: ({
      id,
      prerequisiteCourseId,
    }: {
      id: number;
      prerequisiteCourseId: number | string;
    }) => removeCurriculumPrerequisite(id, prerequisiteCourseId),
  });

  const handleAddPrereq = async () => {
    if (!curriculumId || !selectedCourse || !prereqCourseId) {
      toast.error("Pilih mata kuliah dan prasyaratnya terlebih dahulu.");
      return;
    }
    try {
      await addPrereqMutation.mutateAsync({
        id: curriculumId,
        // Asumsi: curriculum_course_id = id pivot baris course induk.
        payload: {
          curriculum_course_id: Number(selectedCourse.id),
          prerequisite_course_id: Number(prereqCourseId),
        },
      });
      toast.success("Prasyarat ditambahkan.");
      setPrereqCourseId("");
      invalidate();
    } catch (error) {
      toast.error(getApiErrorMessage(error));
    }
  };

  const handleRemovePrereq = async (prereq: PrereqRow) => {
    if (!curriculumId || prereq.prerequisiteCourseId == null) return;
    try {
      await removePrereqMutation.mutateAsync({
        id: curriculumId,
        // Asumsi: id = id COURSE prasyarat (prerequisite_course_id).
        prerequisiteCourseId: prereq.prerequisiteCourseId,
      });
      toast.success("Prasyarat dihapus.");
      invalidate();
    } catch (error) {
      toast.error(getApiErrorMessage(error));
    }
  };

  const busyTab = addCourseMutation.isPending || addPrereqMutation.isPending;

  return (
    <>
      <Modal show={show} onHide={onHide} centered size="xl" backdrop="static">
        <Modal.Header closeButton>
          <Modal.Title className="fs-16 fw-semibold">
            Kelola Mata Kuliah —{" "}
            {detail ? `${detail.code} ${detail.name}` : "Kurikulum"}
            {detail && (
              <Badge className="ms-2 bg-primary-subtle text-primary">
                Total {detail.total_sks} SKS
              </Badge>
            )}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {detailQuery.isLoading ? (
            <div className="py-5 text-center text-muted">
              <span className="spinner-border spinner-border-sm me-2" />
              Memuat detail kurikulum...
            </div>
          ) : detailQuery.isError ? (
            <div className="alert alert-danger py-2 fs-13">
              Gagal memuat detail kurikulum. Tutup modal lalu buka ulang.
            </div>
          ) : (
            <Tabs
              activeKey={tabKey}
              onSelect={(key) => setTabKey(key ?? "courses")}
              className="mb-2"
              defaultActiveKey="courses"
            >
              <Tab
                eventKey="courses"
                title={
                  <span>
                    <IconifyIcon icon="ri-book-2-line" className="me-1" />
                    Mata Kuliah ({courses.length})
                  </span>
                }
              >
                <Table responsive hover size="sm" className="mb-2 align-middle">
                  <thead className="table-light">
                    <tr>
                      <th style={{ width: 90 }}>Kode</th>
                      <th>Nama Mata Kuliah</th>
                      <th className="text-center" style={{ width: 60 }}>SKS</th>
                      <th className="text-center" style={{ width: 90 }}>Semester</th>
                      <th className="text-center" style={{ width: 80 }}>Wajib</th>
                      <th className="text-end" style={{ width: 70 }}>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    {courses.length === 0 ? (
                      <tr>
                        <td colSpan={6} className="text-center text-muted py-4">
                          Belum ada mata kuliah terpasang pada kurikulum ini.
                        </td>
                      </tr>
                    ) : (
                      courses.map((row) => (
                        <tr key={String(row.id)}>
                          <td className="fw-medium">
                            {row.course?.code ?? `#${String(row.course_id)}`}
                          </td>
                          <td>{row.course?.name ?? `Course #${String(row.course_id)}`}</td>
                          <td className="text-center">{row.course?.sks ?? "—"}</td>
                          <td className="text-center">
                            {row.semester ?? row.course?.semester ?? "—"}
                          </td>
                          <td className="text-center">
                            {row.is_mandatory ? (
                              <Badge className="bg-success-subtle text-success">Wajib</Badge>
                            ) : (
                              <Badge className="bg-warning-subtle text-warning">Pilihan</Badge>
                            )}
                          </td>
                          <td className="text-end">
                            <Button
                              variant="outline-danger"
                              size="sm"
                              disabled={removeCourseMutation.isPending}
                              onClick={() => setCourseDeleteTarget(row)}
                            >
                              <IconifyIcon icon="ri:delete-bin-line" />
                            </Button>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </Table>

                {/* form attach course */}
                <div className="border-top pt-2">
                  <Row className="g-2 align-items-end">
                    <Col md={5}>
                      <Form.Label className="mb-1 fs-13 fw-medium">
                        Mata Kuliah
                      </Form.Label>
                      <Form.Select
                        value={attachCourseId}
                        onChange={(e) => setAttachCourseId(e.target.value)}
                        disabled={courseOptions.isLoading}
                      >
                        <option value="">— Pilih Mata Kuliah —</option>
                        {attachOptions.map((row) => (
                          <option key={String(row.id)} value={String(row.id)}>
                            {labelOfItem(row, {
                              labelParts: ["code", "name"],
                            })}
                          </option>
                        ))}
                      </Form.Select>
                    </Col>
                    <Col md={3}>
                      <Form.Label className="mb-1 fs-13 fw-medium">
                        Semester
                      </Form.Label>
                      <Form.Control
                        type="number"
                        min={1}
                        max={14}
                        value={attachSemester}
                        placeholder="1–14"
                        onChange={(e) => setAttachSemester(e.target.value)}
                      />
                    </Col>
                    <Col md={2}>
                      <Form.Check
                        type="checkbox"
                        id="curriculum-attach-mandatory"
                        label="Wajib"
                        checked={attachMandatory}
                        onChange={(e) => setAttachMandatory(e.target.checked)}
                        className="mt-4"
                      />
                    </Col>
                    <Col md={2} className="text-md-end">
                      <Button
                        variant="primary"
                        disabled={busyTab}
                        onClick={() => void handleAttachCourse()}
                      >
                        <IconifyIcon icon="ri:add-line" className="me-1" />
                        Tambah
                      </Button>
                    </Col>
                  </Row>
                </div>
              </Tab>

              <Tab
                eventKey="prerequisites"
                title={
                  <span>
                    <IconifyIcon icon="ri:git-merge-line" className="me-1" />
                    Prasyarat
                  </span>
                }
              >
                {courses.length === 0 ? (
                  <div className="text-center text-muted py-5">
                    Pasang mata kuliah terlebih dahulu pada tab Mata Kuliah.
                  </div>
                ) : (
                  <>
                    <Row className="g-2 align-items-end mb-3">
                      <Col md={6}>
                        <Form.Label className="mb-1 fs-13 fw-medium">
                          Lihat prasyarat untuk
                        </Form.Label>
                        <Form.Select
                          value={selectedPivotId}
                          onChange={(e) => {
                            setSelectedPivotId(e.target.value);
                            setPrereqCourseId("");
                          }}
                        >
                          <option value="">— Pilih Mata Kuliah —</option>
                          {courses.map((row) => (
                            <option key={String(row.id)} value={String(row.id)}>
                              {courseNameId(row)} (Semester {String(row.semester)})
                            </option>
                          ))}
                        </Form.Select>
                      </Col>
                    </Row>

                    {selectedCourse ? (
                      <>
                        <Table responsive hover size="sm" className="mb-2 align-middle">
                          <thead className="table-light">
                            <tr>
                              <th className="ps-2">Mata Kuliah Prasyarat</th>
                              <th className="text-end" style={{ width: 70 }}>Aksi</th>
                            </tr>
                          </thead>
                          <tbody>
                            {prereqList.length === 0 ? (
                              <tr>
                                <td className="text-center text-muted py-4" colSpan={2}>
                                  Belum ada prasyarat untuk{" "}
                                  <strong>{courseNameId(selectedCourse)}</strong>.
                                </td>
                              </tr>
                            ) : (
                              prereqList.map((prereq, idx) => (
                                <tr
                                  key={`${String(prereq.prerequisiteCourseId)}-${idx}`}
                                >
                                  <td className="ps-2">{prereq.label}</td>
                                  <td className="text-end">
                                    <Button
                                      variant="outline-danger"
                                      size="sm"
                                      disabled={removePrereqMutation.isPending}
                                      onClick={() => void handleRemovePrereq(prereq)}
                                    >
                                      <IconifyIcon icon="ri:delete-bin-line" />
                                    </Button>
                                  </td>
                                </tr>
                              ))
                            )}
                          </tbody>
                        </Table>

                        <div className="border-top pt-2">
                          <Row className="g-2 align-items-end">
                            <Col md={8}>
                              <Form.Label className="mb-1 fs-13 fw-medium">
                                Tambah prasyarat dari daftar course terpasang
                              </Form.Label>
                              <Form.Select
                                value={prereqCourseId}
                                onChange={(e) => setPrereqCourseId(e.target.value)}
                                disabled={addableOptions.length === 0}
                              >
                                <option value="">
                                  {addableOptions.length === 0
                                    ? "Semua mata kuliah lain sudah menjadi prasyarat"
                                    : "— Pilih Mata Kuliah —"}
                                </option>
                                {addableOptions.map((row) => (
                                  <option key={String(row.id)} value={String(row.id)}>
                                    {courseNameId(row)}
                                  </option>
                                ))}
                              </Form.Select>
                            </Col>
                            <Col md={4} className="text-md-end">
                              <Button
                                variant="primary"
                                disabled={
                                  busyTab ||
                                  !prereqCourseId ||
                                  addableOptions.length === 0
                                }
                                onClick={() => void handleAddPrereq()}
                              >
                                <IconifyIcon icon="ri:add-line" className="me-1" />
                                Tambah Prasyarat
                              </Button>
                            </Col>
                          </Row>
                        </div>
                      </>
                    ) : (
                      <p className="text-muted fs-13 mb-0">
                        Pilih mata kuliah di atas untuk mengelola prasyaratnya.
                      </p>
                    )}
                  </>
                )}
              </Tab>
            </Tabs>
          )}
        </Modal.Body>
      </Modal>

      <ConfirmDialog
        open={Boolean(courseDeleteTarget)}
        title="Hapus Mata Kuliah"
        message={
          <>
            Hapus{" "}
            <strong>
              {courseDeleteTarget?.course
                ? `${courseDeleteTarget.course.code} — ${courseDeleteTarget.course.name}`
                : `course #${String(courseDeleteTarget?.course_id ?? "")}`}
            </strong>{" "}
            dari kurikulum beserta relasi prasyaratnya?
          </>
        }
        busy={removeCourseMutation.isPending}
        variant="danger"
        confirmLabel="Ya, Hapus"
        onConfirm={handleRemoveCourse}
        onCancel={() => setCourseDeleteTarget(null)}
      />
    </>
  );
};

export default CurriculumManageModal;
