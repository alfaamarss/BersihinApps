<?php

namespace App\Notifications\User;

use App\Models\WasteDelivery;
use App\Notifications\BaseNotification;

class PickupRequestStatusNotification extends BaseNotification
{
    protected string $category = 'pickup_request';

    public function __construct(
        protected WasteDelivery $wasteDelivery,
        protected string $status // 'approved' or 'rejected'
    ) {
        $this->icon = $status === 'approved' ? 'check-circle' : 'x-circle';
        $this->color = $status === 'approved' ? 'success' : 'danger';
    }

    public function toArray(object $notifiable): array
    {
        $isApproved = $this->status === 'approved';
        
        $message = $isApproved 
            ? "Request pengambilan sampah Anda telah disetujui. Collector akan segera datang."
            : "Request pengambilan sampah Anda ditolak.";
        
        if (!$isApproved && $this->wasteDelivery->rejection_reason) {
            $message .= " Alasan: {$this->wasteDelivery->rejection_reason}";
        }

        $scheduledDate = $this->wasteDelivery->requested_pickup_date?->format('d M Y') 
            ?? $this->wasteDelivery->pickup_date?->format('d M Y');
        if ($isApproved && $scheduledDate) {
            $message .= " Jadwal: {$scheduledDate}.";
        }

        $collectorName = $this->wasteDelivery->assignedCollector?->name ?? null;

        return $this->buildData(
            type: "pickup_request_{$this->status}",
            title: $isApproved ? 'Request Pengambilan Disetujui' : 'Request Pengambilan Ditolak',
            message: $message,
            metadata: [
                'waste_delivery_id' => $this->wasteDelivery->id,
                'status' => $this->status,
                'request_status' => $this->wasteDelivery->request_status,
                'pickup_date' => $this->wasteDelivery->requested_pickup_date?->format('Y-m-d'),
                'rejection_reason' => $this->wasteDelivery->rejection_reason ?? null,
                'collector_name' => $collectorName,
            ],
            actionUrl: "/waste-deliveries/{$this->wasteDelivery->id}"
        );
    }
}
