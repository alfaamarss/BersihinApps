<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_account_id',
        'assignee_id',
        'type',
        'description',
        'address',
        'latitude',
        'longitude',
        'resolution_photo',
        'resolution_description',
        'rejection_reason',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        // Delete all photos when complaint is deleted
        static::deleting(function ($complaint) {
            $complaint->photos()->delete();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceAccount()
    {
        return $this->belongsTo(ServiceAccount::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the photos for the complaint.
     */
    public function photos()
    {
        return $this->hasMany(ComplaintPhoto::class)->orderBy('order');
    }

    /**
     * Get the status histories for the complaint.
     */
    public function histories()
    {
        return $this->hasMany(ComplaintHistory::class)->orderBy('created_at', 'asc');
    }

    /**
     * Add a status history record.
     */
    public function addHistory(string $status, ?string $description = null, ?string $note = null, ?int $changedBy = null): ComplaintHistory
    {
        return $this->histories()->create([
            'status' => $status,
            'description' => $description,
            'note' => $note,
            'changed_by' => $changedBy ?? auth()->id(),
        ]);
    }
}
