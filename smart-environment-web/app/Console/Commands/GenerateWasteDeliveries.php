<?php

namespace App\Console\Commands;

use App\Services\WasteDeliveryGeneratorService;
use Illuminate\Console\Command;

class GenerateWasteDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deliveries:generate
                            {--days=7 : Number of days ahead to generate deliveries}
                            {--schedule= : Generate for specific schedule ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate waste deliveries based on active schedules';

    /**
     * Execute the console command.
     */
    public function handle(WasteDeliveryGeneratorService $generator)
    {
        $this->info('Starting waste delivery generation...');

        $daysAhead = (int) $this->option('days');
        $scheduleId = $this->option('schedule');

        try {
            if ($scheduleId) {
                // Generate untuk schedule tertentu
                $schedule = \App\Models\Schedule::findOrFail($scheduleId);

                $this->info("Generating deliveries for schedule #{$scheduleId}...");
                $count = $generator->generateForSchedule($schedule, $daysAhead);

                $this->info("✓ Created {$count} deliveries for schedule #{$scheduleId}");
            } else {
                // Generate untuk semua schedule
                $this->info("Generating deliveries for all active schedules (next {$daysAhead} days)...");
                $summary = $generator->generateAllSchedules($daysAhead);

                $this->newLine();
                $this->info("Generation Summary:");
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Schedules Processed', $summary['schedules_processed']],
                        ['Deliveries Created', $summary['deliveries_created']],
                        ['Errors', count($summary['errors'])],
                    ]
                );

                if (!empty($summary['errors'])) {
                    $this->newLine();
                    $this->warn('Errors encountered:');
                    foreach ($summary['errors'] as $error) {
                        $this->error("Schedule #{$error['schedule_id']}: {$error['error']}");
                    }
                }
            }

            $this->newLine();
            $this->info('✓ Generation completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to generate deliveries: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
