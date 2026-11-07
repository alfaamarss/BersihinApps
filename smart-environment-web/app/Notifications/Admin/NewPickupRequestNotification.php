<?php

namespace App\Notifications\Admin;

use App\Models\WasteDelivery;
use App\Notifications\BaseNotification;

class NewPickupRequestNotification extends BaseNotification
{
    protected string $category = 'pickup_request';
    protected string $icon = 'package';
    protected string $color = 'info';

    public function __construct(protected WasteDelivery $wasteDelivery)
    {
    }

    public function toArray(object $notifiable): array
    {
        $requestedDate = $this->wasteDelivery->requested_pickup_date?->format('d M Y') 
            ?? $this->wasteDelivery->pickup_date?->format('d M Y')
            ?? 'N/A';
        // requested_pickup_time is string, not Carbon
        $requestedTime = $this->wasteDelivery->requested_pickup_time 
            ? substr($this->wasteDelivery->requested_pickup_time, 0, 5) 
            : '';
        $userName = $this->wasteDelivery->serviceAccount?->user?->name ?? 'Unknown';
        
        return $this->buildData(
            type: 'new_pickup_request',
            title: 'Request Pengambilan Baru',
            message: "Request pengambilan di luar jadwal dari \"{$userName}\" untuk tanggal {$requestedDate}" . ($requestedTime ? " pukul {$requestedTime}" : "") . ".",
            metadata: [
                'waste_delivery_id' => $this->wasteDelivery->id,
                'service_account_id' => $this->wasteDelivery->service_account_id,
                'user_name' => $userName,
                'requested_date' => $this->wasteDelivery->requested_pickup_date?->format('Y-m-d'),
                'requested_time' => $requestedTime,
                'address' => $this->wasteDelivery->serviceAccount?->address,
                'note' => $this->wasteDelivery->resident_note,
            ],
            actionUrl: "/admin/waste-deliveries/{$this->wasteDelivery->id}"
        );
    }
}
