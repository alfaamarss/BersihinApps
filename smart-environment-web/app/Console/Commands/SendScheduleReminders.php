<?php

namespace App\Console\Commands;

use App\Models\ServiceAccount;
use App\Models\Schedule;
use App\Notifications\User\ScheduleReminderNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendScheduleReminders extends Command
{
    protected $signature = 'notifications:schedule-reminders';
    protected $description = 'Send schedule reminders to users for today pickup';

    public function __construct(protected NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::now();
        $dayOfWeek = $today->format('l'); // Monday, Tuesday, etc. (capitalized)
        $dayName = $this->getDayName(strtolower($dayOfWeek));

        $this->info("Checking schedules for {$dayName} ({$today->format('Y-m-d')})...");

        // Get all active schedules for today using day_of_week enum
        $schedules = Schedule::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->with('area')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No schedules found for today.');
            return Command::SUCCESS;
        }

        $this->info("Found {$schedules->count()} schedule(s) for today.");

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($schedules as $schedule) {
            $areaName = $schedule->area?->name ?? 'Unknown';
            $this->info("Processing schedule #{$schedule->id} for Area: {$areaName}");

            // Get all active service accounts in this area
            $serviceAccounts = ServiceAccount::where('area_id', $schedule->area_id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            foreach ($serviceAccounts as $serviceAccount) {
                if (!$serviceAccount->user) {
                    continue;
                }

                // Check if notification already sent today to prevent duplicates
                $alreadySent = $this->notificationService->wasAlreadySentToday(
                    $serviceAccount->user,
                    'schedule_reminder',
                    ['schedule_id' => $schedule->id]
                );

                if ($alreadySent) {
                    $skippedCount++;
                    continue;
                }

                // Send notification
                $serviceAccount->user->notify(
                    new ScheduleReminderNotification($schedule, $dayName)
                );
                $sentCount++;
            }
        }

        $this->info("Schedule reminders sent: {$sentCount}");
        $this->info("Skipped (already sent): {$skippedCount}");

        return Command::SUCCESS;
    }

    private function getDayName(string $day): string
    {
        return match ($day) {
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
            default => $day,
        };
    }
}
