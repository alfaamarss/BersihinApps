<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\User\BillingReminderNotification;
use App\Notifications\User\BillingDueTodayNotification;
use App\Notifications\User\BillingOverdueNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendBillingReminders extends Command
{
    protected $signature = 'notifications:billing-reminders 
                            {--force : Force send even if already sent today}';
    protected $description = 'Send billing reminders to users (H-3, H-2, H-1, due today, overdue)';

    public function __construct(protected NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::now()->startOfDay();
        $force = $this->option('force');

        $this->info("Processing billing reminders for {$today->format('Y-m-d')}...");
        $this->newLine();

        $stats = [
            'h3' => 0,
            'h2' => 0,
            'h1' => 0,
            'due_today' => 0,
            'overdue' => 0,
            'skipped' => 0,
        ];

        // H-3 reminder (due date is in 3 days)
        $stats['h3'] = $this->sendReminders($today->copy()->addDays(3), 3, $force);
        
        // H-2 reminder
        $stats['h2'] = $this->sendReminders($today->copy()->addDays(2), 2, $force);
        
        // H-1 reminder
        $stats['h1'] = $this->sendReminders($today->copy()->addDays(1), 1, $force);
        
        // Due today (Hari-H)
        $stats['due_today'] = $this->sendDueTodayReminders($today, $force);
        
        // Overdue (after due date)
        $stats['overdue'] = $this->sendOverdueReminders($today, $force);

        $this->newLine();
        $this->table(
            ['Type', 'Sent'],
            [
                ['H-3 Reminders', $stats['h3']],
                ['H-2 Reminders', $stats['h2']],
                ['H-1 Reminders', $stats['h1']],
                ['Due Today', $stats['due_today']],
                ['Overdue', $stats['overdue']],
            ]
        );

        $total = array_sum($stats);
        $this->info("Total notifications sent: {$total}");

        return Command::SUCCESS;
    }

    private function sendReminders(Carbon $dueDate, int $daysRemaining, bool $force): int
    {
        $this->info("Processing H-{$daysRemaining} reminders (due: {$dueDate->format('Y-m-d')})...");

        // Use va_expiry_at as the due date for billing
        $invoices = Invoice::whereDate('va_expiry_at', $dueDate)
            ->where('status', 'not_paid')
            ->with('serviceAccount.user')
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $user = $invoice->serviceAccount?->user;
            if (!$user) {
                continue;
            }

            // Check if already sent today (unless forced)
            if (!$force) {
                $alreadySent = $this->notificationService->wasAlreadySentToday(
                    $user,
                    'billing_reminder',
                    ['invoice_id' => $invoice->id]
                );

                if ($alreadySent) {
                    continue;
                }
            }

            $user->notify(new BillingReminderNotification($invoice, $daysRemaining));
            $sent++;
        }

        $this->line("  → Sent: {$sent}");
        return $sent;
    }

    private function sendDueTodayReminders(Carbon $today, bool $force): int
    {
        $this->info("Processing due today reminders...");

        $invoices = Invoice::whereDate('va_expiry_at', $today)
            ->where('status', 'not_paid')
            ->with('serviceAccount.user')
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $user = $invoice->serviceAccount?->user;
            if (!$user) {
                continue;
            }

            if (!$force) {
                $alreadySent = $this->notificationService->wasAlreadySentToday(
                    $user,
                    'billing_due_today',
                    ['invoice_id' => $invoice->id]
                );

                if ($alreadySent) {
                    continue;
                }
            }

            $user->notify(new BillingDueTodayNotification($invoice));
            $sent++;
        }

        $this->line("  → Sent: {$sent}");
        return $sent;
    }

    private function sendOverdueReminders(Carbon $today, bool $force): int
    {
        $this->info("Processing overdue reminders...");

        // Get all overdue unpaid invoices
        $invoices = Invoice::whereDate('va_expiry_at', '<', $today)
            ->where('status', 'not_paid')
            ->with('serviceAccount.user')
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $user = $invoice->serviceAccount?->user;
            if (!$user) {
                continue;
            }

            if (!$force) {
                // For overdue, only send once per week to avoid spamming
                $lastSent = $user->notifications()
                    ->where('data->type', 'billing_overdue')
                    ->where('data->metadata->invoice_id', $invoice->id)
                    ->latest()
                    ->first();

                if ($lastSent && $today->diffInDays(Carbon::parse($lastSent->created_at)) < 7) {
                    continue;
                }
            }

            $user->notify(new BillingOverdueNotification($invoice));
            $sent++;
        }

        $this->line("  → Sent: {$sent}");
        return $sent;
    }
}
