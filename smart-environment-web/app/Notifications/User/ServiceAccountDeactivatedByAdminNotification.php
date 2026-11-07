<?php

namespace App\Notifications\User;

use App\Models\ServiceAccount;
use App\Notifications\BaseNotification;

class ServiceAccountDeactivatedByAdminNotification extends BaseNotification
{
    protected string $category = 'service_account';
    protected string $icon = 'user-x';
    protected string $color = 'danger';

    public function __construct(
        protected ServiceAccount $serviceAccount,
        protected ?string $reason = null
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = "Akun layanan \"{$this->serviceAccount->name}\" telah dinonaktifkan oleh admin.";
        if ($this->reason) {
            $message .= " Alasan: {$this->reason}";
        }

        return $this->buildData(
            type: 'service_account_deactivated_by_admin',
            title: 'Akun Layanan Dinonaktifkan',
            message: $message,
            metadata: [
                'service_account_id' => $this->serviceAccount->id,
                'service_account_name' => $this->serviceAccount->name,
                'reason' => $this->reason,
                'deactivated_at' => now()->toISOString(),
            ],
            actionUrl: "/service-accounts/{$this->serviceAccount->id}"
        );
    }
}
