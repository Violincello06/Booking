<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlightRequest;
use App\Http\Requests\UpdateFlightRequest;
use App\Http\Resources\FlightResource;
use App\Models\Flight;
use App\Services\FlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function __construct(
        protected FlightService $flightService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->flightService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar penerbangan berhasil diambil',
            'data' => FlightResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    public function store(StoreFlightRequest $request): JsonResponse
    {
        $flight = $this->flightService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Penerbangan berhasil dibuat',
            'data' => new FlightResource($flight),
        ], 201);
    }

    public function show(Flight $flight): JsonResponse
    {
        $loaded = $this->flightService->find($flight->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail penerbangan berhasil diambil',
            'data' => new FlightResource($loaded),
        ], 200);
    }

    public function update(UpdateFlightRequest $request, Flight $flight): JsonResponse
    {
        $updated = $this->flightService->update($flight, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Penerbangan berhasil diperbarui',
            'data' => new FlightResource($updated),
        ], 200);
    }

    public function destroy(Flight $flight): JsonResponse
    {
        $this->flightService->delete($flight);

        return response()->json([
            'success' => true,
            'message' => 'Penerbangan berhasil dihapus',
        ], 200);
    }
}
