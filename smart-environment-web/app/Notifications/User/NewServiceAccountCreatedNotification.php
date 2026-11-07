<?php

namespace App\Notifications\User;

use App\Models\ServiceAccount;
use App\Notifications\BaseNotification;

class NewServiceAccountCreatedNotification extends BaseNotification
{
    protected string $category = 'service_account';
    protected string $icon = 'home';
    protected string $color = 'success';

    public function __construct(protected ServiceAccount $serviceAccount)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'service_account_created',
            title: 'Akun Layanan Berhasil Dibuat',
            message: "Akun layanan \"{$this->serviceAccount->name}\" berhasil didaftarkan. Selamat menggunakan layanan kami!",
            metadata: [
                'service_account_id' => $this->serviceAccount->id,
                'service_account_name' => $this->serviceAccount->name,
                'address' => $this->serviceAccount->address,
            ],
            actionUrl: "/service-accounts/{$this->serviceAccount->id}"
        );
    }
}
