<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAirportRequest;
use App\Http\Requests\UpdateAirportRequest;
use App\Http\Resources\AirportResource;
use App\Models\Airport;
use App\Services\AirportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function __construct(
        protected AirportService $airportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->airportService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar bandara berhasil diambil',
            'data' => AirportResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    public function store(StoreAirportRequest $request): JsonResponse
    {
        $airport = $this->airportService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bandara berhasil dibuat',
            'data' => new AirportResource($airport),
        ], 201);
    }

    public function show(Airport $airport): JsonResponse
    {
        $loaded = $this->airportService->find($airport->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail bandara berhasil diambil',
            'data' => new AirportResource($loaded),
        ], 200);
    }

    public function update(UpdateAirportRequest $request, Airport $airport): JsonResponse
    {
        $updated = $this->airportService->update($airport, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bandara berhasil diperbarui',
            'data' => new AirportResource($updated),
        ], 200);
    }

    public function destroy(Airport $airport): JsonResponse
    {
        $this->airportService->delete($airport);

        return response()->json([
            'success' => true,
            'message' => 'Bandara berhasil dihapus',
        ], 200);
    }
}
