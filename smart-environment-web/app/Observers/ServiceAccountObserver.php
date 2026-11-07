<?php

namespace App\Observers;

use App\Models\ServiceAccount;
use App\Models\Schedule;
use App\Models\User;
use App\Services\WasteDeliveryGeneratorService;
use App\Notifications\ServiceAccountDeactivatedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ServiceAccountObserver
{
    protected WasteDeliveryGeneratorService $generator;

    public function __construct(WasteDeliveryGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Handle the ServiceAccount "created" event.
     * Auto-generate waste deliveries when a new service account is created.
     */
    public function created(ServiceAccount $serviceAccount): void
    {
        // Only generate if service account is active
        if ($serviceAccount->status !== 'active') {
            Log::info("ServiceAccount {$serviceAccount->id} created but not active. Skipping delivery generation.");
            return;
        }

        // Find active schedule for this area
        $schedule = Schedule::where('area_id', $serviceAccount->area_id)
            ->where('is_active', true)
            ->where('auto_generate_delivery', true)
            ->first();

        if (!$schedule) {
            Log::warning("No active schedule with auto_generate_delivery found for area {$serviceAccount->area_id}. ServiceAccount {$serviceAccount->id} created without delivery generation.");
            return;
        }

        try {
            // Generate deliveries for the next 30 days (1 month)
            $count = $this->generator->generateForSchedule($schedule, 30);
            
            Log::info("Auto-generated {$count} deliveries for new ServiceAccount {$serviceAccount->id} (Area: {$serviceAccount->area_id})");
        } catch (\Exception $e) {
            Log::error("Failed to auto-generate deliveries for ServiceAccount {$serviceAccount->id}: {$e->getMessage()}");
        }
    }

    /**
     * Handle the ServiceAccount "updated" event.
     * Send notification to admins when status changes to inactive.
     */
    public function updated(ServiceAccount $serviceAccount): void
    {
        // Check if status was changed to inactive
        if ($serviceAccount->isDirty('status') && $serviceAccount->status === 'inactive') {
            $this->notifyAdminsOfDeactivation($serviceAccount);
        }
    }

    /**
     * Send notification to all admins when a service account is deactivated.
     */
    protected function notifyAdminsOfDeactivation(ServiceAccount $serviceAccount): void
    {
        try {
            // Get all users with admin or super-admin role
            $admins = User::role(['admin', 'super-admin'])->get();

            if ($admins->isEmpty()) {
                Log::warning("No admin users found to notify about ServiceAccount {$serviceAccount->id} deactivation.");
                return;
            }

            // Send notification to all admins
            Notification::send($admins, new ServiceAccountDeactivatedNotification($serviceAccount));

            Log::info("Sent deactivation notification for ServiceAccount {$serviceAccount->id} to " . $admins->count() . " admin(s).");
        } catch (\Exception $e) {
            Log::error("Failed to send deactivation notification for ServiceAccount {$serviceAccount->id}: {$e->getMessage()}");
        }
    }
}
