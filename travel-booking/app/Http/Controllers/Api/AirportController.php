<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AirportResource;
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

    public function show(int $id): JsonResponse
    {
        $airport = $this->airportService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail bandara berhasil diambil',
            'data' => new AirportResource($airport),
        ], 200);
    }
}
