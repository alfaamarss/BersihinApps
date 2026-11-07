<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\ServiceAccount;
use App\Models\WasteDelivery;
use App\Models\CollectorAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WasteDeliveryGeneratorService
{
    /**
     * Generate waste deliveries untuk semua schedule yang aktif
     *
     * @param int $daysAhead Berapa hari ke depan yang akan di-generate
     * @return array Summary hasil generation
     */
    public function generateAllSchedules(int $daysAhead = 7): array
    {
        $schedules = Schedule::where('is_active', true)
            ->where('auto_generate_delivery', true)
            ->get();

        $summary = [
            'schedules_processed' => 0,
            'deliveries_created' => 0,
            'deliveries_skipped' => 0,
            'errors' => [],
        ];

        foreach ($schedules as $schedule) {
            try {
                $count = $this->generateForSchedule($schedule, $daysAhead);
                $summary['schedules_processed']++;
                $summary['deliveries_created'] += $count;

                Log::info("Generated {$count} deliveries for schedule {$schedule->id}");
            } catch (\Exception $e) {
                $summary['errors'][] = [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to generate deliveries for schedule {$schedule->id}: {$e->getMessage()}");
            }
        }

        return $summary;
    }

    /**
     * Generate waste deliveries untuk schedule tertentu
     *
     * @param Schedule $schedule
     * @param int $daysAhead
     * @return int Jumlah deliveries yang di-create
     */
    public function generateForSchedule(Schedule $schedule, int $daysAhead = 7): int
    {
        $generatedCount = 0;
        $today = Carbon::today();
        $endDate = $today->copy()->addDays($daysAhead);

        $currentDate = $today->copy();

        while ($currentDate->lte($endDate)) {
            // Check apakah hari ini sesuai dengan day_of_week schedule
            if ($this->isDayMatch($currentDate, $schedule->day_of_week)) {
                $count = $this->generateDeliveriesForDate($schedule, $currentDate);
                $generatedCount += $count;
            }

            $currentDate->addDay();
        }

        return $generatedCount;
    }

    /**
     * Generate deliveries untuk tanggal tertentu
     *
     * @param Schedule $schedule
     * @param Carbon $date
     * @return int
     */
    protected function generateDeliveriesForDate(Schedule $schedule, Carbon $date): int
    {
        // Check ada collector assignment atau tidak untuk tanggal ini
        $hasCollector = CollectorAssignment::where('schedule_id', $schedule->id)
            ->where('start_date', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->exists();

        if (!$hasCollector) {
            Log::warning("No collector assigned for schedule {$schedule->id} on {$date->toDateString()}");
            return 0;
        }

        // Get all active service accounts in this area
        $serviceAccounts = ServiceAccount::where('area_id', $schedule->area_id)
            ->where('status', 'active')
            ->get();

        $createdCount = 0;

        DB::beginTransaction();
        try {
            foreach ($serviceAccounts as $account) {
                // Check apakah delivery untuk tanggal ini sudah ada
                $exists = WasteDelivery::where('service_account_id', $account->id)
                    ->where('pickup_date', $date)
                    ->exists();

                if (!$exists) {
                    WasteDelivery::create([
                        'service_account_id' => $account->id,
                        'schedule_id' => $schedule->id,
                        'pickup_date' => $date,
                        'scheduled_at' => $date->copy()->setTime(8, 0), // Default jam 08:00
                        'status' => 'pending',
                        'confirmation_status' => 'pending',
                        'total_amount' => 0,
                    ]);

                    $createdCount++;
                }
            }

            DB::commit();

            if ($createdCount > 0) {
                Log::info("Created {$createdCount} deliveries for schedule {$schedule->id} on {$date->toDateString()}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create deliveries: {$e->getMessage()}");
            throw $e;
        }

        return $createdCount;
    }

    /**
     * Check apakah hari sesuai dengan day_of_week schedule
     *
     * @param Carbon $date
     * @param string $dayOfWeek
     * @return bool
     */
    protected function isDayMatch(Carbon $date, string $dayOfWeek): bool
    {
        // Support format: "Monday", "Senin", dll
        $dayNames = [
            'sunday' => ['sunday', 'minggu'],
            'monday' => ['monday', 'senin'],
            'tuesday' => ['tuesday', 'selasa'],
            'wednesday' => ['wednesday', 'rabu'],
            'thursday' => ['thursday', 'kamis'],
            'friday' => ['friday', 'jumat'],
            'saturday' => ['saturday', 'sabtu'],
        ];

        $currentDay = strtolower($date->format('l')); // "monday", "tuesday", etc
        $scheduleDay = strtolower($dayOfWeek);

        foreach ($dayNames as $key => $variants) {
            if (in_array($currentDay, $variants) && in_array($scheduleDay, $variants)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate deliveries untuk tanggal range tertentu
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateForDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $schedules = Schedule::where('is_active', true)
            ->where('auto_generate_delivery', true)
            ->get();

        $summary = [
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'schedules_processed' => 0,
            'deliveries_created' => 0,
            'errors' => [],
        ];

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            foreach ($schedules as $schedule) {
                if ($this->isDayMatch($currentDate, $schedule->day_of_week)) {
                    try {
                        $count = $this->generateDeliveriesForDate($schedule, $currentDate);
                        $summary['deliveries_created'] += $count;
                    } catch (\Exception $e) {
                        $summary['errors'][] = [
                            'schedule_id' => $schedule->id,
                            'date' => $currentDate->toDateString(),
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            $summary['schedules_processed']++;
            $currentDate->addDay();
        }

        return $summary;
    }

    /**
     * Generate deliveries untuk collector assignment tertentu
     * Akan generate untuk semua service accounts di area/RW yang assigned
     *
     * @param CollectorAssignment $assignment
     * @param int $daysAhead Berapa hari ke depan (default 30 hari)
     * @return int Jumlah deliveries yang berhasil di-create
     */
    public function generateForCollectorAssignment(CollectorAssignment $assignment, int $daysAhead = 30): int
    {
        $schedule = $assignment->schedule;
        $collector = $assignment->user;
        $rw = $assignment->rw;
        
        // Get service accounts yang sesuai dengan assignment
        $query = ServiceAccount::where('area_id', $schedule->area_id)
            ->where('status', 'active');
        
        // Jika ada RW specific, filter by RW
        if ($rw) {
            $query->where('rw_id', $rw->id);
            Log::info("Generating deliveries for collector {$collector->name} assigned to {$schedule->area->name} - {$rw->name}");
        } else {
            Log::info("Generating deliveries for collector {$collector->name} assigned to {$schedule->area->name}");
        }
        
        $serviceAccounts = $query->get();
        
        if ($serviceAccounts->isEmpty()) {
            Log::warning("No active service accounts found for assignment {$assignment->id}");
            return 0;
        }
        
        Log::info("Found {$serviceAccounts->count()} service accounts to generate deliveries");
        
        $generatedCount = 0;
        $startDate = $assignment->start_date ? Carbon::parse($assignment->start_date) : Carbon::today();
        $endDate = $startDate->copy()->addDays($daysAhead);
        
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Check apakah assignment masih aktif di tanggal ini
            if ($assignment->end_date && $currentDate->gt(Carbon::parse($assignment->end_date))) {
                break;
            }
            
            // Check apakah hari ini sesuai dengan day_of_week schedule
            if ($this->isDayMatch($currentDate, $schedule->day_of_week)) {
                foreach ($serviceAccounts as $serviceAccount) {
                    // Check apakah delivery sudah ada
                    $exists = WasteDelivery::where('service_account_id', $serviceAccount->id)
                        ->where('pickup_date', $currentDate->toDateString())
                        ->exists();
                    
                    if (!$exists) {
                        // Extract time from pickup_time (bisa datetime atau time only)
                        $pickupTime = Carbon::parse($schedule->pickup_time)->format('H:i:s');
                        
                        // Combine date and time untuk scheduled_at
                        $scheduledAt = Carbon::parse($currentDate->toDateString() . ' ' . $pickupTime);
                        
                        WasteDelivery::create([
                            'service_account_id' => $serviceAccount->id,
                            'schedule_id' => $schedule->id,
                            'collector_id' => $collector->id, // Langsung assign ke collector
                            'pickup_date' => $currentDate->toDateString(),
                            'scheduled_at' => $scheduledAt, // ⭐ REQUIRED FIELD
                            'status' => 'pending',
                            'confirmation_status' => 'pending',
                        ]);
                        
                        $generatedCount++;
                    }
                }
            }
            
            $currentDate->addDay();
        }
        
        Log::info("Generated {$generatedCount} deliveries for collector assignment {$assignment->id}");
        
        return $generatedCount;
    }

    /**
     * Delete deliveries yang masih pending untuk regenerate
     * Berguna jika ada perubahan schedule
     *
     * @param Schedule $schedule
     * @param Carbon $startDate
     * @return int
     */
    public function deletePendingDeliveries(Schedule $schedule, Carbon $startDate): int
    {
        $deleted = WasteDelivery::where('schedule_id', $schedule->id)
            ->where('pickup_date', '>=', $startDate)
            ->where('status', 'pending')
            ->where('confirmation_status', 'pending')
            ->delete();

        Log::info("Deleted {$deleted} pending deliveries for schedule {$schedule->id}");

        return $deleted;
    }
}
