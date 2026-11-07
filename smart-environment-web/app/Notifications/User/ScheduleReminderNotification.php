<?php

namespace App\Notifications\User;

use App\Models\Schedule;
use App\Notifications\BaseNotification;

class ScheduleReminderNotification extends BaseNotification
{
    protected string $category = 'schedule';
    protected string $icon = 'truck';
    protected string $color = 'primary';

    public function __construct(
        protected Schedule $schedule,
        protected string $scheduleDayName
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $time = $this->schedule->start_time ?? '07:00';
        
        return $this->buildData(
            type: 'schedule_reminder',
            title: 'Jadwal Pengambilan Sampah Hari Ini',
            message: "Hari ini ({$this->scheduleDayName}) ada jadwal pengambilan sampah. Siapkan sampah Anda di depan rumah sebelum pukul {$time}.",
            metadata: [
                'schedule_id' => $this->schedule->id,
                'schedule_day' => $this->scheduleDayName,
                'time' => $time,
                'rw_id' => $this->schedule->rw_id,
            ],
            actionUrl: '/schedules'
        );
    }
}
