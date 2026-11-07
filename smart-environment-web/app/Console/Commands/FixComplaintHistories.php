<?php

namespace App\Console\Commands;

use App\Models\Complaint;
use App\Models\ComplaintHistory;
use Illuminate\Console\Command;

class FixComplaintHistories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complaints:fix-histories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add missing history records for existing complaints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing complaint histories...');

        $complaints = Complaint::with(['histories', 'assignee', 'user'])->get();
        $fixed = 0;

        foreach ($complaints as $complaint) {
            $hasOpenHistory = $complaint->histories->where('status', 'open')->count() > 0;

            if (!$hasOpenHistory) {
                // Add initial 'open' history with complaint created_at time
                ComplaintHistory::create([
                    'complaint_id' => $complaint->id,
                    'status' => 'open',
                    'description' => 'Pengaduan dibuat oleh warga',
                    'changed_by' => $complaint->user_id,
                    'created_at' => $complaint->created_at,
                    'updated_at' => $complaint->created_at,
                ]);

                $this->line("Added 'open' history for complaint #{$complaint->id}");
                $fixed++;
            }

            // Check if current status history exists (for non-open complaints)
            if ($complaint->status !== 'open') {
                $hasCurrentStatusHistory = $complaint->histories->where('status', $complaint->status)->count() > 0;

                if (!$hasCurrentStatusHistory) {
                    $description = match($complaint->status) {
                        'in_progress' => $complaint->assignee_id 
                            ? 'Pengaduan ditugaskan ke ' . ($complaint->assignee->name ?? 'Collector') 
                            : 'Pengaduan sedang ditangani oleh tim',
                        'resolved' => 'Pengaduan telah diselesaikan',
                        'rejected' => 'Pengaduan telah ditolak',
                        default => 'Status diubah'
                    };

                    $note = $complaint->status === 'rejected' ? $complaint->rejection_reason : null;

                    ComplaintHistory::create([
                        'complaint_id' => $complaint->id,
                        'status' => $complaint->status,
                        'description' => $description,
                        'note' => $note,
                        'changed_by' => null,
                        'created_at' => $complaint->updated_at,
                        'updated_at' => $complaint->updated_at,
                    ]);

                    $this->line("Added '{$complaint->status}' history for complaint #{$complaint->id}");
                    $fixed++;
                }
            }
        }

        $this->info("Done! Fixed {$fixed} history records.");

        return Command::SUCCESS;
    }
}
