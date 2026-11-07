<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationsController extends Controller
{
    /**
     * Get all notifications for authenticated user with pagination.
     * 
     * @queryParam per_page int Number of items per page. Default: 20
     * @queryParam category string Filter by category (billing, schedule, complaint, etc.)
     * @queryParam unread_only bool Only return unread notifications
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);
        $category = $request->get('category');
        $unreadOnly = filter_var($request->get('unread_only', false), FILTER_VALIDATE_BOOLEAN);

        $query = $request->user()->notifications();

        // Filter by category if specified
        if ($category) {
            $query->where('data->category', $category);
        }

        // Filter unread only if specified
        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        // Get count per category
        $countByCategory = $request->user()
            ->unreadNotifications()
            ->get()
            ->groupBy(fn($n) => $n->data['category'] ?? 'general')
            ->map->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
                'by_category' => $countByCategory,
            ],
        ]);
    }

    /**
     * Get recent unread notifications.
     * 
     * @queryParam limit int Number of notifications to return. Default: 10
     */
    public function unread(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);

        $notifications = $request->user()
            ->unreadNotifications()
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'meta' => [
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Get a single notification by ID.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => $notification->fresh(),
        ]);
    }

    /**
     * Mark multiple notifications as read.
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|uuid',
        ]);

        $count = $request->user()
            ->notifications()
            ->whereIn('id', $request->ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) marked as read.",
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => "All {$count} notifications marked as read.",
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
        ]);
    }

    /**
     * Delete multiple notifications.
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|uuid',
        ]);

        $count = $request->user()
            ->notifications()
            ->whereIn('id', $request->ids)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) deleted.",
        ]);
    }

    /**
     * Delete all read notifications.
     */
    public function clearRead(Request $request): JsonResponse
    {
        $count = $request->user()->readNotifications()->count();
        $request->user()->readNotifications()->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$count} read notifications.",
        ]);
    }

    /**
     * Get available notification categories.
     */
    public function categories(): JsonResponse
    {
        $categories = [
            'service_account' => [
                'name' => 'Akun Layanan',
                'description' => 'Notifikasi terkait akun layanan',
            ],
            'billing' => [
                'name' => 'Tagihan',
                'description' => 'Notifikasi tagihan dan pembayaran',
            ],
            'schedule' => [
                'name' => 'Jadwal',
                'description' => 'Pengingat jadwal pengambilan sampah',
            ],
            'complaint' => [
                'name' => 'Pelaporan',
                'description' => 'Status laporan/komplain',
            ],
            'pickup_request' => [
                'name' => 'Request Pengambilan',
                'description' => 'Status request pengambilan di luar jadwal',
            ],
            'article' => [
                'name' => 'Artikel',
                'description' => 'Artikel dan berita baru',
            ],
            'task' => [
                'name' => 'Tugas',
                'description' => 'Tugas untuk collector',
            ],
            'rw' => [
                'name' => 'Wilayah',
                'description' => 'Notifikasi terkait wilayah/RW',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
