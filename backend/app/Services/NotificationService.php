<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    /**
     * Send or create a notification for a user.
     */
    public function send(User|int $user, string $title, string $message, string $type = 'system'): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'read_at' => null,
        ]);
    }

    /**
     * Get paginated notifications for the authenticated user.
     */
    public function getUserNotifications(User $user, int $perPage = 10, ?string $type = null): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $user->id);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Count unread notifications for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(User $user, int $id): Notification
    {
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }

    /**
     * Mark all notifications of a user as read.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a single notification.
     */
    public function deleteNotification(User $user, int $id): bool
    {
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $notification->delete();
    }

    /**
     * Broadcast notification to all active users or by role.
     */
    public function broadcast(array $data): int
    {
        $query = User::query();

        if (! empty($data['role'])) {
            $query->where('role', $data['role']);
        }

        $userIds = $query->pluck('id');

        $now = now();
        $records = $userIds->map(fn ($id) => [
            'user_id' => $id,
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? 'system',
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        if (! empty($records)) {
            Notification::insert($records);
        }

        return count($records);
    }

    /**
     * Admin view of all notifications across the system.
     */
    public function getAdminNotifications(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Notification::with('user:id,name,email');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['is_read'])) {
            if ($filters['is_read'] === 'true' || $filters['is_read'] === true || $filters['is_read'] === '1') {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        return $query->latest()->paginate($perPage);
    }
}
