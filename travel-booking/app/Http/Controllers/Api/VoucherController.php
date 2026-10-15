<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyVoucherRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\VoucherResource;
use App\Http\Resources\VoucherUsageResource;
use App\Services\VoucherService;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherService $voucherService
    ) {}

    /**
     * Get active vouchers that users can claim.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->voucherService->getActiveVouchers($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher promo aktif berhasil diambil',
            'data' => VoucherResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * Apply voucher to an active booking.
     */
    public function apply(ApplyVoucherRequest $request): JsonResponse
    {
        try {
            $result = $this->voucherService->applyVoucher(
                $request->user(),
                $request->input('code'),
                $request->input('booking_code')
            );

            return response()->json([
                'success' => true,
                'message' => "Voucher {$result['voucher']->code} berhasil diterapkan! Anda hemat Rp " . number_format($result['discount_amount'], 0, ',', '.'),
                'data' => [
                    'voucher_code' => $result['voucher']->code,
                    'discount_type' => $result['voucher']->discount_type,
                    'discount_amount' => $result['discount_amount'],
                    'original_amount' => $result['original_amount'],
                    'final_amount' => $result['final_amount'],
                    'booking' => new BookingResource($result['booking']),
                ],
            ], 200);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher atau pemesanan tidak ditemukan.',
            ], 404);
        }
    }

    /**
     * View history of vouchers used by the authenticated user.
     */
    public function usages(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->voucherService->getUserVoucherUsages($request->user(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat penggunaan voucher berhasil diambil',
            'data' => VoucherUsageResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }
}
