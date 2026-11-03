<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreSemesterRequest;
use App\Http\Requests\Api\V1\UpdateSemesterRequest;
use App\Http\Resources\Api\V1\SemesterResource;
use App\Models\Semester;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD data master semester (ganjil/genap) + aksi activate (API_SPECIFICATION §3.3).
 * Aturan servis: hanya SATU semester aktif di seluruh tabel (transaksi).
 * Semester aktif tidak dapat dihapus (422). Permission activate: semesters.update.
 */
class SemesterController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = Semester::query()->with('academicYear:id,year,is_active');

        $this->applySearch($query, $request->query('search'), ['name']);
        if ($academicYearId = $request->query('academic_year_id')) {
            $query->where('academic_year_id', (int) $academicYearId);
        }
        if ($request->query('is_active') !== null) {
            $query->where('is_active', (bool) $request->query('is_active', '0'));
        }
        $this->applySorting($query, $request, ['name', 'start_date', 'academic_year_id', 'created_at'], 'academic_year_id');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, SemesterResource::class, 'Daftar semester berhasil dimuat.');
    }

    public function store(StoreSemesterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $semester = DB::transaction(function () use ($validated) {
            $semester = Semester::create($validated);

            if (isset($validated['is_active']) && $validated['is_active']) {
                $this->activateExclusive($semester);
            } else {
                $semester->forceFill(['is_active' => false])->save();
            }

            return $semester;
        });

        SimpleAuditLogger::log('semester_created', $semester, [
            'attributes' => [
                'academic_year_id' => $semester->academic_year_id,
                'name' => $semester->name,
            ],
            'is_active' => $semester->is_active,
        ]);

        return $this->respondSingle(
            SemesterResource::make($semester->loadMissing('academicYear'))->toArray($request),
            'Semester berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Semester $semester): JsonResponse
    {
        $semester->load('academicYear');

        return $this->respondSingle(
            SemesterResource::make($semester)->toArray($request),
            'Detail semester berhasil dimuat.'
        );
    }

    public function update(UpdateSemesterRequest $request, Semester $semester): JsonResponse
    {
        $validated = $request->validated();

        $semester = DB::transaction(function () use ($semester, $validated) {
            // is_active ditangani lewat logika eksklusif (unifikasi satu semester aktif).
            $wantActive = array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : null;
            unset($validated['is_active']);

            $semester->update($validated);

            if ($wantActive === true) {
                $this->activateExclusive($semester);
            } elseif ($wantActive === false) {
                $semester->forceFill(['is_active' => false])->save();
            }

            return $semester->refresh();
        });

        SimpleAuditLogger::log('semester_updated', $semester, [
            'changes' => $semester->getChanges(),
        ]);

        return $this->respondSingle(
            SemesterResource::make($semester->loadMissing('academicYear'))->toArray($request),
            'Semester berhasil diperbarui.'
        );
    }

    public function destroy(Semester $semester): JsonResponse
    {
        if ($semester->is_active) {
            return $this->respondError('Semester yang aktif tidak dapat dihapus. Aktifkan semester lain terlebih dahulu.', 422);
        }

        $semester->delete();

        SimpleAuditLogger::log('semester_deleted', $semester, [
            'attributes' => ['academic_year_id' => $semester->academic_year_id, 'name' => $semester->name],
        ]);

        return $this->respondDeleted('Semester berhasil dihapus.');
    }

    public function activate(Request $request, Semester $semester): JsonResponse
    {
        $this->activateExclusive($semester);

        SimpleAuditLogger::log('semester_activated', $semester->refresh(), ['is_active' => true]);

        return $this->respondSingle(
            SemesterResource::make($semester->refresh()->loadMissing('academicYear'))->toArray($request),
            'Semester berhasil diaktifkan.'
        );
    }

    /** Nonaktifkan semua semester lain, aktifkan satu ini (transaksi). */
    private function activateExclusive(Semester $semester): void
    {
        DB::transaction(function () use ($semester) {
            Semester::query()->whereKeyNot($semester->getKey())->update(['is_active' => false]);
            $semester->forceFill(['is_active' => true])->save();
        });
    }
}
