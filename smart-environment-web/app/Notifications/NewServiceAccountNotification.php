<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ServiceAccount;

class NewServiceAccountNotification extends Notification
{
    use Queueable;

    protected $serviceAccount;

    /**
     * Create a new notification instance.
     */
    public function __construct(ServiceAccount $serviceAccount)
    {
        $this->serviceAccount = $serviceAccount;
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
                    ->subject('Service Account Baru Ditambahkan')
                    ->line('Service Account baru telah dibuat.')
                    ->line('Nama: ' . $this->serviceAccount->name)
                    ->line('User: ' . $this->serviceAccount->user?->name)
                    ->action('Lihat Detail', url('/admin/service-accounts/' . $this->serviceAccount->id))
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
            'type' => 'new_service_account',
            'title' => 'Service Account Baru',
            'message' => 'Service Account baru "' . $this->serviceAccount->name . '" telah dibuat oleh ' . ($this->serviceAccount->user?->name ?? 'User'),
            'service_account_id' => $this->serviceAccount->id,
            'service_account_name' => $this->serviceAccount->name,
            'user_name' => $this->serviceAccount->user?->name,
            'url' => route('admin.service-accounts.edit', $this->serviceAccount->id),
            'icon' => 'user-plus',
            'color' => 'blue',
        ];
    }
}
