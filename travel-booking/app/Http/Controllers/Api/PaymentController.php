<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutPaymentRequest;
use App\Http\Requests\SimulatePaymentRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Checkout payment and get payment instructions / virtual account.
     */
    public function checkout(CheckoutPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->checkout($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Instruksi pembayaran berhasil dibuat. Silakan lakukan pembayaran.',
                'data' => new PaymentResource($payment),
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Pemesanan tidak ditemukan atau anda tidak memiliki akses.',
            ], 404);
        }
    }

    /**
     * Simulate mock payment gateway action (success / failed).
     */
    public function simulate(SimulatePaymentRequest $request, string $paymentCode): JsonResponse
    {
        try {
            $action = $request->input('action', 'success');
            $payment = $this->paymentService->simulatePayment($request->user(), $paymentCode, $action);

            $msg = $action === 'success'
                ? 'Pembayaran berhasil disimulasikan! Status pemesanan kini Terkonfirmasi (Confirmed).'
                : 'Simulasi pembayaran gagal diproses. Status pembayaran ditandai gagal.';

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => new PaymentResource($payment),
            ], 200);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Data pembayaran tidak ditemukan atau anda tidak memiliki akses.',
            ], 404);
        }
    }

    /**
     * Check payment status by payment code.
     */
    public function status(Request $request, string $paymentCode): JsonResponse
    {
        try {
            $payment = $this->paymentService->findUserPayment($request->user(), $paymentCode);

            return response()->json([
                'success' => true,
                'message' => 'Status pembayaran berhasil diambil',
                'data' => new PaymentResource($payment),
            ], 200);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Data pembayaran tidak ditemukan atau anda tidak memiliki akses.',
            ], 404);
        }
    }

    /**
     * Get booking confirmation (E-Ticket / Voucher).
     */
    public function confirmation(Request $request, string $bookingCode): JsonResponse
    {
        try {
            $booking = $this->paymentService->getBookingConfirmation($request->user(), $bookingCode);

            return response()->json([
                'success' => true,
                'message' => 'Konfirmasi pemesanan (E-Ticket / E-Voucher) berhasil diambil',
                'data' => new BookingResource($booking),
            ], 200);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Data pemesanan tidak ditemukan atau anda tidak memiliki akses.',
            ], 404);
        }
    }
}
