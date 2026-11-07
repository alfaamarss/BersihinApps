<?php

namespace App\Notifications\Admin;

use App\Models\ServiceAccount;
use App\Notifications\BaseNotification;

class UserDeactivatedServiceAccountNotification extends BaseNotification
{
    protected string $category = 'service_account';
    protected string $icon = 'user-minus';
    protected string $color = 'warning';

    public function __construct(protected ServiceAccount $serviceAccount)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'user_deactivated_service_account',
            title: 'User Menonaktifkan Akun Layanan',
            message: "User \"{$this->serviceAccount->user?->name}\" menonaktifkan akun layanan \"{$this->serviceAccount->name}\".",
            metadata: [
                'service_account_id' => $this->serviceAccount->id,
                'service_account_name' => $this->serviceAccount->name,
                'user_id' => $this->serviceAccount->user_id,
                'user_name' => $this->serviceAccount->user?->name,
                'area_name' => $this->serviceAccount->area?->name,
                'rw_name' => $this->serviceAccount->rw?->name,
            ],
            actionUrl: "/admin/service-accounts/{$this->serviceAccount->id}"
        );
    }
}
