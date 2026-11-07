<?php

namespace App\Notifications\User;

use App\Models\Invoice;
use App\Notifications\BaseNotification;

class BillingOverdueNotification extends BaseNotification
{
    protected string $category = 'billing';
    protected string $icon = 'alert-triangle';
    protected string $color = 'danger';

    public function __construct(protected Invoice $invoice)
    {
    }

    public function toArray(object $notifiable): array
    {
        $formattedAmount = number_format($this->invoice->amount, 0, ',', '.');

        return $this->buildData(
            type: 'billing_overdue',
            title: 'Tagihan Belum Dibayar',
            message: "Anda belum membayar tagihan sebesar Rp {$formattedAmount}. Sampah minggu depan tidak akan diambil sampai Anda membayar.",
            metadata: [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
                'formatted_amount' => "Rp {$formattedAmount}",
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
                'overdue_days' => $this->invoice->due_date->diffInDays(now()),
            ],
            actionUrl: "/invoices/{$this->invoice->id}"
        );
    }
}
