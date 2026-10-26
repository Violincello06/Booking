<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * List all notifications in the system.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['type', 'user_id', 'is_read']);

        $paginator = $this->notificationService->getAdminNotifications($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar notifikasi sistem berhasil diambil',
            'data' => NotificationResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * Broadcast notification to users.
     */
    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['nullable', 'string', 'in:promo,system,announcement,update'],
            'role' => ['nullable', 'string', 'in:user,staff,admin'],
        ], [
            'title.required' => 'Judul notifikasi wajib diisi.',
            'message.required' => 'Pesan notifikasi wajib diisi.',
        ]);

        $sentCount = $this->notificationService->broadcast($data);

        return response()->json([
            'success' => true,
            'message' => "Notifikasi berhasil disiarkan kepada {$sentCount} pengguna.",
            'data' => [
                'broadcast_count' => $sentCount,
            ],
        ], 201);
    }
}
