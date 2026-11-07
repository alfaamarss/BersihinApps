<?php

namespace App\Notifications\Admin;

use App\Models\WasteDelivery;
use App\Notifications\BaseNotification;

class CollectorSubmittedProofNotification extends BaseNotification
{
    protected string $category = 'pickup_request';
    protected string $icon = 'camera';
    protected string $color = 'success';

    public function __construct(protected WasteDelivery $wasteDelivery)
    {
    }

    public function toArray(object $notifiable): array
    {
        $collectorName = $this->wasteDelivery->assignedCollector?->name ?? 'Collector';
        $userName = $this->wasteDelivery->serviceAccount?->user?->name ?? 'Unknown';
        
        return $this->buildData(
            type: 'collector_submitted_proof',
            title: 'Bukti Pengambilan Dikirim',
            message: "Collector \"{$collectorName}\" telah mengirim bukti pengambilan untuk request dari \"{$userName}\".",
            metadata: [
                'waste_delivery_id' => $this->wasteDelivery->id,
                'collector_id' => $this->wasteDelivery->assigned_collector_id,
                'collector_name' => $collectorName,
                'service_account_id' => $this->wasteDelivery->service_account_id,
                'user_name' => $userName,
                'photo_url' => $this->wasteDelivery->photo_url,
                'collected_at' => $this->wasteDelivery->collected_at?->toISOString(),
            ],
            actionUrl: "/admin/waste-deliveries/{$this->wasteDelivery->id}"
        );
    }
}
