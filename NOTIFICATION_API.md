# Notification API Documentation

## Base URL
```
/api/v1/mobile/notifications
```

## Authentication
All endpoints require authentication via Bearer token (Sanctum).

---

## Endpoints

### 1. Get All Notifications
Retrieve paginated list of notifications for the authenticated user.

**Endpoint:** `GET /notifications`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | int | 20 | Number of items per page (max: 100) |
| category | string | null | Filter by category (billing, schedule, complaint, etc.) |
| unread_only | bool | false | Only return unread notifications |

**Response:**
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
                "action_type": "navigate",
                "metadata": {
                    "invoice_id": 123,
                    "amount": 50000,
                    "due_date": "2025-01-30"
                },
                "created_at": "2025-01-27T08:00:00.000000Z"
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

### 2. Get Unread Notifications
Retrieve recent unread notifications.

**Endpoint:** `GET /notifications/unread`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | int | 10 | Number of notifications to return (max: 50) |

**Response:**
```json
{
    "success": true,
    "data": [...],
    "meta": {
        "unread_count": 15
    }
}
```

---

### 3. Get Unread Count
Get the count of unread notifications.

**Endpoint:** `GET /notifications/unread-count`

**Response:**
```json
{
    "success": true,
    "data": {
        "unread_count": 15,
        "by_category": {
            "billing": 5,
            "schedule": 3,
            "complaint": 7
        }
    }
}
```

---

### 4. Get Single Notification
Retrieve a specific notification by ID.

**Endpoint:** `GET /notifications/{id}`

**Response:**
```json
{
    "success": true,
    "data": {
        "id": "uuid-here",
        "type": "...",
        "data": {...},
        "read_at": null,
        "created_at": "2025-01-27T08:00:00.000000Z"
    }
}
```

---

### 5. Mark as Read
Mark a single notification as read.

**Endpoint:** `POST /notifications/{id}/read`

**Response:**
```json
{
    "success": true,
    "message": "Notification marked as read.",
    "data": {
        "id": "uuid-here",
        "read_at": "2025-01-27T10:00:00.000000Z",
        ...
    }
}
```

---

### 6. Mark Multiple as Read
Mark multiple notifications as read.

**Endpoint:** `POST /notifications/read-multiple`

**Request Body:**
```json
{
    "ids": [
        "uuid-1",
        "uuid-2",
        "uuid-3"
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "3 notification(s) marked as read."
}
```

---

### 7. Mark All as Read
Mark all notifications as read.

**Endpoint:** `POST /notifications/read-all`

**Response:**
```json
{
    "success": true,
    "message": "All 15 notifications marked as read."
}
```

---

### 8. Delete Notification
Delete a single notification.

**Endpoint:** `DELETE /notifications/{id}`

**Response:**
```json
{
    "success": true,
    "message": "Notification deleted."
}
```

---

### 9. Delete Multiple Notifications
Delete multiple notifications.

**Endpoint:** `POST /notifications/delete-multiple`

**Request Body:**
```json
{
    "ids": [
        "uuid-1",
        "uuid-2"
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "2 notification(s) deleted."
}
```

---

### 10. Clear Read Notifications
Delete all read notifications.

**Endpoint:** `DELETE /notifications/clear-read`

**Response:**
```json
{
    "success": true,
    "message": "Deleted 10 read notifications."
}
```

---

### 11. Get Categories
Get available notification categories.

**Endpoint:** `GET /notifications/categories`

**Response:**
```json
{
    "success": true,
    "data": {
        "service_account": {
            "name": "Akun Layanan",
            "description": "Notifikasi terkait akun layanan"
        },
        "billing": {
            "name": "Tagihan",
            "description": "Notifikasi tagihan dan pembayaran"
        },
        "schedule": {
            "name": "Jadwal",
            "description": "Pengingat jadwal pengambilan sampah"
        },
        "complaint": {
            "name": "Pelaporan",
            "description": "Status laporan/komplain"
        },
        "pickup_request": {
            "name": "Request Pengambilan",
            "description": "Status request pengambilan di luar jadwal"
        },
        "article": {
            "name": "Artikel",
            "description": "Artikel dan berita baru"
        },
        "task": {
            "name": "Tugas",
            "description": "Tugas untuk collector"
        }
    }
}
```

---

## Notification Types

### User Notifications
| Type | Category | Description |
|------|----------|-------------|
| `service_account_created` | service_account | Akun layanan berhasil dibuat |
| `service_account_deactivated_by_admin` | service_account | Akun layanan dinonaktifkan oleh admin |
| `new_article` | article | Artikel baru dipublikasikan |
| `schedule_reminder` | schedule | Pengingat jadwal pengambilan hari ini |
| `billing_reminder` | billing | Pengingat tagihan H-3, H-2, H-1 |
| `billing_due_today` | billing | Tenggat pembayaran hari ini |
| `billing_overdue` | billing | Tagihan sudah lewat tenggat |
| `complaint_approved` | complaint | Laporan disetujui |
| `complaint_rejected` | complaint | Laporan ditolak |
| `pickup_request_approved` | pickup_request | Request pengambilan disetujui |
| `pickup_request_rejected` | pickup_request | Request pengambilan ditolak |

### Admin Notifications
| Type | Category | Description |
|------|----------|-------------|
| `user_deactivated_service_account` | service_account | User menonaktifkan akun layanan |
| `new_rw_needs_collector` | rw | RW baru membutuhkan collector |
| `new_complaint` | complaint | Pelaporan baru dari user |
| `new_pickup_request` | pickup_request | Request pengambilan baru |
| `collector_submitted_proof` | pickup_request | Collector mengirim bukti pengambilan |
| `collector_completed_complaint` | complaint | Collector menyelesaikan laporan |

### Collector Notifications
| Type | Category | Description |
|------|----------|-------------|
| `new_pickup_task` | task | Tugas pengambilan baru |
| `new_complaint_task` | task | Tugas laporan baru |

---

## Icon Reference

Available icons for notification display:
- `bell` - Default notification
- `home` - Service account related
- `user-plus` - New account
- `user-x` - Account deactivated
- `user-minus` - User action
- `credit-card` - Billing related
- `alert-circle` - Warning/attention
- `alert-triangle` - Critical warning
- `truck` - Pickup related
- `package` - Delivery/package
- `message-square` - Complaint/message
- `check-circle` - Success/approved
- `x-circle` - Rejected/failed
- `map-pin` - Location/RW related
- `newspaper` - Article
- `camera` - Photo proof

---

## Color Reference

| Color | Usage |
|-------|-------|
| `primary` | General information |
| `success` | Positive actions (approved, completed) |
| `warning` | Attention needed (reminders) |
| `danger` | Critical/urgent (overdue, rejected) |
| `info` | Informational (new content) |

---

## Error Responses

**404 Not Found:**
```json
{
    "success": false,
    "message": "Notification not found."
}
```

**422 Validation Error:**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "ids": ["The ids field is required."]
    }
}
```

**401 Unauthorized:**
```json
{
    "message": "Unauthenticated."
}
```
