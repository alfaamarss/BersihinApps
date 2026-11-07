# Panduan Implementasi Fitur Notifikasi

## Daftar Isi
1. [Overview](#overview)
2. [Arsitektur](#arsitektur)
3. [Database Schema](#database-schema)
4. [File yang Sudah Dibuat](#file-yang-sudah-dibuat)
5. [Notification Classes](#notification-classes)
6. [API Endpoints](#api-endpoints)
7. [Command Schedulers](#command-schedulers)
8. [Integrasi dengan Controller](#integrasi-dengan-controller)
9. [Langkah Selanjutnya](#langkah-selanjutnya)

---

## Overview

### Infrastruktur yang Sudah Ada
- ✅ Tabel `notifications` sudah ada (Laravel database notifications)
- ✅ User model sudah menggunakan trait `Notifiable`
- ✅ NotificationController untuk admin sudah ada
- ✅ 3 Notification classes sudah ada: `NewServiceAccountNotification`, `NewRwNotification`, `ServiceAccountDeactivatedNotification`

### Yang Sudah Dibuat
- ✅ `BaseNotification` class - template untuk semua notification
- ✅ `NotificationService` - service untuk mengirim notifikasi
- ✅ 9 User Notification classes
- ✅ 6 Admin Notification classes
- ✅ 2 Collector Notification classes
- ✅ 2 Scheduler commands (schedule reminders & billing reminders)
- ✅ API Controller untuk mobile (`NotificationsController`)
- ✅ API Routes untuk mobile
- ✅ Integrasi notifikasi di `ComplaintController` (resident)
- ✅ Integrasi notifikasi di `OffSchedulePickupController` (resident)
- ✅ Integrasi notifikasi di `ServiceAccountController` (admin)

---

## File yang Sudah Dibuat

```
app/
├── Notifications/
│   ├── BaseNotification.php                    ✅ CREATED
│   ├── User/
│   │   ├── NewServiceAccountCreatedNotification.php    ✅ CREATED
│   │   ├── NewArticleNotification.php                  ✅ CREATED
│   │   ├── ScheduleReminderNotification.php            ✅ CREATED
│   │   ├── BillingReminderNotification.php             ✅ CREATED
│   │   ├── BillingDueTodayNotification.php             ✅ CREATED
│   │   ├── BillingOverdueNotification.php              ✅ CREATED
│   │   ├── ComplaintStatusNotification.php             ✅ CREATED
│   │   ├── PickupRequestStatusNotification.php         ✅ CREATED
│   │   └── ServiceAccountDeactivatedByAdminNotification.php ✅ CREATED
│   ├── Admin/
│   │   ├── UserDeactivatedServiceAccountNotification.php   ✅ CREATED
│   │   ├── NewRwNeedsCollectorNotification.php             ✅ CREATED
│   │   ├── NewComplaintNotification.php                    ✅ CREATED
│   │   ├── NewPickupRequestNotification.php                ✅ CREATED
│   │   ├── CollectorSubmittedProofNotification.php         ✅ CREATED
│   │   └── CollectorCompletedComplaintNotification.php     ✅ CREATED
│   └── Collector/
│       ├── NewPickupTaskNotification.php               ✅ CREATED
│       └── NewComplaintTaskNotification.php            ✅ CREATED
├── Services/
│   └── NotificationService.php                 ✅ CREATED
├── Console/Commands/
│   ├── SendScheduleReminders.php               ✅ CREATED
│   └── SendBillingReminders.php                ✅ CREATED
└── Http/Controllers/API/
    └── NotificationsController.php             ✅ CREATED

routes/
├── console.php                                 ✅ UPDATED (added schedulers)
└── api.php                                     ✅ UPDATED (added notification routes)

---

## Arsitektur

```
┌──────────────────────────────────────────────────────────────┐
│                    TRIGGER SOURCES                            │
├──────────────────────────────────────────────────────────────┤
│  1. Model Events (Observer)     │  2. Scheduler (Cron Jobs)  │
│     - Create/Update/Delete      │     - Daily billing check  │
│     - Status changes            │     - Schedule reminders   │
├──────────────────────────────────────────────────────────────┤
│                    NOTIFICATION SERVICE                       │
│  App\Services\NotificationService                             │
│  - sendToUser()                                               │
│  - sendToAdmin()                                              │
│  - sendToCollector()                                          │
│  - sendBulk()                                                 │
├──────────────────────────────────────────────────────────────┤
│                    NOTIFICATION CLASSES                       │
│  App\Notifications\{Category}\{NotificationName}              │
│  - via(): ['database']                                        │
│  - toArray(): structured data                                 │
├──────────────────────────────────────────────────────────────┤
│                    STORAGE & DELIVERY                         │
│  - Database: notifications table                              │
│  - API: GET /api/notifications                                │
│  - Web: Admin dashboard                                       │
└──────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Tabel `notifications` (Sudah Ada)
```sql
-- Menggunakan Laravel's default notifications table
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,           -- Class name
    notifiable_type VARCHAR(255) NOT NULL, -- App\Models\User
    notifiable_id BIGINT NOT NULL,         -- User ID
    data TEXT NOT NULL,                    -- JSON payload
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX(notifiable_type, notifiable_id)
);
```

### Standard Data Structure (JSON)
```json
{
    "type": "billing_reminder",
    "category": "billing",
    "title": "Pengingat Tagihan",
    "message": "H-3 menuju tenggat pembayaran...",
    "icon": "credit-card",
    "color": "warning",
    "action_url": "/invoices/123",
    "action_type": "navigate",
    "metadata": {
        "invoice_id": 123,
        "amount": 50000,
        "due_date": "2025-01-30"
    }
}
```

---

## Notification Classes

### Struktur Folder
```
app/Notifications/
├── User/
│   ├── NewServiceAccountCreatedNotification.php
│   ├── NewArticleNotification.php
│   ├── ScheduleReminderNotification.php
│   ├── BillingReminderNotification.php
│   ├── BillingDueTodayNotification.php
│   ├── BillingOverdueNotification.php
│   ├── ComplaintApprovedNotification.php
│   ├── ComplaintRejectedNotification.php
│   ├── PickupRequestApprovedNotification.php
│   ├── PickupRequestRejectedNotification.php
│   └── ServiceAccountDeactivatedByAdminNotification.php
├── Admin/
│   ├── UserDeactivatedServiceAccountNotification.php
│   ├── NewRwNeedsCollectorNotification.php
│   ├── NewComplaintNotification.php
│   ├── NewPickupRequestNotification.php
│   ├── CollectorSubmittedPickupProofNotification.php
│   └── CollectorCompletedComplaintNotification.php
├── Collector/
│   ├── NewPickupTaskNotification.php
│   └── NewComplaintTaskNotification.php
└── BaseNotification.php
```

---

## Implementasi Step-by-Step

### Step 1: Buat Base Notification Class

```php
// app/Notifications/BaseNotification.php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    use Queueable;

    protected string $category;
    protected string $icon = 'bell';
    protected string $color = 'primary';

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected function buildData(string $type, string $title, string $message, array $metadata = [], ?string $actionUrl = null): array
    {
        return [
            'type' => $type,
            'category' => $this->category,
            'title' => $title,
            'message' => $message,
            'icon' => $this->icon,
            'color' => $this->color,
            'action_url' => $actionUrl,
            'metadata' => $metadata,
            'created_at' => now()->toISOString(),
        ];
    }
}
```

### Step 2: Buat Notification Service

```php
// app/Services/NotificationService.php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as NotificationClass;

class NotificationService
{
    /**
     * Send notification to specific user
     */
    public function sendToUser(User $user, NotificationClass $notification): void
    {
        $user->notify($notification);
    }

    /**
     * Send notification to all admins
     */
    public function sendToAdmins(NotificationClass $notification): void
    {
        $admins = User::role('admin')->get();
        Notification::send($admins, $notification);
    }

    /**
     * Send notification to specific collector
     */
    public function sendToCollector(User $collector, NotificationClass $notification): void
    {
        $collector->notify($notification);
    }

    /**
     * Send notification to collectors by RW
     */
    public function sendToCollectorsByRw(int $rwId, NotificationClass $notification): void
    {
        $collectors = User::role('collector')
            ->whereHas('collectorAssignments', function ($query) use ($rwId) {
                $query->where('rw_id', $rwId)
                      ->where('start_date', '<=', now())
                      ->where(function ($q) {
                          $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                      });
            })
            ->get();

        Notification::send($collectors, $notification);
    }

    /**
     * Send bulk notifications
     */
    public function sendBulk($users, NotificationClass $notification): void
    {
        Notification::send($users, $notification);
    }
}
```

### Step 3: Buat Notification Classes

#### A. User Notifications

```php
// app/Notifications/User/NewServiceAccountCreatedNotification.php
<?php

namespace App\Notifications\User;

use App\Models\ServiceAccount;
use App\Notifications\BaseNotification;

class NewServiceAccountCreatedNotification extends BaseNotification
{
    protected string $category = 'service_account';
    protected string $icon = 'home';
    protected string $color = 'success';

    public function __construct(protected ServiceAccount $serviceAccount)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'service_account_created',
            title: 'Akun Layanan Berhasil Dibuat',
            message: "Akun layanan \"{$this->serviceAccount->name}\" berhasil didaftarkan. Selamat menggunakan layanan kami!",
            metadata: [
                'service_account_id' => $this->serviceAccount->id,
                'service_account_name' => $this->serviceAccount->name,
            ],
            actionUrl: "/service-accounts/{$this->serviceAccount->id}"
        );
    }
}
```

```php
// app/Notifications/User/NewArticleNotification.php
<?php

namespace App\Notifications\User;

use App\Models\Article;
use App\Notifications\BaseNotification;

class NewArticleNotification extends BaseNotification
{
    protected string $category = 'article';
    protected string $icon = 'newspaper';
    protected string $color = 'info';

    public function __construct(protected Article $article)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'new_article',
            title: 'Artikel Baru',
            message: "Ada artikel baru: \"{$this->article->title}\"",
            metadata: [
                'article_id' => $this->article->id,
                'article_title' => $this->article->title,
                'article_slug' => $this->article->slug,
            ],
            actionUrl: "/articles/{$this->article->slug}"
        );
    }
}
```

```php
// app/Notifications/User/ScheduleReminderNotification.php
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
        return $this->buildData(
            type: 'schedule_reminder',
            title: 'Jadwal Pengambilan Sampah',
            message: "Hari ini ada jadwal pengambilan sampah ({$this->scheduleDayName}). Siapkan sampah Anda di depan rumah.",
            metadata: [
                'schedule_id' => $this->schedule->id,
                'schedule_day' => $this->scheduleDayName,
                'time' => $this->schedule->start_time ?? '07:00',
            ],
            actionUrl: '/schedules'
        );
    }
}
```

```php
// app/Notifications/User/BillingReminderNotification.php
<?php

namespace App\Notifications\User;

use App\Models\Invoice;
use App\Notifications\BaseNotification;

class BillingReminderNotification extends BaseNotification
{
    protected string $category = 'billing';
    protected string $icon = 'credit-card';
    protected string $color = 'warning';

    public function __construct(
        protected Invoice $invoice,
        protected int $daysRemaining
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = match ($this->daysRemaining) {
            3 => "Ayo, ini H-3 menuju tenggat pembayaran. Lakukan pembayaran agar sampah kamu tetap diambil.",
            2 => "H-2 menuju tenggat pembayaran. Segera lakukan pembayaran.",
            1 => "Besok adalah tenggat pembayaran. Segera lakukan pembayaran.",
            default => "Segera lakukan pembayaran tagihan Anda.",
        };

        return $this->buildData(
            type: 'billing_reminder',
            title: "Pengingat Tagihan (H-{$this->daysRemaining})",
            message: $message,
            metadata: [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
                'days_remaining' => $this->daysRemaining,
            ],
            actionUrl: "/invoices/{$this->invoice->id}"
        );
    }
}
```

```php
// app/Notifications/User/BillingDueTodayNotification.php
<?php

namespace App\Notifications\User;

use App\Models\Invoice;
use App\Notifications\BaseNotification;

class BillingDueTodayNotification extends BaseNotification
{
    protected string $category = 'billing';
    protected string $icon = 'alert-circle';
    protected string $color = 'danger';

    public function __construct(protected Invoice $invoice)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'billing_due_today',
            title: 'Tenggat Pembayaran Hari Ini',
            message: "Hari ini tenggat pembayaran. Segera lakukan pembayaran.",
            metadata: [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
            ],
            actionUrl: "/invoices/{$this->invoice->id}"
        );
    }
}
```

```php
// app/Notifications/User/BillingOverdueNotification.php
<?php

namespace App\Notifications\User;

use App\Models\Invoice;
use App\Notifications\BaseNotification;

class BillingOverdueNotification extends BaseNotification
{
    protected string $category = 'billing';
    protected string $icon = 'alert-triangle';
    protected string $color = 'danger';

    public function __construct(protected Invoice $invoice)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'billing_overdue',
            title: 'Tagihan Belum Dibayar',
            message: "Anda belum membayar tagihan. Sampah minggu depan tidak akan diambil sampai Anda membayar.",
            metadata: [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
            ],
            actionUrl: "/invoices/{$this->invoice->id}"
        );
    }
}
```

```php
// app/Notifications/User/ComplaintStatusNotification.php
<?php

namespace App\Notifications\User;

use App\Models\Complaint;
use App\Notifications\BaseNotification;

class ComplaintStatusNotification extends BaseNotification
{
    protected string $category = 'complaint';

    public function __construct(
        protected Complaint $complaint,
        protected string $status // 'approved' or 'rejected'
    ) {
        $this->icon = $status === 'approved' ? 'check-circle' : 'x-circle';
        $this->color = $status === 'approved' ? 'success' : 'danger';
    }

    public function toArray(object $notifiable): array
    {
        $isApproved = $this->status === 'approved';
        
        return $this->buildData(
            type: "complaint_{$this->status}",
            title: $isApproved ? 'Laporan Disetujui' : 'Laporan Ditolak',
            message: $isApproved 
                ? "Laporan Anda telah disetujui dan sedang diproses."
                : "Laporan Anda ditolak. " . ($this->complaint->rejection_reason ?? ''),
            metadata: [
                'complaint_id' => $this->complaint->id,
                'complaint_number' => $this->complaint->complaint_number,
                'status' => $this->status,
                'category' => $this->complaint->category,
            ],
            actionUrl: "/complaints/{$this->complaint->id}"
        );
    }
}
```

```php
// app/Notifications/User/PickupRequestStatusNotification.php
<?php

namespace App\Notifications\User;

use App\Models\WasteDelivery;
use App\Notifications\BaseNotification;

class PickupRequestStatusNotification extends BaseNotification
{
    protected string $category = 'pickup_request';

    public function __construct(
        protected WasteDelivery $wasteDelivery,
        protected string $status // 'approved' or 'rejected'
    ) {
        $this->icon = $status === 'approved' ? 'check-circle' : 'x-circle';
        $this->color = $status === 'approved' ? 'success' : 'danger';
    }

    public function toArray(object $notifiable): array
    {
        $isApproved = $this->status === 'approved';
        
        return $this->buildData(
            type: "pickup_request_{$this->status}",
            title: $isApproved ? 'Request Pengambilan Disetujui' : 'Request Pengambilan Ditolak',
            message: $isApproved 
                ? "Request pengambilan sampah Anda telah disetujui. Collector akan segera datang."
                : "Request pengambilan sampah Anda ditolak. " . ($this->wasteDelivery->rejection_reason ?? ''),
            metadata: [
                'waste_delivery_id' => $this->wasteDelivery->id,
                'status' => $this->status,
                'scheduled_date' => $this->wasteDelivery->scheduled_date?->format('Y-m-d'),
            ],
            actionUrl: "/waste-deliveries/{$this->wasteDelivery->id}"
        );
    }
}
```

```php
// app/Notifications/User/ServiceAccountDeactivatedByAdminNotification.php
<?php

namespace App\Notifications\User;

use App\Models\ServiceAccount;
use App\Notifications\BaseNotification;

class ServiceAccountDeactivatedByAdminNotification extends BaseNotification
{
    protected string $category = 'service_account';
    protected string $icon = 'user-x';
    protected string $color = 'danger';

    public function __construct(
        protected ServiceAccount $serviceAccount,
        protected ?string $reason = null
    ) {
    }

    public function toArray(object $notifiable): array
    {
        $message = "Akun layanan \"{$this->serviceAccount->name}\" telah dinonaktifkan oleh admin.";
        if ($this->reason) {
            $message .= " Alasan: {$this->reason}";
        }

        return $this->buildData(
            type: 'service_account_deactivated_by_admin',
            title: 'Akun Layanan Dinonaktifkan',
            message: $message,
            metadata: [
                'service_account_id' => $this->serviceAccount->id,
                'service_account_name' => $this->serviceAccount->name,
                'reason' => $this->reason,
            ],
            actionUrl: "/service-accounts/{$this->serviceAccount->id}"
        );
    }
}
```

#### B. Admin Notifications

```php
// app/Notifications/Admin/UserDeactivatedServiceAccountNotification.php
<?php

namespace App\Notifications\Admin;

use App\Models\ServiceAccount;
use App\Notifications\BaseNotification;

class UserDeactivatedServiceAccountNotification extends BaseNotification
{
    protected string $category = 'service_account';
    protected string $icon = 'user-minus';
    protected string $color = 'warning';

    public function __construct(protected ServiceAccount $serviceAccount)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'user_deactivated_service_account',
            title: 'User Menonaktifkan Akun Layanan',
            message: "User \"{$this->serviceAccount->user?->name}\" menonaktifkan akun layanan \"{$this->serviceAccount->name}\".",
            metadata: [
                'service_account_id' => $this->serviceAccount->id,
                'service_account_name' => $this->serviceAccount->name,
                'user_id' => $this->serviceAccount->user_id,
                'user_name' => $this->serviceAccount->user?->name,
            ],
            actionUrl: "/admin/service-accounts/{$this->serviceAccount->id}"
        );
    }
}
```

```php
// app/Notifications/Admin/NewRwNeedsCollectorNotification.php
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
```

```php
// app/Notifications/Admin/NewComplaintNotification.php
<?php

namespace App\Notifications\Admin;

use App\Models\Complaint;
use App\Notifications\BaseNotification;

class NewComplaintNotification extends BaseNotification
{
    protected string $category = 'complaint';
    protected string $icon = 'message-square';
    protected string $color = 'warning';

    public function __construct(protected Complaint $complaint)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'new_complaint',
            title: 'Pelaporan Baru',
            message: "Pelaporan baru dari \"{$this->complaint->user?->name}\": {$this->complaint->category}",
            metadata: [
                'complaint_id' => $this->complaint->id,
                'complaint_number' => $this->complaint->complaint_number,
                'category' => $this->complaint->category,
                'user_id' => $this->complaint->user_id,
                'user_name' => $this->complaint->user?->name,
            ],
            actionUrl: "/admin/complaints/{$this->complaint->id}"
        );
    }
}
```

```php
// app/Notifications/Admin/NewPickupRequestNotification.php
<?php

namespace App\Notifications\Admin;

use App\Models\WasteDelivery;
use App\Notifications\BaseNotification;

class NewPickupRequestNotification extends BaseNotification
{
    protected string $category = 'pickup_request';
    protected string $icon = 'package';
    protected string $color = 'info';

    public function __construct(protected WasteDelivery $wasteDelivery)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'new_pickup_request',
            title: 'Request Pengambilan Baru',
            message: "Request pengambilan diluar jadwal dari \"{$this->wasteDelivery->user?->name}\".",
            metadata: [
                'waste_delivery_id' => $this->wasteDelivery->id,
                'user_id' => $this->wasteDelivery->user_id,
                'user_name' => $this->wasteDelivery->user?->name,
                'requested_date' => $this->wasteDelivery->scheduled_date?->format('Y-m-d'),
            ],
            actionUrl: "/admin/waste-deliveries/{$this->wasteDelivery->id}"
        );
    }
}
```

```php
// app/Notifications/Admin/CollectorSubmittedProofNotification.php
<?php

namespace App\Notifications\Admin;

use App\Models\WasteDelivery;
use App\Notifications\BaseNotification;

class CollectorSubmittedProofNotification extends BaseNotification
{
    protected string $category = 'pickup_request';
    protected string $icon = 'camera';
    protected string $color = 'success';

    public function __construct(protected WasteDelivery $wasteDelivery)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'collector_submitted_proof',
            title: 'Bukti Pengambilan Dikirim',
            message: "Collector \"{$this->wasteDelivery->collector?->name}\" telah mengirim bukti pengambilan untuk request dari \"{$this->wasteDelivery->user?->name}\".",
            metadata: [
                'waste_delivery_id' => $this->wasteDelivery->id,
                'collector_id' => $this->wasteDelivery->assigned_collector_id,
                'collector_name' => $this->wasteDelivery->collector?->name,
                'user_name' => $this->wasteDelivery->user?->name,
            ],
            actionUrl: "/admin/waste-deliveries/{$this->wasteDelivery->id}"
        );
    }
}
```

```php
// app/Notifications/Admin/CollectorCompletedComplaintNotification.php
<?php

namespace App\Notifications\Admin;

use App\Models\Complaint;
use App\Notifications\BaseNotification;

class CollectorCompletedComplaintNotification extends BaseNotification
{
    protected string $category = 'complaint';
    protected string $icon = 'check-circle';
    protected string $color = 'success';

    public function __construct(protected Complaint $complaint)
    {
    }

    public function toArray(object $notifiable): array
    {
        $categoryLabel = match($this->complaint->category) {
            'garbage_not_picked' => 'Sampah Tidak Diambil',
            'garbage_piling' => 'Sampah Menumpuk',
            default => $this->complaint->category,
        };

        return $this->buildData(
            type: 'collector_completed_complaint',
            title: 'Laporan Diselesaikan Collector',
            message: "Collector \"{$this->complaint->assignedUser?->name}\" menyelesaikan laporan kategori \"{$categoryLabel}\".",
            metadata: [
                'complaint_id' => $this->complaint->id,
                'complaint_number' => $this->complaint->complaint_number,
                'category' => $this->complaint->category,
                'collector_id' => $this->complaint->assigned_to,
                'collector_name' => $this->complaint->assignedUser?->name,
            ],
            actionUrl: "/admin/complaints/{$this->complaint->id}"
        );
    }
}
```

#### C. Collector Notifications

```php
// app/Notifications/Collector/NewPickupTaskNotification.php
<?php

namespace App\Notifications\Collector;

use App\Models\WasteDelivery;
use App\Notifications\BaseNotification;

class NewPickupTaskNotification extends BaseNotification
{
    protected string $category = 'task';
    protected string $icon = 'truck';
    protected string $color = 'primary';

    public function __construct(protected WasteDelivery $wasteDelivery)
    {
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData(
            type: 'new_pickup_task',
            title: 'Tugas Pengambilan Baru',
            message: "Anda mendapat tugas pengambilan sampah dari \"{$this->wasteDelivery->user?->name}\" di {$this->wasteDelivery->serviceAccount?->address}.",
            metadata: [
                'waste_delivery_id' => $this->wasteDelivery->id,
                'user_name' => $this->wasteDelivery->user?->name,
                'address' => $this->wasteDelivery->serviceAccount?->address,
                'scheduled_date' => $this->wasteDelivery->scheduled_date?->format('Y-m-d'),
            ],
            actionUrl: "/collector/waste-deliveries/{$this->wasteDelivery->id}"
        );
    }
}
```

```php
// app/Notifications/Collector/NewComplaintTaskNotification.php
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
        $categoryLabel = match($this->complaint->category) {
            'garbage_not_picked' => 'Sampah Tidak Diambil',
            'garbage_piling' => 'Sampah Menumpuk',
            default => $this->complaint->category,
        };

        return $this->buildData(
            type: 'new_complaint_task',
            title: 'Tugas Laporan Baru',
            message: "Anda mendapat tugas menangani laporan \"{$categoryLabel}\" dari \"{$this->complaint->user?->name}\".",
            metadata: [
                'complaint_id' => $this->complaint->id,
                'complaint_number' => $this->complaint->complaint_number,
                'category' => $this->complaint->category,
                'user_name' => $this->complaint->user?->name,
                'address' => $this->complaint->address,
            ],
            actionUrl: "/collector/complaints/{$this->complaint->id}"
        );
    }
}
```

---

### Step 4: Buat Command Schedulers

```php
// app/Console/Commands/SendScheduleReminders.php
<?php

namespace App\Console\Commands;

use App\Models\ServiceAccount;
use App\Models\Schedule;
use App\Notifications\User\ScheduleReminderNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendScheduleReminders extends Command
{
    protected $signature = 'notifications:schedule-reminders';
    protected $description = 'Send schedule reminders to users for today pickup';

    public function handle()
    {
        $today = Carbon::now();
        $dayOfWeek = strtolower($today->format('l')); // monday, tuesday, etc.
        $dayName = $this->getDayName($dayOfWeek);

        // Get all schedules for today
        $schedules = Schedule::where($dayOfWeek, true)
            ->where('is_active', true)
            ->with('rw')
            ->get();

        foreach ($schedules as $schedule) {
            // Get all active service accounts in this RW
            $serviceAccounts = ServiceAccount::where('rw_id', $schedule->rw_id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            foreach ($serviceAccounts as $serviceAccount) {
                if ($serviceAccount->user) {
                    // Check if notification already sent today
                    $alreadySent = $serviceAccount->user->notifications()
                        ->whereDate('created_at', $today)
                        ->where('data->type', 'schedule_reminder')
                        ->exists();

                    if (!$alreadySent) {
                        $serviceAccount->user->notify(
                            new ScheduleReminderNotification($schedule, $dayName)
                        );
                    }
                }
            }
        }

        $this->info('Schedule reminders sent successfully.');
    }

    private function getDayName(string $day): string
    {
        return match($day) {
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
            default => $day,
        };
    }
}
```

```php
// app/Console/Commands/SendBillingReminders.php
<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\User\BillingReminderNotification;
use App\Notifications\User\BillingDueTodayNotification;
use App\Notifications\User\BillingOverdueNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendBillingReminders extends Command
{
    protected $signature = 'notifications:billing-reminders';
    protected $description = 'Send billing reminders to users';

    public function handle()
    {
        $today = Carbon::now()->startOfDay();

        // H-3 reminder (due date is in 3 days = 27th if due on 30th)
        $this->sendReminders($today->copy()->addDays(3), 3);

        // H-2 reminder
        $this->sendReminders($today->copy()->addDays(2), 2);

        // H-1 reminder
        $this->sendReminders($today->copy()->addDays(1), 1);

        // Due today (Hari-H)
        $this->sendDueTodayReminders($today);

        // Overdue (after due date)
        $this->sendOverdueReminders($today);

        $this->info('Billing reminders sent successfully.');
    }

    private function sendReminders(Carbon $dueDate, int $daysRemaining): void
    {
        $invoices = Invoice::whereDate('due_date', $dueDate)
            ->where('status', 'unpaid')
            ->with('serviceAccount.user')
            ->get();

        foreach ($invoices as $invoice) {
            $user = $invoice->serviceAccount?->user;
            if ($user) {
                // Check if already sent today
                $alreadySent = $user->notifications()
                    ->whereDate('created_at', Carbon::today())
                    ->where('data->type', 'billing_reminder')
                    ->where('data->metadata->invoice_id', $invoice->id)
                    ->exists();

                if (!$alreadySent) {
                    $user->notify(new BillingReminderNotification($invoice, $daysRemaining));
                }
            }
        }
    }

    private function sendDueTodayReminders(Carbon $today): void
    {
        $invoices = Invoice::whereDate('due_date', $today)
            ->where('status', 'unpaid')
            ->with('serviceAccount.user')
            ->get();

        foreach ($invoices as $invoice) {
            $user = $invoice->serviceAccount?->user;
            if ($user) {
                $alreadySent = $user->notifications()
                    ->whereDate('created_at', $today)
                    ->where('data->type', 'billing_due_today')
                    ->where('data->metadata->invoice_id', $invoice->id)
                    ->exists();

                if (!$alreadySent) {
                    $user->notify(new BillingDueTodayNotification($invoice));
                }
            }
        }
    }

    private function sendOverdueReminders(Carbon $today): void
    {
        // Only send on the 1st of each month for invoices that were due on the 30th
        if ($today->day !== 1) {
            return;
        }

        $invoices = Invoice::whereDate('due_date', '<', $today)
            ->where('status', 'unpaid')
            ->with('serviceAccount.user')
            ->get();

        foreach ($invoices as $invoice) {
            $user = $invoice->serviceAccount?->user;
            if ($user) {
                $alreadySent = $user->notifications()
                    ->whereDate('created_at', $today)
                    ->where('data->type', 'billing_overdue')
                    ->where('data->metadata->invoice_id', $invoice->id)
                    ->exists();

                if (!$alreadySent) {
                    $user->notify(new BillingOverdueNotification($invoice));
                }
            }
        }
    }
}
```

### Step 5: Register Commands in Kernel

```php
// app/Console/Kernel.php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Send schedule reminders every morning at 6 AM
        $schedule->command('notifications:schedule-reminders')
            ->dailyAt('06:00')
            ->withoutOverlapping();

        // Send billing reminders every day at 8 AM
        $schedule->command('notifications:billing-reminders')
            ->dailyAt('08:00')
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
```

---

### Step 6: Buat API Controller untuk Mobile

```php
// app/Http/Controllers/Api/NotificationController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        
        $notifications = $request->user()
            ->notifications()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Get recent unread notifications
     */
    public function unread(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $notifications = $request->user()
            ->unreadNotifications()
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'meta' => [
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function clearRead(Request $request): JsonResponse
    {
        $deleted = $request->user()->readNotifications()->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deleted} read notifications.",
        ]);
    }
}
```

### Step 7: Register API Routes

```php
// routes/api.php (tambahkan routes berikut)

use App\Http\Controllers\Api\NotificationController;

Route::middleware('auth:sanctum')->group(function () {
    // Notification Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
    });
});
```

---

### Step 8: Implementasi Trigger Points

Berikut adalah tempat-tempat di mana notifikasi harus di-trigger:

#### A. Service Account Events

```php
// Modify: app/Http/Controllers/Api/ServiceAccountController.php

use App\Notifications\User\NewServiceAccountCreatedNotification;
use App\Notifications\Admin\NewRwNeedsCollectorNotification;
use App\Services\NotificationService;

// Dalam method store() setelah create service account:
public function store(Request $request)
{
    // ... existing code ...
    
    $serviceAccount = ServiceAccount::create($validated);
    
    // Send notification to user
    $request->user()->notify(new NewServiceAccountCreatedNotification($serviceAccount));
    
    // Check if this is a new RW that needs collector assignment
    $notificationService = app(NotificationService::class);
    $existingAssignment = CollectorAssignment::where('rw_id', $serviceAccount->rw_id)->exists();
    
    if (!$existingAssignment) {
        $notificationService->sendToAdmins(
            new NewRwNeedsCollectorNotification($serviceAccount->rw)
        );
    }
    
    // ... return response ...
}
```

#### B. Complaint Events

```php
// Modify: app/Http/Controllers/Api/ComplaintController.php

use App\Notifications\Admin\NewComplaintNotification;
use App\Notifications\User\ComplaintStatusNotification;
use App\Notifications\Collector\NewComplaintTaskNotification;
use App\Notifications\Admin\CollectorCompletedComplaintNotification;

// Saat user membuat complaint baru:
public function store(Request $request)
{
    // ... create complaint ...
    
    // Notify admins
    $notificationService = app(NotificationService::class);
    $notificationService->sendToAdmins(new NewComplaintNotification($complaint));
}

// Saat admin approve/reject complaint:
public function updateStatus(Request $request, Complaint $complaint)
{
    // ... update status ...
    
    // Notify user
    $complaint->user->notify(
        new ComplaintStatusNotification($complaint, $status) // 'approved' or 'rejected'
    );
    
    // If approved and assigned to collector
    if ($status === 'approved' && $complaint->assigned_to) {
        $collector = User::find($complaint->assigned_to);
        $collector->notify(new NewComplaintTaskNotification($complaint));
    }
}

// Saat collector menyelesaikan complaint:
public function complete(Request $request, Complaint $complaint)
{
    // ... mark as complete ...
    
    // Notify admins for specific categories
    if (in_array($complaint->category, ['garbage_not_picked', 'garbage_piling'])) {
        $notificationService = app(NotificationService::class);
        $notificationService->sendToAdmins(
            new CollectorCompletedComplaintNotification($complaint)
        );
    }
}
```

#### C. Pickup Request Events

```php
// Modify: app/Http/Controllers/Api/WasteDeliveryController.php

use App\Notifications\Admin\NewPickupRequestNotification;
use App\Notifications\User\PickupRequestStatusNotification;
use App\Notifications\Collector\NewPickupTaskNotification;
use App\Notifications\Admin\CollectorSubmittedProofNotification;

// Saat user membuat pickup request:
public function store(Request $request)
{
    // ... create waste delivery ...
    
    // Notify admins
    $notificationService = app(NotificationService::class);
    $notificationService->sendToAdmins(new NewPickupRequestNotification($wasteDelivery));
}

// Saat admin approve/reject:
public function updateStatus(Request $request, WasteDelivery $wasteDelivery)
{
    // ... update status ...
    
    // Notify user
    $wasteDelivery->user->notify(
        new PickupRequestStatusNotification($wasteDelivery, $status)
    );
    
    // If approved and assigned to collector
    if ($status === 'approved' && $wasteDelivery->assigned_collector_id) {
        $collector = User::find($wasteDelivery->assigned_collector_id);
        $collector->notify(new NewPickupTaskNotification($wasteDelivery));
    }
}

// Saat collector submit bukti pengambilan:
public function submitProof(Request $request, WasteDelivery $wasteDelivery)
{
    // ... upload proof ...
    
    // Notify admins
    $notificationService = app(NotificationService::class);
    $notificationService->sendToAdmins(
        new CollectorSubmittedProofNotification($wasteDelivery)
    );
}
```

#### D. Article Events

```php
// Modify: app/Http/Controllers/Admin/ArticleController.php

use App\Notifications\User\NewArticleNotification;
use App\Models\User;

// Saat article di-publish:
public function store(Request $request)
{
    // ... create article ...
    
    if ($article->status === 'published') {
        // Send to all active users
        $users = User::role('user')
            ->where('status', 'active')
            ->get();
        
        Notification::send($users, new NewArticleNotification($article));
    }
}

public function publish(Article $article)
{
    $article->update(['status' => 'published']);
    
    // Send to all active users
    $users = User::role('user')
        ->where('status', 'active')
        ->get();
    
    Notification::send($users, new NewArticleNotification($article));
}
```

---

## API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/notifications` | Get paginated notifications |
| GET | `/api/notifications/unread` | Get recent unread notifications |
| GET | `/api/notifications/unread-count` | Get unread count only |
| POST | `/api/notifications/{id}/read` | Mark single notification as read |
| POST | `/api/notifications/read-all` | Mark all notifications as read |
| DELETE | `/api/notifications/{id}` | Delete single notification |
| DELETE | `/api/notifications/clear-read` | Delete all read notifications |

---

## Response Format Examples

### GET /api/notifications
```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-here",
            "type": "App\\Notifications\\User\\BillingReminderNotification",
            "notifiable_type": "App\\Models\\User",
            "notifiable_id": 1,
            "data": {
                "type": "billing_reminder",
                "category": "billing",
                "title": "Pengingat Tagihan (H-3)",
                "message": "Ayo, ini H-3 menuju tenggat pembayaran...",
                "icon": "credit-card",
                "color": "warning",
                "action_url": "/invoices/123",
                "metadata": {
                    "invoice_id": 123,
                    "amount": 50000,
                    "due_date": "2025-01-30"
                }
            },
            "read_at": null,
            "created_at": "2025-01-27T08:00:00.000000Z",
            "updated_at": "2025-01-27T08:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 20,
        "total": 45
    }
}
```

---

## Checklist Implementasi

### Backend Tasks
- [ ] Buat `BaseNotification` class
- [ ] Buat `NotificationService`
- [ ] Buat semua Notification classes (User, Admin, Collector)
- [ ] Buat `SendScheduleReminders` command
- [ ] Buat `SendBillingReminders` command
- [ ] Register commands di Kernel scheduler
- [ ] Buat `NotificationController` API
- [ ] Register API routes
- [ ] Update controllers yang relevan untuk trigger notifikasi

### Testing
- [ ] Test each notification class
- [ ] Test scheduler commands
- [ ] Test API endpoints
- [ ] Test notification triggers di setiap controller

### Deployment
- [ ] Setup cron job untuk Laravel scheduler
- [ ] Monitor notification logs
