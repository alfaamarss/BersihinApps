<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as NotificationClass;

class NotificationService
{
    /**
     * Send notification to specific user.
     */
    public function sendToUser(User $user, NotificationClass $notification): void
    {
        $user->notify($notification);
    }

    /**
     * Send notification to all admins.
     */
    public function sendToAdmins(NotificationClass $notification): void
    {
        $admins = User::role('admin')->where('status', 'active')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }

    /**
     * Send notification to specific admin by ID.
     */
    public function sendToAdmin(int $adminId, NotificationClass $notification): void
    {
        $admin = User::role('admin')->find($adminId);
        if ($admin) {
            $admin->notify($notification);
        }
    }

    /**
     * Send notification to specific collector.
     */
    public function sendToCollector(User $collector, NotificationClass $notification): void
    {
        $collector->notify($notification);
    }

    /**
     * Send notification to collector by ID.
     */
    public function sendToCollectorById(int $collectorId, NotificationClass $notification): void
    {
        $collector = User::role('collector')->find($collectorId);
        if ($collector) {
            $collector->notify($notification);
        }
    }

    /**
     * Send notification to collectors assigned to a specific RW.
     */
    public function sendToCollectorsByRw(int $rwId, NotificationClass $notification): void
    {
        $collectors = User::role('collector')
            ->whereHas('collectorAssignments', function ($query) use ($rwId) {
                $query->where('rw_id', $rwId)
                    ->where('start_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    });
            })
            ->where('status', 'active')
            ->get();

        if ($collectors->isNotEmpty()) {
            Notification::send($collectors, $notification);
        }
    }

    /**
     * Send notification to all active users with 'user' role.
     */
    public function sendToAllUsers(NotificationClass $notification): void
    {
        $users = User::role('user')->where('status', 'active')->get();
        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }

    /**
     * Send notification to users in a specific area.
     */
    public function sendToUsersByArea(int $areaId, NotificationClass $notification): void
    {
        $users = User::role('user')
            ->where('status', 'active')
            ->whereHas('serviceAccounts', function ($query) use ($areaId) {
                $query->where('area_id', $areaId)
                    ->where('status', 'active');
            })
            ->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }

    /**
     * Send notification to users in a specific RW.
     */
    public function sendToUsersByRw(int $rwId, NotificationClass $notification): void
    {
        $users = User::role('user')
            ->where('status', 'active')
            ->whereHas('serviceAccounts', function ($query) use ($rwId) {
                $query->where('rw_id', $rwId)
                    ->where('status', 'active');
            })
            ->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }

    /**
     * Send bulk notifications to a collection of users.
     */
    public function sendBulk(Collection $users, NotificationClass $notification): void
    {
        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }

    /**
     * Check if notification was already sent today to prevent duplicates.
     */
    public function wasAlreadySentToday(User $user, string $notificationType, ?array $metadata = null): bool
    {
        $query = $user->notifications()
            ->whereDate('created_at', today())
            ->where('data->type', $notificationType);

        if ($metadata) {
            foreach ($metadata as $key => $value) {
                $query->where("data->metadata->{$key}", $value);
            }
        }

        return $query->exists();
    }
}
