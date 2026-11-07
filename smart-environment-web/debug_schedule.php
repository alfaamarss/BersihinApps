<?php

use App\Models\Schedule;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schedules = Schedule::all();
echo "Total Schedules: " . $schedules->count() . "\n";

foreach ($schedules as $schedule) {
    echo "ID: {$schedule->id}, Area: {$schedule->area_id}, Day: {$schedule->day_of_week}, Time: {$schedule->pickup_time}, Active: {$schedule->is_active}\n";
}

$start = '2025-12-01';
$end = '2025-12-31';
$startDate = Carbon::parse($start)->startOfDay();
$endDate = Carbon::parse($end)->endOfDay();

echo "\nChecking for range: $start to $end\n";

foreach ($schedules as $schedule) {
    $dayMapping = [
        'Monday' => Carbon::MONDAY,
        'Tuesday' => Carbon::TUESDAY,
        'Wednesday' => Carbon::WEDNESDAY,
        'Thursday' => Carbon::THURSDAY,
        'Friday' => Carbon::FRIDAY,
        'Saturday' => Carbon::SATURDAY,
        'Sunday' => Carbon::SUNDAY,
    ];
    
    $dayOfWeek = $dayMapping[$schedule->day_of_week] ?? null;
    echo "Schedule {$schedule->id} ({$schedule->day_of_week}) -> Carbon Day: $dayOfWeek\n";
    
    $currentDate = $startDate->copy();
    while ($currentDate->dayOfWeek !== $dayOfWeek && $currentDate->lte($endDate)) {
        $currentDate->addDay();
    }
    
    if ($currentDate->lte($endDate)) {
        echo "  First occurrence: " . $currentDate->format('Y-m-d') . "\n";
    } else {
        echo "  No occurrence in range.\n";
    }
}
