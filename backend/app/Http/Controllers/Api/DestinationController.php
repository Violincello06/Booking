<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DestinationResource;
use App\Services\DestinationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function __construct(
        protected DestinationService $destinationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->destinationService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data destinasi berhasil diambil',
            'data' => DestinationResource::collection($paginator->items()),
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
        $destination = $this->destinationService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail destinasi berhasil diambil',
            'data' => new DestinationResource($destination),
        ], 200);
    }
}
