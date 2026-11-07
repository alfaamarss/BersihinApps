<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Rw;

class NewRwNotification extends Notification
{
    use Queueable;

    protected $rw;

    /**
     * Create a new notification instance.
     */
    public function __construct(Rw $rw)
    {
        $this->rw = $rw;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Data RW Baru Ditambahkan')
                    ->line('Data RW baru telah ditambahkan.')
                    ->line('RW: ' . $this->rw->name)
                    ->line('Area: ' . $this->rw->area?->name)
                    ->action('Lihat Detail', url('/admin/rws/' . $this->rw->id))
                    ->line('Terima kasih!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_rw',
            'title' => 'Data RW Baru',
            'message' => 'Data RW baru "' . $this->rw->name . '" telah ditambahkan di area ' . ($this->rw->area?->name ?? '-'),
            'rw_id' => $this->rw->id,
            'rw_name' => $this->rw->name,
            'area_name' => $this->rw->area?->name,
            'url' => route('admin.rws.index'),
            'icon' => 'map-pin',
            'color' => 'green',
        ];
    }
}
