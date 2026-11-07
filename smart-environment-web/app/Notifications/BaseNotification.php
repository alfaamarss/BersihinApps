<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    use Queueable;

    protected string $category = 'general';
    protected string $icon = 'bell';
    protected string $color = 'primary';

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Build the notification data array with consistent structure.
     */
    protected function buildData(
        string $type,
        string $title,
        string $message,
        array $metadata = [],
        ?string $actionUrl = null,
        ?string $actionType = 'navigate'
    ): array {
        return [
            'type' => $type,
            'category' => $this->category,
            'title' => $title,
            'message' => $message,
            'icon' => $this->icon,
            'color' => $this->color,
            'action_url' => $actionUrl,
            'action_type' => $actionType,
            'metadata' => $metadata,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Get icon name based on notification type.
     */
    protected function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Get color based on notification type.
     */
    protected function getColor(): string
    {
        return $this->color;
    }
}
