<?php

namespace App\Notifications\Admin;

use App\Models\Rw;
use App\Notifications\BaseNotification;

class NewRwNeedsCollectorNotification extends BaseNotification
{
    protected string $category = 'rw';
    protected string $icon = 'map-pin';
    protected string $color = 'info';

    public function __construct(protected Rw $rw)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'new_rw_needs_collector',
            title: 'RW Baru Membutuhkan Collector',
            message: "User baru mendaftar di RW {$this->rw->name} ({$this->rw->area?->name}). Segera assign collector untuk RW ini.",
            metadata: [
                'rw_id' => $this->rw->id,
                'rw_name' => $this->rw->name,
                'area_id' => $this->rw->area_id,
                'area_name' => $this->rw->area?->name,
            ],
            actionUrl: "/admin/collector-assignments?rw_id={$this->rw->id}"
        );
    }
}
