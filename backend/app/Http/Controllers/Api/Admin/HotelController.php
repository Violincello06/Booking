<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use App\Services\HotelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function __construct(
        protected HotelService $hotelService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->hotelService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar hotel berhasil diambil',
            'data' => HotelResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    public function store(StoreHotelRequest $request): JsonResponse
    {
        $hotel = $this->hotelService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Hotel berhasil dibuat',
            'data' => new HotelResource($hotel),
        ], 201);
    }

    public function show(Hotel $hotel): JsonResponse
    {
        $loaded = $this->hotelService->find($hotel->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail hotel berhasil diambil',
            'data' => new HotelResource($loaded),
        ], 200);
    }

    public function update(UpdateHotelRequest $request, Hotel $hotel): JsonResponse
    {
        $updated = $this->hotelService->update($hotel, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Hotel berhasil diperbarui',
            'data' => new HotelResource($updated),
        ], 200);
    }

    public function destroy(Hotel $hotel): JsonResponse
    {
        $this->hotelService->delete($hotel);

        return response()->json([
            'success' => true,
            'message' => 'Hotel berhasil dihapus',
        ], 200);
    }
}
