<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ComplaintPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'photo_url',
        'order',
    ];

    protected static function boot()
    {
        parent::boot();

        // Delete photo file when record is deleted
        static::deleting(function ($photo) {
            if ($photo->photo_url && Storage::disk('public')->exists($photo->photo_url)) {
                Storage::disk('public')->delete($photo->photo_url);
            }
        });
    }

    /**
     * Get the full URL for the photo.
     */
    public function getPhotoUrlFullAttribute(): string
    {
        return $this->photo_url ? Storage::url($this->photo_url) : '';
    }

    /**
     * Get the complaint that owns the photo.
     */
    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }
}