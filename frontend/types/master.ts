/**
 * Tipe entitas Data Master (Phase 3) — kontrak API_SPECIFICATION.md.
 * Semua tipe mengikuti envelope { success, message, data, meta? } milik backend.
 */

// ===== Struktur kurikulum =====

/** Baris pivot GET /curriculums/{id} — `id` = id baris pivot (bukan id course). */
export type CurriculumCourseRef = {
  /** id baris pivot (dipakai utk hapus/Delete prasyarat). */
  id: number | string;
  curriculum_id: number;
  course_id: number;
  /** Detail course ikut di-include pada detail kurikulum. */
  course?:
    | {
        id?: number;
        code: string;
        name: string;
        sks?: number;
        semester?: number;
      }
    | null;
  semester: number | string;
  is_mandatory: boolean;
};

/**
 * Baris prasyarat pada GET /curriculums/{id}.
 * Bentuk bisa berbeda antar-implementasi → dirender defensif oleh UI,
 * yaitu lewat normalizer (lihat components/app/(admin)/kurikulum).
 */
export type CurriculumPrerequisiteRef = Record<string, unknown>;

/** Baris GET /curriculums (list). */
export type Curriculum = {
  id: number;
  study_program_id: number;
  academic_year_id: number;
  code: string;
  name: string;
  total_sks: number;
  is_active: boolean;
  description?: string | null;
};

/** GET /curriculums/{id} — list + daftar course & prasyarat terpasang. */
export type CurriculumDetail = Curriculum & {
  courses: CurriculumCourseRef[];
  prerequisites?: CurriculumPrerequisiteRef[];
};

// ===== Fakultas → Jurusan → Prodi =====

export type Faculty = {
  id: number;
  code: string;
  name: string;
  description?: string | null;
};

export type Department = {
  id: number;
  faculty_id: number;
  code: string;
  name: string;
  /** Opsional: relasi ikut di-include bila backend menyediakannya. */
  faculty?: { id: number; code: string; name: string } | null;
};

export type DegreeLevel = "D3" | "D4" | "S1" | "S2" | "S3";

export type StudyProgram = {
  id: number;
  department_id: number;
  code: string;
  name: string;
  degree: DegreeLevel;
  is_active: boolean;
  department?: { id: number; code: string; name: string } | null;
};

// ===== Kalender akademik =====

/** Baris GET /academic-years — hanya satu yang is_active=true. */
export type AcademicYear = {
  id: number;
  /** Bisa angka ATAU format "2025/2026" → dirender apa adanya. */
  year: number | string;
  is_active: boolean;
};

export type SemesterTerm = "ganjil" | "genap";

export type Semester = {
  id: number;
  academic_year_id: number;
  name: SemesterTerm | string;
  start_date: string;
  end_date: string;
  krs_start?: string | null;
  krs_end?: string | null;
  is_active: boolean;
  academic_year?: { id: number; year: number | string } | null;
};

// ===== Status akademik & ruangan =====

export type AcademicStatus = {
  id: number;
  code: string;
  name: string;
  /** Hex color untuk badge (mis. "#dc3545"). */
  color?: string | null;
};

export type Building = {
  id: number;
  code: string;
  name: string;
};

export type ClassRoomType = "teori" | "laboratorium" | "seminar";

export type ClassRoom = {
  id: number;
  building_id: number;
  code: string;
  name: string;
  capacity: number;
  room_type: ClassRoomType | string;
  building?: { id: number; code: string; name: string } | null;
};

// ===== Mata kuliah =====

export type CourseType = "wajib" | "pilihan";

export type Course = {
  id: number;
  study_program_id: number;
  code: string;
  name: string;
  sks: number;
  semester: number;
  course_type: CourseType | string;
  description?: string | null;
};

// ===== Dosen & Mahasiswa (akun login dibuat otomatis oleh backend) =====

export type Lecturer = {
  id: number;
  lecturer_number: string;
  name: string;
  study_program_id: number;
  academic_position: string;
  employment_status: string;
  education_degree?: string | null;
  gender: string;
  phone?: string | null;
  address?: string | null;
  user?: { id?: number; email: string } | null;
  user_email?: string | null;
  study_program?: { id: number; code: string; name: string } | null;
};

export type Student = {
  id: number;
  student_number: string;
  name: string;
  study_program_id: number;
  entry_year: number | string;
  academic_status_id?: number | null;
  academic_status?: AcademicStatus | null;
  gender: string;
  place_of_birth?: string | null;
  date_of_birth?: string | null;
  religion?: string | null;
  address?: string | null;
  phone?: string | null;
  user?: { id?: number; email: string } | null;
  user_email?: string | null;
  study_program?: { id: number; code: string; name: string } | null;
};

/** Ref ringkas untuk relasi tier (dipakai label lookup). */
export type MasterRecordRef = {
  id: number;
  [key: string]: unknown;
};
