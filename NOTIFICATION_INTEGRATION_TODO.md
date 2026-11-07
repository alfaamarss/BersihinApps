# Langkah-langkah Integrasi Notifikasi di Controller

Berikut adalah daftar controller yang perlu diupdate untuk menambahkan trigger notifikasi:

## ✅ SUDAH DIINTEGRASIKAN

### a. ComplaintController (Admin)
**File:** `app/Http/Controllers/Admin/ComplaintController.php` ✅

**Trigger Points:**
- ✅ Ketika admin assign collector → Notifikasi ke User (laporan sedang diproses + nama collector)
- ✅ Ketika admin assign collector → Notifikasi ke Collector (tugas baru)
- ✅ Ketika admin ubah status ke rejected → Notifikasi ke User
- ✅ Ketika admin ubah status ke resolved → Notifikasi ke User
- ✅ Ketika admin ubah status ke pending_confirmation → Notifikasi ke User

### b. ComplaintController (Resident Mobile)
**File:** `app/Http/Controllers/API/Mobile/Resident/ComplaintController.php` ✅

**Trigger Points:**
- ✅ Ketika user buat complaint baru → Notifikasi ke Admin

### c. OffSchedulePickupController (Resident Mobile)
**File:** `app/Http/Controllers/API/Mobile/Resident/OffSchedulePickupController.php` ✅

**Trigger Points:**
- ✅ Ketika user request pickup di luar jadwal → Notifikasi ke Admin

### d. ServiceAccountController (Admin)
**File:** `app/Http/Controllers/Admin/ServiceAccountController.php` ✅

**Trigger Points:**
- ✅ Ketika admin nonaktifkan akun layanan → Notifikasi ke User

---

## 📋 BELUM DIINTEGRASIKAN

### a. OffSchedulePickupController (Admin)
**File:** `app/Http/Controllers/Admin/OffSchedulePickupController.php`

**Trigger Points:**
- Ketika admin approve request → Notifikasi ke User (`PickupRequestStatusNotification` approved)
- Ketika admin reject request → Notifikasi ke User (`PickupRequestStatusNotification` rejected)
- Ketika admin assign collector → Notifikasi ke Collector (`NewPickupTaskNotification`)

**Contoh Kode:**
```php
use App\Notifications\User\PickupRequestStatusNotification;
use App\Notifications\Collector\NewPickupTaskNotification;

// Ketika approve & assign:
$wasteDelivery->user->notify(new PickupRequestStatusNotification($wasteDelivery, 'approved'));

if ($wasteDelivery->assigned_collector_id) {
    $collector = User::find($wasteDelivery->assigned_collector_id);
    $collector->notify(new NewPickupTaskNotification($wasteDelivery));
}
```

### c. ArticleController (Admin)
**File:** `app/Http/Controllers/Admin/ArticleController.php`

**Trigger Points:**
- Ketika artikel dipublish → Notifikasi ke semua Users (`NewArticleNotification`)

**Contoh Kode:**
```php
use App\Notifications\User\NewArticleNotification;
use App\Services\NotificationService;

// Ketika publish artikel:
if ($article->status === 'published') {
    $notificationService = app(NotificationService::class);
    $notificationService->sendToAllUsers(new NewArticleNotification($article));
}
```

---

## 2. Collector Controllers (Mobile)

### a. ComplaintController (Collector)
**File:** `app/Http/Controllers/API/Mobile/Collector/ComplaintController.php`

**Trigger Points:**
- Ketika collector complete complaint (sampah tidak diambil/menumpuk) → Notifikasi ke Admin (`CollectorCompletedComplaintNotification`)

**Contoh Kode:**
```php
use App\Notifications\Admin\CollectorCompletedComplaintNotification;
use App\Services\NotificationService;

// Ketika complete complaint:
if (in_array($complaint->type, ['garbage_not_picked', 'garbage_piling'])) {
    $notificationService = app(NotificationService::class);
    $notificationService->sendToAdmins(new CollectorCompletedComplaintNotification($complaint));
}
```

### b. OffSchedulePickupController (Collector)
**File:** `app/Http/Controllers/API/Mobile/Collector/OffSchedulePickupController.php`

**Trigger Points:**
- Ketika collector submit bukti pengambilan → Notifikasi ke Admin (`CollectorSubmittedProofNotification`)

**Contoh Kode:**
```php
use App\Notifications\Admin\CollectorSubmittedProofNotification;
use App\Services\NotificationService;

// Ketika complete dengan bukti:
$notificationService = app(NotificationService::class);
$notificationService->sendToAdmins(new CollectorSubmittedProofNotification($wasteDelivery));
```

---

## 3. Mobile Service Account Controller

### a. ServiceAccountsController (Mobile)
**File:** `app/Http/Controllers/API/Mobile/ServiceAccountsController.php`

**Trigger Points:**
- Ketika user buat akun layanan baru → Notifikasi ke User (`NewServiceAccountCreatedNotification`)
- Jika RW baru dan belum ada collector → Notifikasi ke Admin (`NewRwNeedsCollectorNotification`)
- Ketika user nonaktifkan akun layanan → Notifikasi ke Admin (`UserDeactivatedServiceAccountNotification`)

**Contoh Kode:**
```php
use App\Notifications\User\NewServiceAccountCreatedNotification;
use App\Notifications\Admin\NewRwNeedsCollectorNotification;
use App\Notifications\Admin\UserDeactivatedServiceAccountNotification;
use App\Services\NotificationService;

// Ketika create:
$request->user()->notify(new NewServiceAccountCreatedNotification($serviceAccount));

// Check RW baru:
$hasCollector = CollectorAssignment::where('rw_id', $serviceAccount->rw_id)
    ->where(function($q) {
        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
    })
    ->exists();

if (!$hasCollector) {
    $notificationService = app(NotificationService::class);
    $notificationService->sendToAdmins(new NewRwNeedsCollectorNotification($serviceAccount->rw));
}

// Ketika deactivate:
if ($wasActive && $nowInactive) {
    $notificationService = app(NotificationService::class);
    $notificationService->sendToAdmins(new UserDeactivatedServiceAccountNotification($serviceAccount));
}
```

---

## Testing Commands

Untuk test scheduler commands:

```bash
# Test schedule reminders
php artisan notifications:schedule-reminders

# Test billing reminders
php artisan notifications:billing-reminders

# Force send (ignore already sent today)
php artisan notifications:billing-reminders --force
```

## Cron Setup

Pastikan Laravel scheduler sudah dijadwalkan di crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Catatan Penting

1. **Logging:** Semua notifikasi yang gagal dikirim akan di-log ke Laravel log
2. **Duplicate Prevention:** Scheduler commands sudah include pengecekan duplikat
3. **Performance:** Gunakan `Notification::send()` untuk bulk notifications
4. **Testing:** Test setiap notification class secara individual sebelum integrasi
