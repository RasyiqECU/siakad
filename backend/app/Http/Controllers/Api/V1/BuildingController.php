<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreBuildingRequest;
use App\Http\Requests\Api\V1\UpdateBuildingRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Models\Building;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD data master gedung (API_SPECIFICATION §3.3).
 */
class BuildingController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = Building::query()->withCount('classRooms');

        $this->applySearch($query, $request->query('search'), ['code', 'name']);
        $this->applySorting($query, $request, ['code', 'name', 'created_at'], 'code');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, BuildingResource::class, 'Daftar gedung berhasil dimuat.');
    }

    public function store(StoreBuildingRequest $request): JsonResponse
    {
        $building = Building::create($request->validated());

        SimpleAuditLogger::log('building_created', $building, [
            'attributes' => ['code' => $building->code, 'name' => $building->name],
        ]);

        return $this->respondSingle(
            BuildingResource::make($building)->toArray($request),
            'Gedung berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Building $building): JsonResponse
    {
        $building->loadCount('classRooms');

        return $this->respondSingle(
            BuildingResource::make($building)->toArray($request),
            'Detail gedung berhasil dimuat.'
        );
    }

    public function update(UpdateBuildingRequest $request, Building $building): JsonResponse
    {
        $building->update($request->validated());

        SimpleAuditLogger::log('building_updated', $building, [
            'changes' => $building->getChanges(),
        ]);

        return $this->respondSingle(
            BuildingResource::make($building->loadCount('classRooms'))->toArray($request),
            'Gedung berhasil diperbarui.'
        );
    }

    public function destroy(Building $building): JsonResponse
    {
        $building->delete();

        SimpleAuditLogger::log('building_deleted', $building, [
            'attributes' => ['code' => $building->code, 'name' => $building->name],
        ]);

        return $this->respondDeleted('Gedung berhasil dihapus.');
    }
}
