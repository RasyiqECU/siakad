<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\HandlesMasterData;
use App\Http\Requests\Api\V1\StoreClassRoomRequest;
use App\Http\Requests\Api\V1\UpdateClassRoomRequest;
use App\Http\Resources\Api\V1\ClassRoomResource;
use App\Models\ClassRoom;
use App\Services\SimpleAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD data master ruang kelas (API_SPECIFICATION §3.3).
 * Filter: ?search (code/name), ?building_id, ?room_type.
 */
class ClassRoomController extends Controller
{
    use HandlesMasterData;

    public function index(Request $request): JsonResponse
    {
        $query = ClassRoom::query()->with('building:id,code,name');

        $this->applySearch($query, $request->query('search'), ['code', 'name']);
        if ($buildingId = $request->query('building_id')) {
            $query->where('building_id', (int) $buildingId);
        }
        if ($roomType = $request->query('room_type')) {
            $query->where('room_type', $roomType);
        }
        $this->applySorting($query, $request, ['code', 'name', 'capacity', 'building_id', 'created_at'], 'code');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($this->perPage($request))->appends($request->query());

        return $this->respondPaginated($paginator, ClassRoomResource::class, 'Daftar ruang kelas berhasil dimuat.');
    }

    public function store(StoreClassRoomRequest $request): JsonResponse
    {
        $classRoom = ClassRoom::create($request->validated());
        $classRoom->load('building:id,code,name');

        SimpleAuditLogger::log('class_room_created', $classRoom, [
            'attributes' => ['code' => $classRoom->code, 'name' => $classRoom->name],
        ]);

        return $this->respondSingle(
            ClassRoomResource::make($classRoom)->toArray($request),
            'Ruang kelas berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, ClassRoom $classRoom): JsonResponse
    {
        $classRoom->load('building:id,code,name');

        return $this->respondSingle(
            ClassRoomResource::make($classRoom)->toArray($request),
            'Detail ruang kelas berhasil dimuat.'
        );
    }

    public function update(UpdateClassRoomRequest $request, ClassRoom $classRoom): JsonResponse
    {
        $classRoom->update($request->validated());
        $classRoom->load('building:id,code,name');

        SimpleAuditLogger::log('class_room_updated', $classRoom, [
            'changes' => $classRoom->getChanges(),
        ]);

        return $this->respondSingle(
            ClassRoomResource::make($classRoom)->toArray($request),
            'Ruang kelas berhasil diperbarui.'
        );
    }

    public function destroy(ClassRoom $classRoom): JsonResponse
    {
        $classRoom->delete();

        SimpleAuditLogger::log('class_room_deleted', $classRoom, [
            'attributes' => ['code' => $classRoom->code, 'name' => $classRoom->name],
        ]);

        return $this->respondDeleted('Ruang kelas berhasil dihapus.');
    }
}
