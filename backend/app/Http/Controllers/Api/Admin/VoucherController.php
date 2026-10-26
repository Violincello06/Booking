<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherService $voucherService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->voucherService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher berhasil diambil',
            'data' => VoucherResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    public function store(StoreVoucherRequest $request): JsonResponse
    {
        $voucher = $this->voucherService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dibuat',
            'data' => new VoucherResource($voucher),
        ], 201);
    }

    public function show(Voucher $voucher): JsonResponse
    {
        $loaded = $this->voucherService->find($voucher->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail voucher berhasil diambil',
            'data' => new VoucherResource($loaded),
        ], 200);
    }

    public function update(UpdateVoucherRequest $request, Voucher $voucher): JsonResponse
    {
        $updated = $this->voucherService->update($voucher, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diperbarui',
            'data' => new VoucherResource($updated),
        ], 200);
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        $this->voucherService->delete($voucher);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dihapus',
        ], 200);
    }
}
