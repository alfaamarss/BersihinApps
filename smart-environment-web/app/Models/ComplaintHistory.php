<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'status',
        'description',
        'note',
        'changed_by',
    ];

    /**
     * Status labels in Indonesian
     */
    public const STATUS_LABELS = [
        'open' => 'Status Menunggu',
        'in_progress' => 'Status Dalam Proses',
        'pending_confirmation' => 'Menunggu Konfirmasi Warga',
        'resolved' => 'Status Selesai',
        'rejected' => 'Status Ditolak',
    ];

    /**
     * Status descriptions in Indonesian
     */
    public const STATUS_DESCRIPTIONS = [
        'open' => 'Pengaduan dibuat oleh warga',
        'in_progress' => 'Pengaduan sedang ditangani oleh tim',
        'pending_confirmation' => 'Petugas sudah menyelesaikan, menunggu konfirmasi warga',
        'resolved' => 'Pengaduan telah diselesaikan',
        'rejected' => 'Pengaduan telah ditolak',
    ];

    /**
     * Get the complaint that owns the history.
     */
    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * Get the user who changed the status.
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status description
     */
    public function getStatusDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $baseDescription = self::STATUS_DESCRIPTIONS[$this->status] ?? '';
        
        // Tambahkan note jika ada (misalnya alasan penolakan)
        if ($this->note && $this->status === 'rejected') {
            return $baseDescription . ' dengan alasan ' . $this->note;
        }

        return $baseDescription;
    }

    /**
     * Get icon class based on status
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'open' => 'clock',
            'in_progress' => 'cog',
            'pending_confirmation' => 'question-mark-circle',
            'resolved' => 'check-circle',
            'rejected' => 'x-circle',
            default => 'info-circle',
        };
    }

    /**
     * Get color class based on status
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'blue',
            'in_progress' => 'yellow',
            'pending_confirmation' => 'purple',
            'resolved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
