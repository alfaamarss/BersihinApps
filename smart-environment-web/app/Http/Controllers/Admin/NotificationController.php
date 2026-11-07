<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications count and recent notifications
     */
    public function getUnread()
    {
        $user = Auth::user();
        $unreadCount = $user->unreadNotifications()->count();
        $notifications = $user->unreadNotifications()->take(5)->get();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            
            // Check if it's an AJAX request
            if ($request->expectsJson()) {
                return response()->json(['success' => true]);
            }
            
            return back()->with('success', 'Notifikasi berhasil ditandai sebagai sudah dibaca.');
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false], 404);
        }
        
        return back()->with('error', 'Notifikasi tidak ditemukan.');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        // Check if it's an AJAX request
        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Semua notifikasi berhasil ditandai sebagai sudah dibaca.');
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->delete();
            return back()->with('success', 'Notifikasi berhasil dihapus.');
        }

        return back()->with('error', 'Notifikasi tidak ditemukan.');
    }

    /**
     * Clear all read notifications
     */
    public function clearAll()
    {
        Auth::user()->readNotifications()->delete();
        
        return back()->with('success', 'Semua notifikasi yang sudah dibaca berhasil dihapus.');
    }
}
