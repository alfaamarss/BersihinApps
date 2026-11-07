<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Schedule;
use App\Models\WasteDelivery;
use App\Services\WasteDeliveryGeneratorService;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    protected WasteDeliveryGeneratorService $generator;

    public function __construct(WasteDeliveryGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Handle the Invoice "updated" event.
     * Auto-generate waste deliveries for next month when invoice is paid.
     */
    public function updated(Invoice $invoice): void
    {
        // Only trigger when invoice status changes to 'paid'
        if (!$invoice->isDirty('status')) {
            return;
        }

        if ($invoice->status !== 'paid') {
            return;
        }

        // Update request_status to 'paid' for off-schedule pickup requests
        $this->updateOffSchedulePickupStatus($invoice);

        // Get service account
        $serviceAccount = $invoice->serviceAccount;
        
        if (!$serviceAccount || $serviceAccount->status !== 'active') {
            Log::info("Invoice {$invoice->id} paid but service account is not active. Skipping delivery generation.");
            return;
        }

        // Find active schedule for this area
        $schedule = Schedule::where('area_id', $serviceAccount->area_id)
            ->where('is_active', true)
            ->where('auto_generate_delivery', true)
            ->first();

        if (!$schedule) {
            Log::warning("No active schedule with auto_generate_delivery found for area {$serviceAccount->area_id}. Invoice {$invoice->id} paid without next month delivery generation.");
            return;
        }

        try {
            // Generate deliveries for the next 30 days (1 month ahead)
            // This will create deliveries that don't already exist
            $count = $this->generator->generateForSchedule($schedule, 30);
            
            Log::info("Auto-generated {$count} deliveries for next period after Invoice {$invoice->id} was paid (ServiceAccount: {$serviceAccount->id})");
        } catch (\Exception $e) {
            Log::error("Failed to auto-generate deliveries after Invoice {$invoice->id} payment: {$e->getMessage()}");
        }
    }

    /**
     * Update off-schedule pickup request_status to 'paid' when invoice is paid.
     */
    protected function updateOffSchedulePickupStatus(Invoice $invoice): void
    {
        // Find the waste delivery associated with this invoice (off-schedule pickup)
        $delivery = WasteDelivery::where('id', $invoice->waste_delivery_id)
            ->where('pickup_type', 'request')
            ->where('request_status', 'completed')
            ->first();

        if ($delivery) {
            $delivery->request_status = 'paid';
            $delivery->save();

            Log::info("Updated off-schedule pickup #{$delivery->id} request_status to 'paid' after Invoice #{$invoice->id} payment.");
        }
    }
}
