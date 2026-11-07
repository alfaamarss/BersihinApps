<?php

namespace App\Notifications\Admin;

use App\Models\Complaint;
use App\Notifications\BaseNotification;

class CollectorCompletedComplaintNotification extends BaseNotification
{
    protected string $category = 'complaint';
    protected string $icon = 'check-circle';
    protected string $color = 'success';

    public function __construct(protected Complaint $complaint)
    {
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = $this->getTypeLabel($this->complaint->type);

        return $this->buildData(
            type: 'collector_completed_complaint',
            title: 'Laporan Diselesaikan Collector',
            message: "Collector \"{$this->complaint->assignee?->name}\" menyelesaikan laporan kategori \"{$typeLabel}\" dari \"{$this->complaint->user?->name}\".",
            metadata: [
                'complaint_id' => $this->complaint->id,
                'complaint_number' => $this->complaint->complaint_number ?? $this->complaint->id,
                'type' => $this->complaint->type,
                'type_label' => $typeLabel,
                'collector_id' => $this->complaint->assignee_id,
                'collector_name' => $this->complaint->assignee?->name,
                'user_name' => $this->complaint->user?->name,
                'completed_at' => now()->toISOString(),
            ],
            actionUrl: "/admin/complaints/{$this->complaint->id}"
        );
    }

    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'sampah_tidak_diangkut' => 'Sampah Tidak Diangkut',
            'sampah_menumpuk' => 'Sampah Menumpuk',
            'petugas_tidak_datang' => 'Petugas Tidak Datang',
            'jadwal_tidak_sesuai' => 'Jadwal Tidak Sesuai',
            'keluhan_layanan' => 'Keluhan Layanan',
            'lainnya' => 'Lainnya',
            default => $type,
        };
    }
}
