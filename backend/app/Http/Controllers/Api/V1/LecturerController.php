<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreLecturerRequest;
use App\Http\Requests\Api\V1\UpdateLecturerRequest;
use App\Http\Resources\Api\V1\LecturerResource;
use App\Models\Lecturer;
use App\Models\User;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD dosen (API_SPECIFICATION §3.3). Soft delete.
 * Filter: ?search (name/lecturer_number), ?study_program_id,
 * ?academic_position, ?employment_status.
 *
 * PENAUTAN AKUN (store saja — tidak re-link saat update): skema DB membuat
 * user_id wajib, jadi setiap dosen baru selalu dibuat satu akun aktif
 * ber-role `dosen` dalam satu DB::transaction; user_email opsional — bila
 * kosong dipakai email determinis '{NIDN}@dosen.simora.test';
 * email terpakai (termasuk user terhapus lunak) → 422 "Email sudah digunakan".
 */
class LecturerController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = Lecturer::query()
            ->with(['user:id,name,email,is_active', 'studyProgram:id,code,name,degree']);

        $this->applySearch($query, $request->query('search'), ['name', 'lecturer_number']);
        if ($studyProgramId = $request->query('study_program_id')) {
            $query->where('study_program_id', (int) $studyProgramId);
        }
        if ($academicPosition = $request->query('academic_position')) {
            $query->where('academic_position', $academicPosition);
        }
        if ($employmentStatus = $request->query('employment_status')) {
            $query->where('employment_status', $employmentStatus);
        }
        $this->applySorting($query, $request, ['name', 'lecturer_number', 'study_program_id', 'created_at'], 'name');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, LecturerResource::class, 'Daftar dosen berhasil dimuat.');
    }

    public function store(StoreLecturerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // user_id wajib pada skema: bila admin tidak memberi user_email,
        // buat akun otomatis dengan email determinis dari NIDN.
        $userEmail = $request->input('user_email')
            ?? strtolower($validated['lecturer_number']).'@dosen.simora.test';

        // Email sudah dipakai user (termasuk yang terhapus lunak) → tolak eksplisit.
        if (User::withTrashed()->where('email', $userEmail)->exists()) {
            return $this->respondError('Email sudah digunakan.', 422);
        }

        [$createdUser, $lecturer] = DB::transaction(function () use ($validated, $userEmail, $request) {
            $user = User::create([
                'name' => $request->input('user_name') ?: $validated['name'],
                'email' => $userEmail,
                'password' => $request->input('user_password') ?: 'password',
                'is_active' => true,
            ]);
            $user->assignRole('dosen');

            $lecturer = Lecturer::create($validated + ['user_id' => $user->getAuthIdentifier()]);

            return [$user, $lecturer];
        });

        SimpleAuditLogger::log('lecturer_created', $lecturer, [
            'attributes' => [
                'lecturer_number' => $lecturer->lecturer_number,
                'name' => $lecturer->name,
            ],
            'created_user_id' => $createdUser?->getAuthIdentifier(),
            'user_email' => $userEmail,
        ]);

        $lecturer->loadMissing(['user', 'studyProgram']);

        return $this->respondSingle(
            LecturerResource::make($lecturer)->toArray($request),
            'Dosen berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Lecturer $lecturer): JsonResponse
    {
        $lecturer->loadMissing(['user', 'studyProgram']);

        return $this->respondSingle(
            LecturerResource::make($lecturer)->toArray($request),
            'Detail dosen berhasil dimuat.'
        );
    }

    public function update(UpdateLecturerRequest $request, Lecturer $lecturer): JsonResponse
    {
        $lecturer->update($request->validated());

        SimpleAuditLogger::log('lecturer_updated', $lecturer, [
            'changes' => $lecturer->getChanges(),
        ]);

        $lecturer->loadMissing(['user', 'studyProgram']);

        return $this->respondSingle(
            LecturerResource::make($lecturer)->toArray($request),
            'Dosen berhasil diperbarui.'
        );
    }

    public function destroy(Lecturer $lecturer): JsonResponse
    {
        // Soft delete dosen; akun user tetap dipertahankan.
        $lecturer->delete();

        SimpleAuditLogger::log('lecturer_deleted', $lecturer, [
            'attributes' => ['lecturer_number' => $lecturer->lecturer_number, 'name' => $lecturer->name],
        ]);

        return $this->respondDeleted('Dosen berhasil dihapus.');
    }
}
