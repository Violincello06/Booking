<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchHotelRequest;
use App\Http\Resources\HotelResource;
use App\Services\HotelService;
use Illuminate\Http\JsonResponse;

class HotelController extends Controller
{
    public function __construct(
        protected HotelService $hotelService
    ) {}

    public function index(SearchHotelRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->hotelService->search($request->validated(), $perPage);

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

    public function show(int $id): JsonResponse
    {
        $hotel = $this->hotelService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail hotel berhasil diambil',
            'data' => new HotelResource($hotel),
        ], 200);
    }
}
