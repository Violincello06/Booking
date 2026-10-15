<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $roomService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $hotelId = $request->has('hotel_id') ? (int) $request->input('hotel_id') : null;
        $paginator = $this->roomService->paginate($perPage, $hotelId);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kamar berhasil diambil',
            'data' => RoomResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kamar berhasil dibuat',
            'data' => new RoomResource($room),
        ], 201);
    }

    public function show(Room $room): JsonResponse
    {
        $loaded = $this->roomService->find($room->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail kamar berhasil diambil',
            'data' => new RoomResource($loaded),
        ], 200);
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $updated = $this->roomService->update($room, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kamar berhasil diperbarui',
            'data' => new RoomResource($updated),
        ], 200);
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->roomService->delete($room);

        return response()->json([
            'success' => true,
            'message' => 'Kamar berhasil dihapus',
        ], 200);
    }
}
