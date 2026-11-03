<?php

namespace Tests\Feature;

use App\Models\AcademicStatus;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CoursePrerequisite;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Feature test Phase 3 — Master Data (API_SPECIFICATION §3.3):
 * CRUD fakultas + validasi, tahun akademik/semester satu-aktif, penautan
 * akun user pada mahasiswa, kurikulum (attach/detach + prasyarat), RBAC.
 */
class MasterDataTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function seedApp(): void
    {
        $this->seed();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@simora.test')->firstOrFail();
    }

    protected function dosenUser(): User
    {
        return User::where('email', 'dosen@simora.test')->firstOrFail();
    }

    protected function mahasiswaUser(): User
    {
        return User::where('email', 'mahasiswa@simora.test')->firstOrFail();
    }

    // ------------------------------------------------------------------
    // 1) CRUD fakultas + validasi (422)
    // ------------------------------------------------------------------

    public function test_superadmin_faculty_crud_flow_and_validation(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        // --- create
        $create = $this->postJson('/api/v1/faculties', [
            'code' => 'UJI',
            'name' => 'Fakultas Uji Coba',
            'description' => 'Fakultas untuk pengujian.',
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'UJI');

        $facultyId = $create->json('data.id');

        // --- validation: field wajib kosong
        $this->postJson('/api/v1/faculties', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);

        // --- duplikat code
        $duplicate = $this->postJson('/api/v1/faculties', ['code' => 'UJI', 'name' => 'Duplikat']);
        $duplicate->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertArrayHasKey('code', $duplicate->json('errors'));

        // --- update
        $this->patchJson("/api/v1/faculties/{$facultyId}", ['name' => 'Fakultas Uji Revisi'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Fakultas Uji Revisi')
            ->assertJsonPath('data.code', 'UJI');

        // --- show
        $this->getJson("/api/v1/faculties/{$facultyId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // --- delete (soft)
        $this->deleteJson("/api/v1/faculties/{$facultyId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('faculties', ['id' => $facultyId]);
    }

    // ------------------------------------------------------------------
    // 2) List 12 master data resource tampil terpaginasi
    // ------------------------------------------------------------------

    public function test_master_data_lists_are_paginated(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        $endpoints = [
            '/api/v1/faculties' => 2,
            '/api/v1/departments' => 4,
            '/api/v1/study-programs' => 6,
            '/api/v1/academic-years' => 2,
            '/api/v1/semesters' => 3,
            '/api/v1/academic-statuses' => 6,
            '/api/v1/buildings' => 2,
            '/api/v1/class-rooms' => 6,
            '/api/v1/lecturers' => 8,
            '/api/v1/students' => 20,
            '/api/v1/courses' => 10,
            '/api/v1/curriculums' => 2,
        ];

        foreach ($endpoints as $endpoint => $expectedTotal) {
            $response = $this->getJson($endpoint);

            $response->assertStatus(200)
                ->assertJsonPath('success', true);

            $this->assertSame($expectedTotal, $response->json('meta.total'), "Total pada {$endpoint}");
            $this->assertSame(1, $response->json('meta.current_page'), "current_page pada {$endpoint}");
        }
    }

    public function test_master_data_search_and_sort_filters(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        // search fungsional (code/name) — lowercase maupun uppercase (ilike/pgsql)
        $searchCase = $this->getJson('/api/v1/faculties?search=fakultas teknik');
        $searchCase->assertStatus(200);
        $this->assertSame(1, $searchCase->json('meta.total'));

        // sort_by whitelisted + sort_dir
        $sorted = $this->getJson('/api/v1/faculties?sort_by=name&sort_dir=desc');
        $sorted->assertStatus(200);
        $names = collect($sorted->json('data'))->pluck('name');
        $this->assertSame(['Fakultas Teknik', 'Fakultas Pendidikan Matematika dan Ilmu Pengetahuan'], $names->all());

        // sort_by di luar whitelist → jatuh ke default
        $this->getJson('/api/v1/faculties?sort_by=evil_column;drop')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // ------------------------------------------------------------------
    // 3) Tahun akademik: tepat satu aktif + aktif tidak boleh dihapus
    // ------------------------------------------------------------------

    public function test_academic_year_single_active_enforced_and_active_year_protected(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        $seededActive = AcademicYear::where('is_active', true)->firstOrFail();
        $this->assertSame('2026/2027', $seededActive->year);

        // Hapus tahun aktif → 422
        $this->deleteJson("/api/v1/academic-years/{$seededActive->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Buat tahun baru aktif → tahun lama otomatis nonaktif
        $create = $this->postJson('/api/v1/academic-years', ['year' => '2026/2028', 'is_active' => true]);
        $create->assertStatus(201)->assertJsonPath('data.is_active', true);

        $newId = $create->json('data.id');
        $this->assertSame(0, AcademicYear::where('is_active', true)->whereKeyNot($newId)->count());

        // update aktif via PATCH juga menjaga eksklusivitas
        $another = $this->postJson('/api/v1/academic-years', ['year' => '2027/2028']);
        $anotherId = $another->json('data.id');
        $this->patchJson("/api/v1/academic-years/{$anotherId}", ['is_active' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', true);
        $this->assertSame(0, AcademicYear::where('is_active', true)->whereKeyNot($anotherId)->count());

        // nonaktif boleh dihapus
        $this->deleteJson("/api/v1/academic-years/{$newId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ------------------------------------------------------------------
    // 4) Semester: activate eksklusif + aktif tidak boleh dihapus
    // ------------------------------------------------------------------

    public function test_semester_activate_is_exclusive_and_active_semester_cannot_be_deleted(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        $active = Semester::where('is_active', true)->firstOrFail();
        $inactive = Semester::where('is_active', false)->firstOrFail();
        $this->assertSame('ganjil', $active->name);

        // Hapus semester aktif → 422
        $this->deleteJson("/api/v1/semesters/{$active->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Aktifkan semester lain — eksklusivitas dijaga
        $activate = $this->postJson("/api/v1/semesters/{$inactive->id}/activate");
        $activate->assertStatus(200)->assertJsonPath('data.is_active', true);
        $this->assertSame(1, Semester::where('is_active', true)->pluck('id')->count());
        $this->assertSame((int) $inactive->id, (int) Semester::where('is_active', true)->firstOrFail()->id);        $active->refresh();
        $this->assertFalse($active->is_active);

        // Kini semester lama boleh dihapus, semester baru aktif terjaga
        $this->deleteJson("/api/v1/semesters/{$active->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ------------------------------------------------------------------
    // 5) Penautan akun user pada mahasiswa
    // ------------------------------------------------------------------

    public function test_student_store_creates_linked_user_and_rejects_taken_email(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        $program = \App\Models\StudyProgram::where('code', 'TI-S1')->firstOrFail();

        // --- email baru → user dibuat + ditautkan
        $email = 'mahasiswa.uji@simora.test';
        $create = $this->postJson('/api/v1/students', [
            'study_program_id' => $program->id,
            'student_number' => '99000001',
            'name' => 'Uji Mahasiswa',
            'entry_year' => '2026',
            'gender' => 'L',
            'user_email' => $email,
            'user_password' => 'password123',
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('success', true);

        $studentId = $create->json('data.id');
        $userId = $create->json('data.user.id');

        $this->assertNotNull($userId);
        $user = User::findOrFail($userId);
        $this->assertSame($email, $user->email);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('mahasiswa'));
        $student = Student::findOrFail($studentId);
        $this->assertSame($user->id, $student->user_id);

        // akun baru bisa login
        $login = $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'password123']);
        $login->assertStatus(200);

        // --- email sudah dipakai → 422 "Email sudah digunakan"
        $this->postJson('/api/v1/students', [
            'study_program_id' => $program->id,
            'student_number' => '99000002',
            'name' => 'Uji Mahasiswa Dua',
            'entry_year' => '2026',
            'gender' => 'P',
            'user_email' => $email,
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Email sudah digunakan.');

        // validasi entry_year tidak wajar → 422
        $this->postJson('/api/v1/students', [
            'study_program_id' => $program->id,
            'student_number' => '99000003',
            'name' => 'Uji Angkatan',
            'entry_year' => '1999',
            'gender' => 'L',
        ])->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // 6) Kurikulum: attach/detach MK + recompute total_sks + duplikat 422
    // ------------------------------------------------------------------

    public function test_curriculum_attach_detach_courses_recomputes_total_sks(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        $curriculum = Curriculum::where('code', 'KR-TI-2025')->firstOrFail();
        $initialSks = $curriculum->total_sks;
        $this->assertGreaterThan(0, $initialSks);

        $extra = Course::where('code', 'MAT-101')->firstOrFail();
        $this->assertNotSame($curriculum->study_program_id, $extra->study_program_id); // beda prodi

        // --- attach lintas prodi → 422
        $this->postJson("/api/v1/curriculums/{$curriculum->id}/courses", [
            'course_id' => $extra->id,
            'semester' => 4,
            'is_mandatory' => true,
        ])->assertStatus(422)
            ->assertJsonPath('success', false);

        // --- attach MK prodi sama yang belum termuat → 201, total_sks bertambah
        $add = Course::create([
            'study_program_id' => $curriculum->study_program_id,
            'code' => 'TIF-999',
            'name' => 'Mata Kuliah Uji',
            'sks' => 3,
            'semester' => 6,
            'course_type' => 'wajib',
        ]);

        $attach = $this->postJson("/api/v1/curriculums/{$curriculum->id}/courses", [
            'course_id' => $add->id,
            'semester' => 6,
        ]);
        $attach->assertStatus(201);
        $this->assertSame($initialSks + 3, $attach->json('data.total_sks'));

        $curriculum->refresh();
        $this->assertSame($initialSks + 3, $curriculum->total_sks);

        // --- duplikat attach → 422
        $this->postJson("/api/v1/curriculums/{$curriculum->id}/courses", [
            'course_id' => $add->id,
            'semester' => 6,
        ])->assertStatus(422)
            ->assertJsonPath('success', false);

        // --- detail show memuat courses + prerequisites
        $show = $this->getJson("/api/v1/curriculums/{$curriculum->id}");
        $show->assertStatus(200);
        $this->assertSame($curriculum->curriculumCourses()->count(), count($show->json('data.courses')));
        $this->assertSame(2, count($show->json('data.prerequisites')));

        // --- detach → total_sks kembali
        $detach = $this->deleteJson("/api/v1/curriculums/{$curriculum->id}/courses/{$add->id}");
        $detach->assertStatus(200);
        $this->assertSame($initialSks, $detach->json('data.total_sks'));

        // --- attach pada semester di luar 1..14 → 422
        $this->postJson("/api/v1/curriculums/{$curriculum->id}/courses", [
            'course_id' => $add->id,
            'semester' => 20,
        ])->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // 7) Prasyarat kurikulum: aturan pasangan + duplikat
    // ------------------------------------------------------------------

    public function test_curriculum_prerequisite_rules(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        $curriculum = Curriculum::where('code', 'KR-TI-2025')->firstOrFail();

        $itemStrukturData = CurriculumCourse::query()
            ->where('curriculum_id', $curriculum->id)
            ->whereHas('course', fn ($q) => $q->where('code', 'TIF-201'))
            ->firstOrFail();
        $itemBasisData = CurriculumCourse::query()
            ->where('curriculum_id', $curriculum->id)
            ->whereHas('course', fn ($q) => $q->where('code', 'TIF-202'))
            ->firstOrFail();
        $programanDasar = Course::where('code', 'TIF-102')->firstOrFail();
        $matematika = Course::where('code', 'TIF-101')->firstOrFail();

        // --- duplikat pair → 422 (seeder memasang TIF-202 ← TIF-102)
        $this->postJson("/api/v1/curriculums/{$curriculum->id}/prerequisites", [
            'curriculum_course_id' => $itemBasisData->id,
            'prerequisite_course_id' => $programanDasar->id,
        ])->assertStatus(422)
            ->assertJsonPath('success', false);

        // --- prasyarat diri sendiri → 422 (Basis Data ← Basis Data)
        $this->postJson("/api/v1/curriculums/{$curriculum->id}/prerequisites", [
            'curriculum_course_id' => $itemBasisData->id,
            'prerequisite_course_id' => $itemBasisData->course_id,
        ])->assertStatus(422);

        // --- duplikat pair seeder (TIF-201 ← TIF-101) → 422
        $matematika = Course::where('code', 'TIF-101')->firstOrFail();
        $this->postJson("/api/v1/curriculums/{$curriculum->id}/prerequisites", [
            'curriculum_course_id' => $itemStrukturData->id,
            'prerequisite_course_id' => $matematika->id,
        ])->assertStatus(422)
            ->assertJsonPath('success', false);

        // --- prasyarat di luar kurikulum → 422
        $luarKurikulum = Course::where('code', 'MAT-101')->firstOrFail();
        $this->postJson("/api/v1/curriculums/{$curriculum->id}/prerequisites", [
            'curriculum_course_id' => $itemStrukturData->id,
            'prerequisite_course_id' => $luarKurikulum->id,
        ])->assertStatus(422);

        // --- pasangan valid (TIF-302 ← TIF-201) → 201 lalu detach → 200
        $itemRpl = CurriculumCourse::query()
            ->where('curriculum_id', $curriculum->id)
            ->whereHas('course', fn ($q) => $q->where('code', 'TIF-302'))
            ->firstOrFail();

        $this->postJson("/api/v1/curriculums/{$curriculum->id}/prerequisites", [
            'curriculum_course_id' => $itemRpl->id,
            'prerequisite_course_id' => $itemStrukturData->course_id,
        ])->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertSame(3, CoursePrerequisite::query()
            ->whereHas('curriculumCourse', fn ($q) => $q->where('curriculum_id', $curriculum->id))
            ->count());

        $this->deleteJson("/api/v1/curriculums/{$curriculum->id}/prerequisites/{$itemStrukturData->course_id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSame(0, CoursePrerequisite::query()
            ->where('prerequisite_course_id', $itemStrukturData->course_id)
            ->whereHas('curriculumCourse', fn ($q) => $q->where('curriculum_id', $curriculum->id))
            ->count());

        // --- curriculum_course_id milik kurikulum lain → 422
        $other = Curriculum::where('code', 'KR-TI-2026')->firstOrFail();
        $this->postJson("/api/v1/curriculums/{$other->id}/prerequisites", [
            'curriculum_course_id' => $itemStrukturData->id,
            'prerequisite_course_id' => $matematika->id,
        ])->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // 8) RBAC: dosen hanya read, mahasiswa tidak punya akses master data
    // ------------------------------------------------------------------

    public function test_dosen_can_read_but_not_write_master_data(): void
    {
        $this->seedApp();
        $this->actingAs($this->dosenUser(), 'sanctum');

        // read diizinkan pada resource yang di-matriks
        $this->getJson('/api/v1/students')->assertStatus(200);
        $this->getJson('/api/v1/courses')->assertStatus(200);
        $this->getJson('/api/v1/curriculums')->assertStatus(200);

        // dosen tidak punya faculties.read (lihat PermissionsSeeder::roleMatrix)
        $this->getJson('/api/v1/faculties')->assertStatus(403);

        // write ditolak
        $this->postJson('/api/v1/faculties', ['code' => 'DOSEN', 'name' => 'Dosen Fakultas'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
        $this->postJson('/api/v1/students/', [
            'study_program_id' => 1,
            'student_number' => 'X1',
            'name' => 'X',
            'entry_year' => '2026',
            'gender' => 'L',
        ])->assertStatus(403);
        $this->postJson('/api/v1/curriculums/1/courses', ['course_id' => 1, 'semester' => 1])
            ->assertStatus(403);
    }

    public function test_mahasiswa_cannot_access_master_data_read_or_write(): void
    {
        $this->seedApp();
        $this->actingAs($this->mahasiswaUser(), 'sanctum');

        // mahasiswa tidak punya students.read → 403 pada list & store
        $this->getJson('/api/v1/students')->assertStatus(403);

        $this->postJson('/api/v1/students', [
            'study_program_id' => 1,
            'student_number' => 'X2',
            'name' => 'X',
            'entry_year' => '2026',
            'gender' => 'L',
        ])->assertStatus(403);

        $this->postJson('/api/v1/faculties', ['code' => 'MH', 'name' => 'Mahasiswa Fakultas'])
            ->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // 9) Dosen resource + akun linked user (email di resource)
    // ------------------------------------------------------------------

    public function test_lecturer_store_creates_linked_user_and_show_exposes_email(): void
    {
        $this->seedApp();
        $this->actingAs($this->superadmin(), 'sanctum');

        $program = \App\Models\StudyProgram::where('code', 'TI-S1')->firstOrFail();
        $email = 'dosen.uji@simora.test';

        $create = $this->postJson('/api/v1/lecturers', [
            'study_program_id' => $program->id,
            'lecturer_number' => '99990001',
            'name' => 'Dosen Uji, M.Kom.',
            'academic_position' => 'Lektor',
            'employment_status' => 'tetap',
            'education_degree' => 'S2',
            'gender' => 'L',
            'user_email' => $email,
        ]);
        $create->assertStatus(201);
        $lecturerId = $create->json('data.id');
        $this->assertSame($email, $create->json('data.user.email'));

        $this->postJson('/api/v1/lecturers', [
            'study_program_id' => $program->id,
            'lecturer_number' => '99990002',
            'name' => 'Dosen Uji Dua',
            'academic_position' => 'Lektor',
            'employment_status' => 'tetap',
            'gender' => 'P',
            'user_email' => $email,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Email sudah digunakan.');

        // show dosen menampilkan email user
        $show = $this->getJson("/api/v1/lecturers/{$lecturerId}");
        $show->assertStatus(200)->assertJsonPath('data.user.email', $email);

        // user baru dapat login
        $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'password'])
            ->assertStatus(200);

        // validasi hex color pada status akademik
        $this->actingAs($this->superadmin(), 'sanctum');
        $this->postJson('/api/v1/academic-statuses', [
            'code' => 'UJIHEX',
            'name' => 'Status Uji',
            'color' => '#ZZZZZZ',
        ])->assertStatus(422);

        $this->postJson('/api/v1/academic-statuses', [
            'code' => 'UJIHEX',
            'name' => 'Status Uji',
            'color' => '#AB12CD',
        ])->assertStatus(201);

        // mahasiswa dengan status valid tampil lengkap di detail
        $statusId = AcademicStatus::where('code', 'UJIHEX')->firstOrFail()->id;
        $createStudent = $this->postJson('/api/v1/students', [
            'study_program_id' => $program->id,
            'student_number' => '99000077',
            'name' => 'Mahasiswa Status Uji',
            'entry_year' => '2026',
            'gender' => 'L',
            'academic_status_id' => $statusId,
        ]);
        $createStudent->assertStatus(201);

        $showStudent = $this->getJson("/api/v1/students/{$createStudent->json('data.id')}");
        $showStudent->assertStatus(200)
            ->assertJsonPath('data.academic_status.code', 'UJIHEX')
            ->assertJsonPath('data.study_program.name', 'S1 Teknik Informatika')
            ->assertJsonPath('data.user.email', '99000077@students.simora.test');
    }
}
