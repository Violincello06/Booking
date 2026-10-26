<?php

namespace App\Http\Controllers\Api;

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
     * Get paginated notifications for current user with unread counter.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $type = $request->input('type');

        $paginator = $this->notificationService->getUserNotifications($request->user(), $perPage, $type);
        $unreadCount = $this->notificationService->getUnreadCount($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Daftar notifikasi pengguna berhasil diambil',
            'data' => [
                'unread_count' => $unreadCount,
                'notifications' => NotificationResource::collection($paginator->items()),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * Lightweight endpoint to fetch unread notification badge counter.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ], 200);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = $this->notificationService->markAsRead($request->user(), $id);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi telah ditandai sebagai dibaca',
            'data' => new NotificationResource($notification),
        ], 200);
    }

    /**
     * Mark all notifications of current user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $affected = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'message' => "Sebanyak {$affected} notifikasi berhasil ditandai sebagai telah dibaca",
            'data' => [
                'updated_count' => $affected,
            ],
        ], 200);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->notificationService->deleteNotification($request->user(), $id);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dihapus',
        ], 200);
    }
}
