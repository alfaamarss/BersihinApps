<?php

namespace App\Notifications\Collector;

use App\Models\WasteDelivery;
use App\Notifications\BaseNotification;

class NewPickupTaskNotification extends BaseNotification
{
    protected string $category = 'task';
    protected string $icon = 'truck';
    protected string $color = 'primary';

    public function __construct(protected WasteDelivery $wasteDelivery)
    {
    }

    public function toArray(object $notifiable): array
    {
        $pickupDate = $this->wasteDelivery->requested_pickup_date?->format('d M Y') 
            ?? $this->wasteDelivery->pickup_date?->format('d M Y')
            ?? 'Belum ditentukan';
        // requested_pickup_time is string, not Carbon
        $pickupTime = $this->wasteDelivery->requested_pickup_time 
            ? substr($this->wasteDelivery->requested_pickup_time, 0, 5) 
            : null;
        $address = $this->wasteDelivery->serviceAccount?->address ?? 'Alamat tidak tersedia';
        $userName = $this->wasteDelivery->serviceAccount?->user?->name ?? 'Unknown';
        $userPhone = $this->wasteDelivery->serviceAccount?->user?->phone_number ?? null;

        $message = "Anda mendapat tugas pengambilan sampah dari \"{$userName}\" untuk tanggal {$pickupDate}";
        if ($pickupTime) {
            $message .= " pukul {$pickupTime}";
        }
        $message .= ".";

        return $this->buildData(
            type: 'new_pickup_task',
            title: 'Tugas Pengambilan Baru',
            message: $message,
            metadata: [
                'waste_delivery_id' => $this->wasteDelivery->id,
                'service_account_id' => $this->wasteDelivery->service_account_id,
                'user_name' => $userName,
                'user_phone' => $userPhone,
                'address' => $address,
                'pickup_date' => $this->wasteDelivery->requested_pickup_date?->format('Y-m-d'),
                'pickup_time' => $pickupTime,
                'resident_note' => $this->wasteDelivery->resident_note,
                'latitude' => $this->wasteDelivery->serviceAccount?->latitude,
                'longitude' => $this->wasteDelivery->serviceAccount?->longitude,
            ],
            actionUrl: "/collector/waste-deliveries/{$this->wasteDelivery->id}"
        );
    }
}
