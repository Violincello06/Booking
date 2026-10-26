<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAirlineRequest;
use App\Http\Requests\UpdateAirlineRequest;
use App\Http\Resources\AirlineResource;
use App\Models\Airline;
use App\Services\AirlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirlineController extends Controller
{
    public function __construct(
        protected AirlineService $airlineService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->airlineService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar maskapai berhasil diambil',
            'data' => AirlineResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    public function store(StoreAirlineRequest $request): JsonResponse
    {
        $airline = $this->airlineService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Maskapai berhasil dibuat',
            'data' => new AirlineResource($airline),
        ], 201);
    }

    public function show(Airline $airline): JsonResponse
    {
        $loaded = $this->airlineService->find($airline->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail maskapai berhasil diambil',
            'data' => new AirlineResource($loaded),
        ], 200);
    }

    public function update(UpdateAirlineRequest $request, Airline $airline): JsonResponse
    {
        $updated = $this->airlineService->update($airline, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Maskapai berhasil diperbarui',
            'data' => new AirlineResource($updated),
        ], 200);
    }

    public function destroy(Airline $airline): JsonResponse
    {
        $this->airlineService->delete($airline);

        return response()->json([
            'success' => true,
            'message' => 'Maskapai berhasil dihapus',
        ], 200);
    }
}
