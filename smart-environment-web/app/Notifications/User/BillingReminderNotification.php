<?php

namespace App\Notifications\User;

use App\Models\Invoice;
use App\Notifications\BaseNotification;

class BillingReminderNotification extends BaseNotification
{
    protected string $category = 'billing';
    protected string $icon = 'credit-card';
    protected string $color = 'warning';

    public function __construct(
        protected Invoice $invoice,
        protected int $daysRemaining
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = match ($this->daysRemaining) {
            3 => "Ayo, ini H-3 menuju tenggat pembayaran. Lakukan pembayaran agar sampah kamu tetap diambil.",
            2 => "H-2 menuju tenggat pembayaran. Segera lakukan pembayaran.",
            1 => "Besok adalah tenggat pembayaran. Segera lakukan pembayaran.",
            default => "Segera lakukan pembayaran tagihan Anda.",
        };

        $formattedAmount = number_format($this->invoice->amount, 0, ',', '.');

        return $this->buildData(
            type: 'billing_reminder',
            title: "Pengingat Tagihan (H-{$this->daysRemaining})",
            message: $message,
            metadata: [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
                'formatted_amount' => "Rp {$formattedAmount}",
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
                'days_remaining' => $this->daysRemaining,
            ],
            actionUrl: "/invoices/{$this->invoice->id}"
        );
    }
}
