<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreStudentRequest;
use App\Http\Requests\Api\V1\UpdateStudentRequest;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\Student;
use App\Models\User;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD mahasiswa (API_SPECIFICATION §3.3). Soft delete.
 * Filter: ?search (name/student_number), ?study_program_id, ?entry_year,
 * ?gender, ?academic_status_id.
 *
 * PENAUTAN AKUN (store saja — tidak re-link saat update):
 * skema DB membuat user_id wajib (NOT NULL), jadi setiap mahasiswa baru
 * selalu dibuat satu akun aktif ber-role `mahasiswa` dalam satu
 * DB::transaction:
 * - user_email opsional dari request; bila kosong, akun otomatis dibuat
 *   dengan email determinis '{NIM}@students.simora.test';
 * - user_name/user_password opsional (default nama mahasiswa / 'password');
 * - email sudah terpakai (termasuk user terhapus lunak) → ditolak 422
 *   "Email sudah digunakan" (tidak menautkan diam-diam).
 */
class StudentController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = Student::query()
            ->with(['user:id,name,email,is_active', 'studyProgram:id,code,name,degree', 'academicStatus:id,code,name,color']);

        $this->applySearch($query, $request->query('search'), ['name', 'student_number']);
        if ($studyProgramId = $request->query('study_program_id')) {
            $query->where('study_program_id', (int) $studyProgramId);
        }
        if ($entryYear = $request->query('entry_year')) {
            $query->where('entry_year', (string) $entryYear);
        }
        if ($gender = $request->query('gender')) {
            $query->where('gender', $gender);
        }
        if ($academicStatusId = $request->query('academic_status_id')) {
            $query->where('academic_status_id', (int) $academicStatusId);
        }
        $this->applySorting($query, $request, ['name', 'student_number', 'entry_year', 'study_program_id', 'created_at'], 'student_number');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, StudentResource::class, 'Daftar mahasiswa berhasil dimuat.');
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // user_id wajib pada skema: bila admin tidak memberi user_email,
        // buat akun otomatis dengan email determinis dari NIM.
        $userEmail = $request->input('user_email')
            ?? strtolower($validated['student_number']).'@students.simora.test';

        // Email sudah dipakai user (termasuk yang terhapus lunak) → tolak eksplisit.
        if ($this->userEmailTaken($userEmail)) {
            return $this->respondError('Email sudah digunakan.', 422);
        }

        [$createdUser, $student] = DB::transaction(function () use ($validated, $userEmail, $request) {
            $user = $this->createLinkedUser(
                (string) $userEmail,
                $request->input('user_name'),
                $request->input('user_password'),
                $validated['name'],
                'mahasiswa'
            );

            $student = Student::create($validated + ['user_id' => $user->getAuthIdentifier()]);

            return [$user, $student];
        });

        SimpleAuditLogger::log('student_created', $student, [
            'attributes' => [
                'student_number' => $student->student_number,
                'name' => $student->name,
                'study_program_id' => $student->study_program_id,
            ],
            // Identitas akun yang dibuat ikut tercatat di audit log.
            'created_user_id' => $createdUser?->getAuthIdentifier(),
            'user_email' => $userEmail,
        ]);

        $student->loadMissing(['user', 'studyProgram', 'academicStatus']);

        return $this->respondSingle(
            StudentResource::make($student)->toArray($request),
            'Mahasiswa berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Student $student): JsonResponse
    {
        $student->loadMissing(['user', 'studyProgram', 'academicStatus']);

        return $this->respondSingle(
            StudentResource::make($student)->toArray($request),
            'Detail mahasiswa berhasil dimuat.'
        );
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student->update($request->validated());

        SimpleAuditLogger::log('student_updated', $student, [
            'changes' => $student->getChanges(),
        ]);

        $student->loadMissing(['user', 'studyProgram', 'academicStatus']);

        return $this->respondSingle(
            StudentResource::make($student)->toArray($request),
            'Mahasiswa berhasil diperbarui.'
        );
    }

    public function destroy(Student $student): JsonResponse
    {
        // Soft delete: baris mahasiswa masuk keranjang, akun user tetap dipertahankan.
        $student->delete();

        SimpleAuditLogger::log('student_deleted', $student, [
            'attributes' => ['student_number' => $student->student_number, 'name' => $student->name],
        ]);

        return $this->respondDeleted('Mahasiswa berhasil dihapus.');
    }

    /** True bila email dimiliki user manapun (termasuk yang terhapus lunak). */
    private function userEmailTaken(string $email): bool
    {
        return User::withTrashed()->where('email', $email)->exists();
    }

    /**
     * Buat user aktif baru untuk ditautkan ke mahasiswa/dosen.
     * Password default 'password' bila tidak disediakan.
     */
    private function createLinkedUser(string $email, ?string $name, ?string $password, string $fallbackName, string $role): User
    {
        $user = User::create([
            'name' => $name !== null && $name !== '' ? $name : $fallbackName,
            'email' => $email,
            'password' => $password !== null && $password !== '' ? $password : 'password',
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
