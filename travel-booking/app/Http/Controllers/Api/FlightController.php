<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchFlightRequest;
use App\Http\Resources\FlightResource;
use App\Services\FlightService;
use Illuminate\Http\JsonResponse;

class FlightController extends Controller
{
    public function __construct(
        protected FlightService $flightService
    ) {}

    public function index(SearchFlightRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->flightService->search($request->validated(), $perPage);

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

    public function show(int $id): JsonResponse
    {
        $flight = $this->flightService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail penerbangan berhasil diambil',
            'data' => new FlightResource($flight),
        ], 200);
    }
}
