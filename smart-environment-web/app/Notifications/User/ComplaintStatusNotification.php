<?php

namespace App\Notifications\User;

use App\Models\Complaint;
use App\Notifications\BaseNotification;

class ComplaintStatusNotification extends BaseNotification
{
    protected string $category = 'complaint';

    public function __construct(
        protected Complaint $complaint,
        protected string $status, // 'in_progress', 'resolved', 'rejected', 'pending_confirmation'
        protected ?string $collectorName = null
    ) {
        $this->setIconAndColor();
    }

    private function setIconAndColor(): void
    {
        match ($this->status) {
            'in_progress' => [
                $this->icon = 'loader',
                $this->color = 'info',
            ],
            'resolved' => [
                $this->icon = 'check-circle',
                $this->color = 'success',
            ],
            'rejected' => [
                $this->icon = 'x-circle',
                $this->color = 'danger',
            ],
            'pending_confirmation' => [
                $this->icon = 'clock',
                $this->color = 'warning',
            ],
            default => [
                $this->icon = 'bell',
                $this->color = 'primary',
            ],
        };
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->getTitle();
        $message = $this->getMessage();

        return $this->buildData(
            type: "complaint_{$this->status}",
            title: $title,
            message: $message,
            metadata: [
                'complaint_id' => $this->complaint->id,
                'complaint_number' => $this->complaint->complaint_number ?? $this->complaint->id,
                'status' => $this->status,
                'type' => $this->complaint->type,
                'collector_name' => $this->collectorName,
                'rejection_reason' => $this->complaint->rejection_reason,
            ],
            actionUrl: "/complaints/{$this->complaint->id}"
        );
    }

    private function getTitle(): string
    {
        return match ($this->status) {
            'in_progress' => 'Laporan Sedang Diproses',
            'resolved' => 'Laporan Selesai',
            'rejected' => 'Laporan Ditolak',
            'pending_confirmation' => 'Menunggu Konfirmasi Anda',
            default => 'Update Status Laporan',
        };
    }

    private function getMessage(): string
    {
        $complaintNumber = $this->complaint->complaint_number ?? "#{$this->complaint->id}";
        
        return match ($this->status) {
            'in_progress' => $this->collectorName 
                ? "Laporan Anda ({$complaintNumber}) sedang diproses. Collector \"{$this->collectorName}\" telah ditugaskan untuk menangani laporan Anda."
                : "Laporan Anda ({$complaintNumber}) sedang diproses oleh tim kami.",
            'resolved' => "Laporan Anda ({$complaintNumber}) telah diselesaikan. Terima kasih atas laporannya!",
            'rejected' => "Laporan Anda ({$complaintNumber}) ditolak." . ($this->complaint->rejection_reason ? " Alasan: {$this->complaint->rejection_reason}" : ''),
            'pending_confirmation' => "Laporan Anda ({$complaintNumber}) telah ditangani. Silakan konfirmasi apakah masalah sudah terselesaikan.",
            default => "Status laporan Anda ({$complaintNumber}) telah diperbarui.",
        };
    }
}
