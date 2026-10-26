<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Http\Resources\DestinationResource;
use App\Models\Destination;
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

    public function store(StoreDestinationRequest $request): JsonResponse
    {
        $destination = $this->destinationService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Destinasi berhasil dibuat',
            'data' => new DestinationResource($destination),
        ], 201);
    }

    public function show(Destination $destination): JsonResponse
    {
        $loaded = $this->destinationService->find($destination->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail destinasi berhasil diambil',
            'data' => new DestinationResource($loaded),
        ], 200);
    }

    public function update(UpdateDestinationRequest $request, Destination $destination): JsonResponse
    {
        $updated = $this->destinationService->update($destination, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Destinasi berhasil diperbarui',
            'data' => new DestinationResource($updated),
        ], 200);
    }

    public function destroy(Destination $destination): JsonResponse
    {
        $this->destinationService->delete($destination);

        return response()->json([
            'success' => true,
            'message' => 'Destinasi berhasil dihapus',
        ], 200);
    }
}
