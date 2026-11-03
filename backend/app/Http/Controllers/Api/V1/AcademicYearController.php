<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreAcademicYearRequest;
use App\Http\Requests\Api\V1\UpdateAcademicYearRequest;
use App\Http\Resources\Api\V1\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD data master tahun akademik (API_SPECIFICATION §3.3).
 * Aturan servis: HANYA SATU tahun berstatus is_active=true — dijaga
 * di dalam DB::transaction (mengaktifkan satu menonaktifkan lainnya).
 * Tahun akademik yang sedang aktif tidak boleh dihapus (422).
 */
class AcademicYearController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = AcademicYear::query();

        $this->applySearch($query, $request->query('search'), ['year']);
        if ($request->query('is_active') !== null) {
            $query->where('is_active', (bool) $request->query('is_active', '0'));
        }
        $this->applySorting($query, $request, ['year', 'is_active', 'created_at'], 'year', 'desc');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, AcademicYearResource::class, 'Daftar tahun akademik berhasil dimuat.');
    }

    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $activate = (bool) ($validated['is_active'] ?? false);

        $academicYear = DB::transaction(function () use ($validated, $activate) {
            $academicYear = AcademicYear::create([
                'year' => $validated['year'],
                'is_active' => false,
            ]);

            if ($activate) {
                $this->activateExclusive($academicYear);
            }

            return $academicYear;
        });

        SimpleAuditLogger::log('academic_year_created', $academicYear, [
            'attributes' => ['year' => $academicYear->year],
            'is_active' => $academicYear->is_active,
        ]);

        return $this->respondSingle(
            AcademicYearResource::make($academicYear->refresh())->toArray($request),
            'Tahun akademik berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, AcademicYear $academicYear): JsonResponse
    {
        $academicYear->load('semesters');

        return $this->respondSingle(
            AcademicYearResource::make($academicYear)->toArray($request),
            'Detail tahun akademik berhasil dimuat.'
        );
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $validated = $request->validated();

        $academicYear = DB::transaction(function () use ($academicYear, $validated) {
            // is_active tidak langsung di-update — lewat logika eksklusif.
            $wantActive = array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : null;
            unset($validated['is_active']);

            $academicYear->update($validated);

            if ($wantActive === true) {
                $this->activateExclusive($academicYear);
            } elseif ($wantActive === false) {
                $academicYear->forceFill(['is_active' => false])->save();
            }

            return $academicYear->refresh();
        });

        SimpleAuditLogger::log('academic_year_updated', $academicYear, [
            'changes' => $academicYear->getChanges(),
            'is_active' => $academicYear->is_active,
        ]);

        return $this->respondSingle(
            AcademicYearResource::make($academicYear)->toArray($request),
            'Tahun akademik berhasil diperbarui.'
        );
    }

    public function destroy(AcademicYear $academicYear): JsonResponse
    {
        if ($academicYear->is_active) {
            return $this->respondError('Tahun akademik yang aktif tidak dapat dihapus. Nonaktifkan minimal satu tahun terlebih dahulu.', 422);
        }

        $academicYear->delete();

        SimpleAuditLogger::log('academic_year_deleted', $academicYear, [
            'attributes' => ['year' => $academicYear->year],
        ]);

        return $this->respondDeleted('Tahun akademik berhasil dihapus.');
    }

    /**
     * Aktifkan satu tahun dan nonaktifkan tahun lainnya (transaksi eksklusif).
     */
    private function activateExclusive(AcademicYear $academicYear): void
    {
        AcademicYear::query()->whereKeyNot($academicYear->getKey())->update(['is_active' => false]);
        $academicYear->forceFill(['is_active' => true])->save();
    }
}
