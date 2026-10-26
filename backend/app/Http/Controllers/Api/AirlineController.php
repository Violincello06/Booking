<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AirlineResource;
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

    public function show(int $id): JsonResponse
    {
        $airline = $this->airlineService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail maskapai berhasil diambil',
            'data' => new AirlineResource($airline),
        ], 200);
    }
}
