<?php

namespace App\Notifications\Admin;

use App\Models\Complaint;
use App\Notifications\BaseNotification;

class NewComplaintNotification extends BaseNotification
{
    protected string $category = 'complaint';
    protected string $icon = 'message-square';
    protected string $color = 'warning';

    public function __construct(protected Complaint $complaint)
    {
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = $this->getTypeLabel($this->complaint->type);

        return $this->buildData(
            type: 'new_complaint',
            title: 'Pelaporan Baru',
            message: "Pelaporan baru dari \"{$this->complaint->user?->name}\": {$typeLabel}",
            metadata: [
                'complaint_id' => $this->complaint->id,
                'complaint_number' => $this->complaint->complaint_number ?? $this->complaint->id,
                'type' => $this->complaint->type,
                'type_label' => $typeLabel,
                'user_id' => $this->complaint->user_id,
                'user_name' => $this->complaint->user?->name,
                'description' => $this->complaint->description,
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
