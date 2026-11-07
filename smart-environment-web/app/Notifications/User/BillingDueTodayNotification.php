<?php

namespace App\Notifications\User;

use App\Models\Invoice;
use App\Notifications\BaseNotification;

class BillingDueTodayNotification extends BaseNotification
{
    protected string $category = 'billing';
    protected string $icon = 'alert-circle';
    protected string $color = 'danger';

    public function __construct(protected Invoice $invoice)
    {
    }

    public function toArray(object $notifiable): array
    {
        $formattedAmount = number_format($this->invoice->amount, 0, ',', '.');

        return $this->buildData(
            type: 'billing_due_today',
            title: 'Tenggat Pembayaran Hari Ini',
            message: "Hari ini tenggat pembayaran tagihan Anda sebesar Rp {$formattedAmount}. Segera lakukan pembayaran.",
            metadata: [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
                'formatted_amount' => "Rp {$formattedAmount}",
            ],
            actionUrl: "/invoices/{$this->invoice->id}"
        );
    }
}
