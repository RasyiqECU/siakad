<?php

namespace Database\Seeders;

use App\Enums\AcademicPosition;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\RoomType;
use App\Models\AcademicStatus;
use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\CoursePrerequisite;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\StudyProgram;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed data demo MASTER DATA (DATABASE_DESIGN §7 — hanya data master,
 * tanpa course_classes/KRS/presensi/nilai/tagihan milik Phase 4).
 *
 * Idempotent: firstOrCreate pada kunci unik; email demo yang sudah ada
 * (dosen@simora.test, mahasiswa@simora.test) ditautkan — tidak dibuat ulang.
 * Dilewati pada environment production (pola RolesAndUsersSeeder).
 */
class MasterDataDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Seeder demo master data dilewati (APP_ENV = production).');

            return;
        }

        $faculties = $this->seedFaculties();
        $departments = $this->seedDepartments($faculties);
        $studyPrograms = $this->seedStudyPrograms($departments);
        $years = $this->seedAcademicYears();
        $semesters = $this->seedSemesters($years);
        $statuses = $this->seedAcademicStatuses();
        $buildings = $this->seedBuildings();
        $classRooms = $this->seedClassRooms($buildings);
        $lecturers = $this->seedLecturers($studyPrograms);
        $students = $this->seedStudents($studyPrograms, $statuses);
        $courses = $this->seedCourses($studyPrograms);
        $this->seedCurriculums($studyPrograms, $years, $courses);

        $this->command?->info(sprintf(
            'MasterDataDemoSeeder: %d fakultas, %d jurusan, %d prodi, %d tahun akademik, %d semester, %d status, %d gedung, %d ruang, %d dosen, %d mahasiswa, %d mata kuliah, kurikulum+MK selesai.',
            $faculties->count(), $departments->count(), $studyPrograms->count(),
            $years->count(), $semesters->count(), $statuses->count(),
            2, $classRooms->count(), $lecturers->count(), $students->count(), $courses->count(),
        ));
    }

    // ------------------------------------------------------------------
    // Fakultas 2, Jurusan 4
    // ------------------------------------------------------------------

    private function seedFaculties(): iterable
    {
        $rows = [
            ['code' => 'FT', 'name' => 'Fakultas Teknik', 'description' => 'Fakultas teknik dan informatika.'],
            ['code' => 'FPMIPA', 'name' => 'Fakultas Pendidikan Matematika dan Ilmu Pengetahuan', 'description' => 'Fakultas pendidikan sains dan matematika.'],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $models->push(Faculty::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'description' => $row['description']],
            ));
        }

        return $models->keyBy('code');
    }

    private function seedDepartments(iterable $faculties): iterable
    {
        /** @var Faculty $ft */
        $ft = $faculties->get('FT');
        /** @var Faculty $fpmipa */
        $fpmipa = $faculties->get('FPMIPA');

        $rows = [
            ['code' => 'JTI', 'name' => 'Jurusan Teknologi Informasi', 'faculty_id' => $ft->id],
            ['code' => 'JTM', 'name' => 'Jurusan Teknik Mesin', 'faculty_id' => $ft->id],
            ['code' => 'JPMAT', 'name' => 'Jurusan Pendidikan Matematika', 'faculty_id' => $fpmipa->id],
            ['code' => 'JPBIO', 'name' => 'Jurusan Pendidikan Biologi', 'faculty_id' => $fpmipa->id],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $models->push(Department::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'faculty_id' => $row['faculty_id']],
            ));
        }

        return $models->keyBy('code');
    }

    // ------------------------------------------------------------------
    // Program studi 6 (campuran S1/S2/S3)
    // ------------------------------------------------------------------

    private function seedStudyPrograms(iterable $departments): iterable
    {
        $rows = [
            ['code' => 'TI-S1', 'name' => 'S1 Teknik Informatika', 'degree' => 'S1', 'department' => 'JTI'],
            ['code' => 'TI-S2', 'name' => 'S2 Magister Teknik Informatika', 'degree' => 'S2', 'department' => 'JTI'],
            ['code' => 'TM-S1', 'name' => 'S1 Teknik Mesin', 'degree' => 'S1', 'department' => 'JTM'],
            ['code' => 'PMAT-S1', 'name' => 'S1 Pendidikan Matematika', 'degree' => 'S1', 'department' => 'JPMAT'],
            ['code' => 'PMAT-S3', 'name' => 'S3 Pendidikan Matematika', 'degree' => 'S3', 'department' => 'JPMAT'],
            ['code' => 'PBIO-S1', 'name' => 'S1 Pendidikan Biologi', 'degree' => 'S1', 'department' => 'JPBIO'],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $models->put($row['code'], StudyProgram::firstOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'degree' => $row['degree'],
                    'department_id' => $departments->get($row['department'])->id,
                    'is_active' => true,
                ],
            ));
        }

        return $models;
    }

    // ------------------------------------------------------------------
    // Tahun akademik 2 + semester (persis satu aktif di masing-masing tabel)
    // ------------------------------------------------------------------

    private function seedAcademicYears(): iterable
    {
        $inactive = AcademicYear::firstOrCreate(['year' => '2025/2026'], ['is_active' => false]);
        $active = AcademicYear::firstOrCreate(['year' => '2026/2027'], ['is_active' => true]);

        // Pastikan tepat satu tahun aktif (aturan servis §5).
        AcademicYear::query()->whereKeyNot($active->id)->update(['is_active' => false]);
        if (! $active->is_active) {
            $active->forceFill(['is_active' => true])->save();
        }

        return collect(['2025/2026' => $inactive, '2026/2027' => $active]);
    }

    private function seedSemesters(iterable $years): iterable
    {
        $y2025 = $years->get('2025/2026');
        $y2026 = $years->get('2026/2027');

        $rows = [
            ['academic_year_id' => $y2025->id, 'name' => 'ganjil', 'start_date' => '2025-09-01', 'end_date' => '2026-01-15', 'krs_start' => null, 'krs_end' => null, 'is_active' => false],
            ['academic_year_id' => $y2025->id, 'name' => 'genap', 'start_date' => '2026-02-01', 'end_date' => '2026-06-15', 'krs_start' => null, 'krs_end' => null, 'is_active' => false],
            // Semester ganjil 2026/2027 aktif — sel KRS membungkus hari ini (2026-11).
            ['academic_year_id' => $y2026->id, 'name' => 'ganjil', 'start_date' => '2026-09-01', 'end_date' => '2027-01-15', 'krs_start' => '2026-10-01 00:00:00', 'krs_end' => '2026-11-30 23:59:59', 'is_active' => true],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $models->push(Semester::firstOrCreate(
                ['academic_year_id' => $row['academic_year_id'], 'name' => $row['name']],
                [
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                    'krs_start' => $row['krs_start'],
                    'krs_end' => $row['krs_end'],
                    'is_active' => false,
                ],
            ));
        }

        // Tepat satu semester aktif: ganjil 2026/2027.
        $active = $models->last();
        Semester::query()->whereKeyNot($active->id)->update(['is_active' => false]);
        if (! $active->is_active) {
            $active->forceFill(['is_active' => true])->save();
        }

        return $models;
    }

    // ------------------------------------------------------------------
    // Status akademik 6
    // ------------------------------------------------------------------

    private function seedAcademicStatuses(): iterable
    {
        $rows = [
            ['code' => 'BARU', 'name' => 'Baru', 'color' => '#38BDF8'],
            ['code' => 'AKTIF', 'name' => 'Aktif', 'color' => '#22C55E'],
            ['code' => 'CUTI', 'name' => 'Cuti', 'color' => '#F59E0B'],
            ['code' => 'LULUS', 'name' => 'Lulus', 'color' => '#6366F1'],
            ['code' => 'KELUAR', 'name' => 'Keluar', 'color' => '#6B7280'],
            ['code' => 'DO', 'name' => 'Drop Out', 'color' => '#EF4444'],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $models->put($row['code'], AcademicStatus::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'color' => $row['color']],
            ));
        }

        return $models;
    }

    // ------------------------------------------------------------------
    // Gedung 2 + ruang kelas 6
    // ------------------------------------------------------------------

    private function seedBuildings(): iterable
    {
        $rows = [
            ['code' => 'GKU', 'name' => 'Gedung Kuliah Umum'],
            ['code' => 'GDL', 'name' => 'Gedung Laboratorium'],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $models->put($row['code'], Building::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name']],
            ));
        }

        return $models;
    }

    private function seedClassRooms(iterable $buildings): iterable
    {
        $gku = $buildings->get('GKU');
        $gdl = $buildings->get('GDL');

        $rows = [
            ['code' => 'GKU-A', 'name' => 'Ruang Teori GKU A', 'capacity' => 40, 'room_type' => RoomType::Teori, 'building_id' => $gku->id],
            ['code' => 'GKU-B', 'name' => 'Ruang Teori GKU B', 'capacity' => 40, 'room_type' => RoomType::Teori, 'building_id' => $gku->id],
            ['code' => 'GKU-S', 'name' => 'Ruang Seminar GKU', 'capacity' => 25, 'room_type' => RoomType::Seminar, 'building_id' => $gku->id],
            ['code' => 'GDL-KOMP1', 'name' => 'Lab Komputasi 1', 'capacity' => 30, 'room_type' => RoomType::Laboratorium, 'building_id' => $gdl->id],
            ['code' => 'GDL-KOMP2', 'name' => 'Lab Komputasi 2', 'capacity' => 30, 'room_type' => RoomType::Laboratorium, 'building_id' => $gdl->id],
            ['code' => 'GDL-MESIN', 'name' => 'Lab Teknik Mesin', 'capacity' => 20, 'room_type' => RoomType::Laboratorium, 'building_id' => $gdl->id],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $models->push(ClassRoom::firstOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'capacity' => $row['capacity'],
                    'room_type' => $row['room_type'],
                    'building_id' => $row['building_id'],
                ],
            ));
        }

        return $models;
    }

    // ------------------------------------------------------------------
    // Dosen 8 (dengan akun linked user, password 'password')
    // ------------------------------------------------------------------

    private function seedLecturers(iterable $studyPrograms): iterable
    {
        $rows = [
            // Dosen #1 menautkan akun demo dosen@simora.test yang sudah ada.
            ['email' => 'dosen@simora.test', 'name' => 'Dr. Budi Santoso, M.Kom', 'lecturer_number' => '0012118701',
                'academic_position' => AcademicPosition::LektorKepala, 'employment_status' => EmploymentStatus::Tetap,
                'education_degree' => 'S3', 'gender' => Gender::L, 'study_program' => 'TI-S1'],
            ['email' => 'dosen1@simora.test', 'name' => 'Prof. Ani Wulandari, Dr.', 'lecturer_number' => '0020018502',
                'academic_position' => AcademicPosition::GuruBesar, 'employment_status' => EmploymentStatus::Tetap,
                'education_degree' => 'S3', 'gender' => Gender::P, 'study_program' => 'TI-S1'],
            ['email' => 'dosen2@simora.test', 'name' => 'Retno Palupi, M.T.', 'lecturer_number' => '0031008703',
                'academic_position' => AcademicPosition::AsistenAhli, 'employment_status' => EmploymentStatus::Tetap,
                'education_degree' => 'S2', 'gender' => Gender::P, 'study_program' => 'TI-S1'],
            ['email' => 'dosen3@simora.test', 'name' => 'Hendra Gunawan, S.T., M.Kom.', 'lecturer_number' => '0041008904',
                'academic_position' => AcademicPosition::Lektor, 'employment_status' => EmploymentStatus::Tetap,
                'education_degree' => 'S2', 'gender' => Gender::L, 'study_program' => 'TI-S1'],
            ['email' => 'dosen4@simora.test', 'name' => 'Agus Setiawan, S.Pd., M.Pd.', 'lecturer_number' => '0051018605',
                'academic_position' => AcademicPosition::Lektor, 'employment_status' => EmploymentStatus::NonTetap,
                'education_degree' => 'S2', 'gender' => Gender::L, 'study_program' => 'PMAT-S1'],
            ['email' => 'dosen5@simora.test', 'name' => 'Dewi Kartika, M.Sc.', 'lecturer_number' => '0061018906',
                'academic_position' => AcademicPosition::TenagaPengajar, 'employment_status' => EmploymentStatus::NonTetap,
                'education_degree' => 'S2', 'gender' => Gender::P, 'study_program' => 'PMAT-S1'],
            ['email' => 'dosen6@simora.test', 'name' => 'Rizky Pratama, S.T.', 'lecturer_number' => '0071008707',
                'academic_position' => AcademicPosition::AsistenAhli, 'employment_status' => EmploymentStatus::Tetap,
                'education_degree' => 'S2', 'gender' => Gender::L, 'study_program' => 'TM-S1'],
            ['email' => 'dosen7@simora.test', 'name' => 'Maria Fransiska, M.Kes.', 'lecturer_number' => '0081018808',
                'academic_position' => AcademicPosition::TenagaPengajar, 'employment_status' => EmploymentStatus::Tetap,
                'education_degree' => 'S2', 'gender' => Gender::P, 'study_program' => 'PBIO-S1'],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                ['name' => $row['name'], 'password' => self::DEMO_PASSWORD, 'is_active' => true],
            );
            if (! $user->hasRole('dosen')) {
                $user->assignRole('dosen');
            }

            $models->push(Lecturer::firstOrCreate(
                ['lecturer_number' => $row['lecturer_number']],
                [
                    'user_id' => $user->id,
                    'study_program_id' => $studyPrograms->get($row['study_program'])->id,
                    'name' => $row['name'],
                    'academic_position' => $row['academic_position'],
                    'employment_status' => $row['employment_status'],
                    'education_degree' => $row['education_degree'],
                    'gender' => $row['gender'],
                    'phone' => '0812'.str_pad((string) $user->id, 8, '3', STR_PAD_LEFT),
                    'address' => 'Jl. Pendidikan No. '.($user->id + 10),
                    'photo' => null,
                ],
            ));
        }

        return $models;
    }

    // ------------------------------------------------------------------
    // Mahasiswa 20 (angkatan 2023–2026, akun linked user 'password')
    // ------------------------------------------------------------------

    private function seedStudents(iterable $studyPrograms, iterable $statuses): iterable
    {
        // Mahasiswa #1 menautkan akun demo mahasiswa@simora.test yang sudah ada.
        $people = [
            ['email' => 'mahasiswa@simora.test', 'name' => 'Andi Mahasiswa', 'gender' => Gender::L, 'year' => '2023'],
            ['email' => 'mahasiswa1@simora.test', 'name' => 'Bunga Lestari', 'gender' => Gender::P, 'year' => '2023'],
            ['email' => 'mahasiswa2@simora.test', 'name' => 'Citra Ayu', 'gender' => Gender::P, 'year' => '2023'],
            ['email' => 'mahasiswa3@simora.test', 'name' => 'Dimas Prasetyo', 'gender' => Gender::L, 'year' => '2023'],
            ['email' => 'mahasiswa4@simora.test', 'name' => 'Eka Wijaya', 'gender' => Gender::L, 'year' => '2023'],
            ['email' => 'mahasiswa5@simora.test', 'name' => 'Fitri Handayani', 'gender' => Gender::P, 'year' => '2023'],
            ['email' => 'mahasiswa6@simora.test', 'name' => 'Gilang Saputra', 'gender' => Gender::L, 'year' => '2024'],
            ['email' => 'mahasiswa7@simora.test', 'name' => 'Hana Ramadhani', 'gender' => Gender::P, 'year' => '2024'],
            ['email' => 'mahasiswa8@simora.test', 'name' => 'Iqbal Maulana', 'gender' => Gender::L, 'year' => '2024'],
            ['email' => 'mahasiswa9@simora.test', 'name' => 'Joko Susilo', 'gender' => Gender::L, 'year' => '2024'],
            ['email' => 'mahasiswa10@simora.test', 'name' => 'Kartika Sari', 'gender' => Gender::P, 'year' => '2024'],
            ['email' => 'mahasiswa11@simora.test', 'name' => 'Lukman Hakim', 'gender' => Gender::L, 'year' => '2025'],
            ['email' => 'mahasiswa12@simora.test', 'name' => 'Maya Anggraini', 'gender' => Gender::P, 'year' => '2025'],
            ['email' => 'mahasiswa13@simora.test', 'name' => 'Nanda Pratama', 'gender' => Gender::L, 'year' => '2025'],
            ['email' => 'mahasiswa14@simora.test', 'name' => 'Oktaviani Putri', 'gender' => Gender::P, 'year' => '2025'],
            ['email' => 'mahasiswa15@simora.test', 'name' => 'Putra Nugroho', 'gender' => Gender::L, 'year' => '2025'],
            ['email' => 'mahasiswa16@simora.test', 'name' => 'Qori Amelia', 'gender' => Gender::P, 'year' => '2026'],
            ['email' => 'mahasiswa17@simora.test', 'name' => 'Raka Aditya', 'gender' => Gender::L, 'year' => '2026'],
            ['email' => 'mahasiswa18@simora.test', 'name' => 'Shinta Maharani', 'gender' => Gender::P, 'year' => '2026'],
            ['email' => 'mahasiswa19@simora.test', 'name' => 'Tomi Hartanto', 'gender' => Gender::L, 'year' => '2026'],
        ];

        $prodiByEmail = [
            'mahasiswa@simora.test' => 'TI-S1', 'mahasiswa1@simora.test' => 'TI-S1', 'mahasiswa2@simora.test' => 'TI-S1',
            'mahasiswa3@simora.test' => 'TI-S1', 'mahasiswa4@simora.test' => 'TI-S1', 'mahasiswa5@simora.test' => 'TI-S1',
            'mahasiswa6@simora.test' => 'TI-S1', 'mahasiswa7@simora.test' => 'TI-S1', 'mahasiswa8@simora.test' => 'TI-S1',
            'mahasiswa9@simora.test' => 'TI-S1', 'mahasiswa10@simora.test' => 'TI-S1',
            'mahasiswa11@simora.test' => 'PMAT-S1', 'mahasiswa12@simora.test' => 'PMAT-S1',
            'mahasiswa13@simora.test' => 'PMAT-S1', 'mahasiswa14@simora.test' => 'PMAT-S1',
            'mahasiswa15@simora.test' => 'TM-S1', 'mahasiswa16@simora.test' => 'TM-S1',
            'mahasiswa17@simora.test' => 'PBIO-S1', 'mahasiswa18@simora.test' => 'PBIO-S1',
            'mahasiswa19@simora.test' => 'TI-S1',
        ];

        // Statuses: mayoritas Aktif + 1 Cuti (index 6) + 1 Lulus (index 11).
        $statusByEmail = fn (string $email): string => match ($email) {
            'mahasiswa6@simora.test' => 'CUTI',
            'mahasiswa11@simora.test' => 'LULUS',
            default => 'AKTIF',
        };

        $models = collect();
        foreach ($people as $index => $person) {
            $user = User::firstOrCreate(
                ['email' => $person['email']],
                ['name' => $person['name'], 'password' => self::DEMO_PASSWORD, 'is_active' => true],
            );
            if (! $user->hasRole('mahasiswa')) {
                $user->assignRole('mahasiswa');
            }

            $studentNumber = substr($person['year'], 2, 2).str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $models->push(Student::firstOrCreate(
                ['student_number' => $studentNumber],
                [
                    'user_id' => $user->id,
                    'study_program_id' => $studyPrograms->get($prodiByEmail[$person['email']])->id,
                    'name' => $person['name'],
                    'entry_year' => $person['year'],
                    'academic_status_id' => $statuses->get($statusByEmail($person['email']))->id,
                    'gender' => $person['gender'],
                    'place_of_birth' => 'Bandung',
                    'date_of_birth' => '200'.(4 + ($index % 6)).'-0'.(1 + ($index % 9)).'-1'.($index % 9),
                    'religion' => $index % 2 === 0 ? 'Islam' : 'Kristen',
                    'address' => 'Jl. Merdeka No. '.($index + 1),
                    'phone' => '0812'.str_pad((string) ($user->id + 100), 8, '9', STR_PAD_LEFT),
                    'photo' => null,
                    'father_name' => 'Ayah '.$person['name'],
                    'mother_name' => 'Ibu '.$person['name'],
                    'parent_phone' => '0821'.str_pad((string) ($user->id + 200), 8, '8', STR_PAD_LEFT),
                    'parent_address' => 'Jl. Merdeka No. '.($index + 1),
                    'guardian_name' => null,
                    'guardian_phone' => null,
                    'notes' => null,
                ],
            ));
        }

        return $models;
    }

    // ------------------------------------------------------------------
    // Mata kuliah 10
    // ------------------------------------------------------------------

    private function seedCourses(iterable $studyPrograms): iterable
    {
        $rows = [
            // Prodi TI-S1 (kode TI*)
            ['code' => 'TIF-101', 'name' => 'Matematika Dasar', 'sks' => 3, 'semester' => 1, 'course_type' => 'wajib', 'study_program' => 'TI-S1'],
            ['code' => 'TIF-102', 'name' => 'Pemrograman Dasar', 'sks' => 3, 'semester' => 1, 'course_type' => 'wajib', 'study_program' => 'TI-S1'],
            ['code' => 'TIF-201', 'name' => 'Struktur Data', 'sks' => 3, 'semester' => 3, 'course_type' => 'wajib', 'study_program' => 'TI-S1'],
            ['code' => 'TIF-202', 'name' => 'Basis Data', 'sks' => 3, 'semester' => 3, 'course_type' => 'wajib', 'study_program' => 'TI-S1'],
            ['code' => 'TIF-301', 'name' => 'Jaringan Komputer', 'sks' => 3, 'semester' => 5, 'course_type' => 'wajib', 'study_program' => 'TI-S1'],
            ['code' => 'TIF-302', 'name' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'semester' => 5, 'course_type' => 'wajib', 'study_program' => 'TI-S1'],
            ['code' => 'TIF-401', 'name' => 'Kecerdasan Buatan', 'sks' => 3, 'semester' => 7, 'course_type' => 'wajib', 'study_program' => 'TI-S1'],
            ['code' => 'TIF-402', 'name' => 'Kapita Selekta Informatika', 'sks' => 2, 'semester' => 7, 'course_type' => 'pilihan', 'study_program' => 'TI-S1'],
            ['code' => 'TIF-403', 'name' => 'Proyek Akhir', 'sks' => 6, 'semester' => 8, 'course_type' => 'wajib', 'study_program' => 'TI-S1'],
            // Prodi lain (lintas prodi)
            ['code' => 'MAT-101', 'name' => 'Kalkulus Pendidikan', 'sks' => 4, 'semester' => 1, 'course_type' => 'wajib', 'study_program' => 'PMAT-S1'],
        ];

        $models = collect();
        foreach ($rows as $row) {
            $models->put($row['code'], Course::firstOrCreate(
                ['code' => $row['code']],
                [
                    'study_program_id' => $studyPrograms->get($row['study_program'])->id,
                    'name' => $row['name'],
                    'sks' => $row['sks'],
                    'semester' => $row['semester'],
                    'course_type' => $row['course_type'],
                    'description' => "Mata kuliah {$row['name']} (program studi {$row['study_program']}).",
                ],
            ));
        }

        return $models;
    }

    // ------------------------------------------------------------------
    // Kurikulum 2 (prodi S1 TI) + isi kurikulum + prasyarat
    // ------------------------------------------------------------------

    private function seedCurriculums(iterable $studyPrograms, iterable $years, iterable $courses): void
    {
        $tiS1 = $studyPrograms->get('TI-S1');
        $y2025 = $years->get('2025/2026');
        $y2026 = $years->get('2026/2027');

        $curriculum2025 = Curriculum::firstOrCreate(
            ['code' => 'KR-TI-2025'],
            [
                'study_program_id' => $tiS1->id,
                'academic_year_id' => $y2025->id,
                'name' => 'Kurikulum Teknik Informatika 2025',
                'total_sks' => 0,
                'is_active' => true,
                'description' => 'Kurikulum aktif program studi S1 Teknik Informatika (mulai 2025/2026).',
            ],
        );

        // Kurikulum baru 2026/2027 masih kosong (akan diisi berikutnya).
        Curriculum::firstOrCreate(
            ['code' => 'KR-TI-2026'],
            [
                'study_program_id' => $tiS1->id,
                'academic_year_id' => $y2026->id,
                'name' => 'Kurikulum Teknik Informatika 2026',
                'total_sks' => 0,
                'is_active' => false,
                'description' => 'Draft kurikulum baru 2026/2027.',
            ],
        );

        // Isi kurikulum 2025: seluruh mata kuliah pada semester yang sesuai.
        $tiCourses = $courses->filter(fn ($course) => $course->study_program_id === $tiS1->id);
        foreach ($tiCourses as $course) {
            CurriculumCourse::firstOrCreate(
                ['curriculum_id' => $curriculum2025->id, 'course_id' => $course->id],
                ['semester' => $course->semester, 'is_mandatory' => $course->course_type === 'wajib'],
            );
        }

        // Prasyarat: Matematika Dasar sebelum Struktur Data,
        // Pemrograman Dasar sebelum Basis Data.
        $this->attachPrerequisite($curriculum2025, $courses, 'TIF-201', 'TIF-101');
        $this->attachPrerequisite($curriculum2025, $courses, 'TIF-202', 'TIF-102');

        // total_sks = jumlah sks mata kuliah yang termuat (di-recompute).
        $totalSks = (int) CurriculumCourse::query()
            ->where('curriculum_id', $curriculum2025->id)
            ->join('courses', 'courses.id', '=', 'curriculum_courses.course_id')
            ->sum('courses.sks');
        $curriculum2025->forceFill(['total_sks' => $totalSks])->save();
    }

    /** Lampirkan prasyarat single course → id kursus pada kurikulum. */
    private function attachPrerequisite(Curriculum $curriculum, iterable $courses, string $courseCode, string $prerequisiteCode): void
    {
        $item = CurriculumCourse::query()
            ->where('curriculum_id', $curriculum->id)
            ->where('course_id', $courses->get($courseCode)->id)
            ->first();

        if ($item === null) {
            return;
        }

        CoursePrerequisite::firstOrCreate(
            ['curriculum_course_id' => $item->id, 'prerequisite_course_id' => $courses->get($prerequisiteCode)->id],
        );
    }
}
