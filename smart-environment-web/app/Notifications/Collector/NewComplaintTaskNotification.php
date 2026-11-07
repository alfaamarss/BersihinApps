<?php

namespace App\Notifications\Collector;

use App\Models\Complaint;
use App\Notifications\BaseNotification;

class NewComplaintTaskNotification extends BaseNotification
{
    protected string $category = 'task';
    protected string $icon = 'alert-circle';
    protected string $color = 'warning';

    public function __construct(protected Complaint $complaint)
    {
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = $this->getTypeLabel($this->complaint->type);

        return $this->buildData(
            type: 'new_complaint_task',
            title: 'Tugas Laporan Baru',
            message: "Anda mendapat tugas menangani laporan \"{$typeLabel}\" dari \"{$this->complaint->user?->name}\".",
            metadata: [
                'complaint_id' => $this->complaint->id,
                'complaint_number' => $this->complaint->complaint_number ?? $this->complaint->id,
                'type' => $this->complaint->type,
                'type_label' => $typeLabel,
                'user_id' => $this->complaint->user_id,
                'user_name' => $this->complaint->user?->name,
                'user_phone' => $this->complaint->user?->phone_number,
                'address' => $this->complaint->address,
                'description' => $this->complaint->description,
                'latitude' => $this->complaint->latitude,
                'longitude' => $this->complaint->longitude,
            ],
            actionUrl: "/collector/complaints/{$this->complaint->id}"
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
