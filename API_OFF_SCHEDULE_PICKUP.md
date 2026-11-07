# API Documentation: Off-Schedule Pickup

## Overview

Off-Schedule Pickup adalah fitur untuk warga yang ingin meminta pengambilan sampah di luar jadwal rutin. Fitur ini mengenakan biaya tambahan (surcharge) yang dapat dikonfigurasi oleh admin.

## Base URL

```
Production: https://api.smartenvironment.id/api/v1
Development: http://localhost:8000/api/v1
```

## Authentication

Semua endpoint membutuhkan Bearer Token:

```http
Authorization: Bearer {token}
```

---

## 📊 Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        OFF-SCHEDULE PICKUP FLOW                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────┐         ┌──────────┐         ┌──────────┐                    │
│  │  WARGA   │         │  ADMIN   │         │ COLLECTOR│                    │
│  └────┬─────┘         └────┬─────┘         └────┬─────┘                    │
│       │                    │                    │                          │
│       │ 1. Lihat Info      │                    │                          │
│       │    Pricing         │                    │                          │
│       │◄──────────────────►│                    │                          │
│       │                    │                    │                          │
│       │ 2. Buat Request    │                    │                          │
│       │────────────────────►                    │                          │
│       │    status: sent    │                    │                          │
│       │                    │                    │                          │
│       │                    │ 3. Assign          │                          │
│       │                    │    Collector       │                          │
│       │                    │────────────────────►                          │
│       │                    │    status:         │                          │
│       │                    │    processing      │                          │
│       │                    │                    │                          │
│       │                    │                    │ 4. Complete              │
│       │                    │                    │    + Input Waste         │
│       │                    │                    │    + Upload Photo        │
│       │◄───────────────────────────────────────┤    status: collected     │
│       │    Notifikasi      │                    │                          │
│       │                    │                    │                          │
│       │ 5. Konfirmasi      │                    │                          │
│       │────────────────────►                    │                          │
│       │    status:         │                    │                          │
│       │    completed       │                    │                          │
│       │    confirmed_at ✓  │                    │                          │
│       │                    │                    │                          │
│       │    ┌─────────────────────────┐         │                          │
│       │    │  INVOICE CREATED        │         │                          │
│       │    │  INV-YYYYMMDD-XXXX     │         │                          │
│       │    └─────────────────────────┘         │                          │
│       │                    │                    │                          │
│       │ 6. Bayar Invoice   │                    │                          │
│       │────────────────────►                    │                          │
│       │    status: paid    │                    │                          │
│       │                    │                    │                          │
│       ▼                    ▼                    ▼                          │
│                        SELESAI                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 🏠 Resident (Warga) API

### 1. Get Pricing Info

Mendapatkan informasi harga surcharge sebelum membuat request.

```http
GET /mobile/resident/off-schedule-pickups/pricing-info
```

**Response:**
```json
{
  "success": true,
  "data": {
    "off_schedule_surcharge": 5000,
    "formatted_surcharge": "Rp 5.000",
    "message": "Biaya tambahan untuk pickup di luar jadwal"
  }
}
```

---

### 2. Create Off-Schedule Pickup Request

Membuat request pickup baru di luar jadwal.

```http
POST /mobile/resident/off-schedule-pickups
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `service_account_id` | integer | ✅ | ID service account (alamat layanan) |
| `requested_pickup_date` | date | ✅ | Tanggal pickup yang diminta (YYYY-MM-DD) |
| `requested_pickup_time` | time | ❌ | Jam pickup yang diminta (HH:mm) |
| `note` | string | ❌ | Catatan dari warga |

**Example Request:**
```json
{
  "service_account_id": 1,
  "requested_pickup_date": "2025-11-26",
  "requested_pickup_time": "09:00",
  "note": "Sampah menumpuk, mohon segera diambil"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Request pickup berhasil dibuat",
  "data": {
    "id": 15,
    "service_account_id": 1,
    "service_account_name": "Ioka Purba",
    "address": "Jl. H. Misbah No.18, Hamdan, Kecamatan Medan Maimun",
    "pickup_type": "request",
    "status": "pending",
    "request_status": "sent",
    "requested_pickup_date": "2025-11-26",
    "requested_pickup_time": "09:00",
    "extra_fee": 5000,
    "note": "Sampah menumpuk, mohon segera diambil",
    "created_at": "2025-11-25T19:22:10.000000Z"
  }
}
```

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "service_account_id": ["Masih ada request yang belum selesai untuk alamat ini"]
  }
}
```

---

### 3. List Off-Schedule Pickup Requests

Mendapatkan daftar semua request pickup.

```http
GET /mobile/resident/off-schedule-pickups
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `pending`, `collected`, `completed` |
| `request_status` | string | Filter: `sent`, `processing`, `pending`, `completed`, `rejected` |
| `page` | integer | Nomor halaman |
| `per_page` | integer | Jumlah per halaman (default: 15) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "service_account": {
        "id": 1,
        "name": "Ioka Purba",
        "address": "Jl. H. Misbah No.18..."
      },
      "pickup_type": "request",
      "status": "pending",
      "request_status": "processing",
      "requested_pickup_date": "2025-11-26",
      "requested_pickup_time": "09:00",
      "extra_fee": 5000,
      "total_amount": 0,
      "assigned_collector": {
        "id": 2,
        "name": "Collector"
      },
      "created_at": "2025-11-25T19:22:10.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 4. Get Off-Schedule Pickup Detail

Mendapatkan detail satu request pickup.

```http
GET /mobile/resident/off-schedule-pickups/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "service_account": {
      "id": 1,
      "name": "Ioka Purba",
      "address": "Jl. H. Misbah No.18, Hamdan, Kecamatan Medan Maimun",
      "latitude": 3.5896,
      "longitude": 98.6731
    },
    "pickup_type": "request",
    "status": "collected",
    "request_status": "pending",
    "confirmation_status": "pending",
    "requested_pickup_date": "2025-11-26",
    "requested_pickup_time": "09:00",
    "collected_at": "2025-11-25T19:27:19.000000Z",
    "bag_count": 3,
    "base_amount": 15000,
    "extra_fee": 5000,
    "total_amount": 20000,
    "photo_url": "https://api.smartenvironment.id/storage/pickups/off_schedule_15_1732563439.jpg",
    "collector_notes": "Sampah sudah diambil, 3 kantong",
    "waste_items": [
      {
        "waste_id": 1,
        "waste_name": "Sampah Organik",
        "pocket_size_id": 1,
        "pocket_size_name": "Besar",
        "quantity": 2,
        "unit_price": 5000,
        "total_price": 10000
      },
      {
        "waste_id": 2,
        "waste_name": "Sampah Anorganik",
        "pocket_size_id": 2,
        "pocket_size_name": "Sedang",
        "quantity": 1,
        "unit_price": 5000,
        "total_price": 5000
      }
    ],
    "assigned_collector": {
      "id": 2,
      "name": "Collector",
      "phone": "081234567890"
    }
  }
}
```

---

### 5. Confirm Pickup

Warga mengkonfirmasi bahwa sampah sudah diambil oleh collector.

```http
POST /mobile/resident/off-schedule-pickups/{id}/confirm
```

**Request Body:** None

**Response:**
```json
{
  "success": true,
  "message": "Pickup berhasil dikonfirmasi",
  "data": {
    "id": 15,
    "status": "completed",
    "request_status": "completed",
    "confirmation_status": "confirmed",
    "confirmed_at": "2025-11-25T19:31:34.000000Z",
    "completed_at": "2025-11-25T19:31:34.000000Z",
    "total_amount": 20000,
    "invoice": {
      "id": 2,
      "invoice_number": "INV-20251125-0002",
      "total_amount": 20000,
      "status": "not_paid",
      "issued_at": "2025-11-25T19:31:34.000000Z"
    }
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Pickup belum selesai diambil oleh collector"
}
```

---

## 🚛 Collector API

### 1. List Assigned Off-Schedule Pickups

Mendapatkan daftar pickup yang ditugaskan ke collector.

```http
GET /mobile/collector/off-schedule-pickups
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter: `pending`, `collected` |
| `date` | date | Filter by pickup date |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "service_account": {
        "id": 1,
        "name": "Ioka Purba",
        "address": "Jl. H. Misbah No.18...",
        "latitude": 3.5896,
        "longitude": 98.6731,
        "contact_phone": "081234567890"
      },
      "pickup_type": "request",
      "status": "pending",
      "request_status": "processing",
      "requested_pickup_date": "2025-11-26",
      "requested_pickup_time": "09:00",
      "resident_note": "Sampah menumpuk, mohon segera diambil"
    }
  ]
}
```

---

### 2. Get Off-Schedule Pickup Detail

Mendapatkan detail pickup beserta informasi harga waste.

```http
GET /mobile/collector/off-schedule-pickups/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "service_account": {
      "id": 1,
      "name": "Ioka Purba",
      "address": "Jl. H. Misbah No.18, Hamdan, Kecamatan Medan Maimun",
      "latitude": 3.5896,
      "longitude": 98.6731,
      "contact_phone": "081234567890"
    },
    "pickup_type": "request",
    "status": "pending",
    "request_status": "processing",
    "requested_pickup_date": "2025-11-26",
    "requested_pickup_time": "09:00",
    "resident_note": "Sampah menumpuk",
    "off_schedule_surcharge": 5000,
    "waste_pricing": [
      {
        "waste_id": 1,
        "waste_name": "Sampah Organik",
        "pocket_size_id": 1,
        "pocket_size_name": "Besar",
        "price": 5000
      },
      {
        "waste_id": 1,
        "waste_name": "Sampah Organik",
        "pocket_size_id": 2,
        "pocket_size_name": "Sedang",
        "price": 3500
      },
      {
        "waste_id": 1,
        "waste_name": "Sampah Organik",
        "pocket_size_id": 3,
        "pocket_size_name": "Kecil",
        "price": 2000
      },
      {
        "waste_id": 2,
        "waste_name": "Sampah Anorganik",
        "pocket_size_id": 1,
        "pocket_size_name": "Besar",
        "price": 7500
      },
      {
        "waste_id": 2,
        "waste_name": "Sampah Anorganik",
        "pocket_size_id": 2,
        "pocket_size_name": "Sedang",
        "price": 5000
      },
      {
        "waste_id": 2,
        "waste_name": "Sampah Anorganik",
        "pocket_size_id": 3,
        "pocket_size_name": "Kecil",
        "price": 3000
      }
    ]
  }
}
```

---

### 3. Complete Off-Schedule Pickup

Collector menyelesaikan pickup dengan input detail sampah dan foto.

```http
PUT /mobile/collector/off-schedule-pickups/{id}/complete
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `photo` | string | ✅ | Base64 encoded image |
| `collector_notes` | string | ❌ | Catatan dari collector |
| `waste_items` | array | ✅ | Array of waste items |
| `waste_items.*.waste_id` | integer | ✅ | ID jenis sampah |
| `waste_items.*.pocket_size_id` | integer | ✅ | ID ukuran kantong |
| `waste_items.*.quantity` | integer | ✅ | Jumlah kantong |

**Example Request:**
```json
{
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD...",
  "collector_notes": "Sampah sudah diambil, kondisi baik",
  "waste_items": [
    {
      "waste_id": 1,
      "pocket_size_id": 1,
      "quantity": 2
    },
    {
      "waste_id": 2,
      "pocket_size_id": 2,
      "quantity": 1
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Pickup berhasil diperbarui dan menunggu konfirmasi warga",
  "data": {
    "id": 15,
    "status": "collected",
    "request_status": "pending",
    "collected_at": "2025-11-25T19:27:19.000000Z",
    "bag_count": 3,
    "base_amount": 15000,
    "extra_fee": 5000,
    "total_amount": 20000,
    "photo_url": "https://api.smartenvironment.id/storage/pickups/off_schedule_15_1732563439.jpg",
    "waste_items": [
      {
        "waste_id": 1,
        "waste_name": "Sampah Organik",
        "pocket_size_name": "Besar",
        "quantity": 2,
        "unit_price": 5000,
        "total_price": 10000
      },
      {
        "waste_id": 2,
        "waste_name": "Sampah Anorganik",
        "pocket_size_name": "Sedang",
        "quantity": 1,
        "unit_price": 5000,
        "total_price": 5000
      }
    ]
  }
}
```

---

### 4. Skip/Reject Off-Schedule Pickup

Collector menolak atau melewati pickup dengan alasan.

```http
PUT /mobile/collector/off-schedule-pickups/{id}/skip
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `reason` | string | ✅ | Alasan skip/tolak |
| `notes` | string | ❌ | Catatan tambahan |

**Example Request:**
```json
{
  "reason": "Alamat tidak ditemukan",
  "notes": "Sudah mencoba menghubungi warga tapi tidak ada respon"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Request dibatalkan oleh kolektor",
  "data": {
    "id": 15,
    "status": "cancelled",
    "request_status": "rejected",
    "rejection_reason": "Alamat tidak ditemukan",
    "collector_notes": "Sudah mencoba menghubungi warga tapi tidak ada respon"
  }
}
```

---

## 💰 Kalkulasi Biaya

### Formula
```
Total Amount = Base Amount + Extra Fee (Surcharge)
```

### Komponen
| Komponen | Deskripsi |
|----------|-----------|
| **Base Amount** | Jumlah dari semua (quantity × unit_price) waste items |
| **Extra Fee** | Biaya tambahan dari `app_settings.off_schedule_surcharge` |

### Contoh Kalkulasi
```
┌──────────────────────────────────────────────────────────────┐
│  WASTE ITEMS                                                 │
├──────────────────────────────────────────────────────────────┤
│  2x Sampah Organik (Besar)     @ Rp 5.000  =  Rp 10.000     │
│  1x Sampah Anorganik (Sedang)  @ Rp 5.000  =  Rp  5.000     │
├──────────────────────────────────────────────────────────────┤
│  Base Amount                               =  Rp 15.000     │
│  Extra Fee (Off-Schedule Surcharge)        =  Rp  5.000     │
├──────────────────────────────────────────────────────────────┤
│  TOTAL AMOUNT                              =  Rp 20.000     │
└──────────────────────────────────────────────────────────────┘
```

---

## 📋 Status Reference

### Delivery Status (`status`)
| Status | Description |
|--------|-------------|
| `pending` | Menunggu diambil |
| `collected` | Sudah diambil collector, menunggu konfirmasi warga |
| `completed` | Selesai dan dikonfirmasi |
| `cancelled` | Dibatalkan |

### Request Status (`request_status`)
| Status | Description |
|--------|-------------|
| `sent` | Request terkirim, menunggu admin assign |
| `processing` | Collector sudah ditugaskan |
| `pending` | Menunggu konfirmasi warga |
| `completed` | Selesai |
| `rejected` | Ditolak |
| `paid` | Invoice sudah dibayar |

### Confirmation Status (`confirmation_status`)
| Status | Description |
|--------|-------------|
| `pending` | Menunggu konfirmasi warga |
| `confirmed` | Warga sudah konfirmasi |

---

## 🗄️ Database Schema

### waste_deliveries (relevant columns)
```sql
id                      BIGINT UNSIGNED
service_account_id      BIGINT UNSIGNED     -- Alamat layanan
schedule_id             BIGINT UNSIGNED     -- NULL untuk off-schedule
pickup_date             DATE
pickup_type             ENUM('scheduled', 'request')  -- 'request' untuk off-schedule
status                  VARCHAR             -- pending, collected, completed, cancelled
request_status          VARCHAR             -- sent, processing, pending, completed, rejected, paid
confirmation_status     VARCHAR             -- pending, confirmed
requested_at            TIMESTAMP
requested_pickup_date   DATE
requested_pickup_time   TIME
requested_by            BIGINT UNSIGNED     -- User ID yang request
assigned_collector_id   BIGINT UNSIGNED
bag_count               INT
base_amount             DECIMAL(12,2)       -- Total harga waste items
extra_fee               DECIMAL(12,2)       -- Off-schedule surcharge
total_amount            DECIMAL(12,2)       -- base_amount + extra_fee
collected_at            TIMESTAMP
completed_at            TIMESTAMP
confirmed_at            TIMESTAMP           -- Waktu warga konfirmasi
photo_url               VARCHAR
collector_notes         TEXT
resident_note           TEXT
rejection_reason        TEXT
```

### waste_delivery_items
```sql
id                  BIGINT UNSIGNED
waste_delivery_id   BIGINT UNSIGNED
waste_id            BIGINT UNSIGNED     -- Jenis sampah
pocket_size_id      BIGINT UNSIGNED     -- Ukuran kantong
quantity            INT
unit_price          DECIMAL(12,2)
total_price         DECIMAL(12,2)
```

### app_settings
```sql
id                      BIGINT UNSIGNED
off_schedule_surcharge  DECIMAL(12,2)   -- Biaya tambahan off-schedule
```

---

## ⚠️ Error Codes

| HTTP Code | Error | Description |
|-----------|-------|-------------|
| 400 | Bad Request | Request tidak valid (misal: konfirmasi sebelum collected) |
| 401 | Unauthorized | Token tidak valid atau expired |
| 403 | Forbidden | Tidak punya akses ke resource |
| 404 | Not Found | Resource tidak ditemukan |
| 422 | Validation Error | Data input tidak valid |
| 500 | Server Error | Error internal server |

---

## 📱 Mobile App Integration Notes

### Untuk Flutter/Dart

```dart
// Model class
class OffSchedulePickup {
  final int id;
  final ServiceAccount serviceAccount;
  final String status;
  final String requestStatus;
  final DateTime? requestedPickupDate;
  final String? requestedPickupTime;
  final double extraFee;
  final double totalAmount;
  final List<WasteItem> wasteItems;
  // ... other fields
}

// Create request
Future<OffSchedulePickup> createOffSchedulePickup({
  required int serviceAccountId,
  required DateTime pickupDate,
  String? pickupTime,
  String? note,
}) async {
  final response = await dio.post(
    '/mobile/resident/off-schedule-pickups',
    data: {
      'service_account_id': serviceAccountId,
      'requested_pickup_date': pickupDate.toIso8601String().split('T')[0],
      'requested_pickup_time': pickupTime,
      'note': note,
    },
  );
  return OffSchedulePickup.fromJson(response.data['data']);
}

// Confirm pickup
Future<OffSchedulePickup> confirmPickup(int id) async {
  final response = await dio.post(
    '/mobile/resident/off-schedule-pickups/$id/confirm',
  );
  return OffSchedulePickup.fromJson(response.data['data']);
}
```

---

## 📝 Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2025-11-25 | Initial release |
| 1.0.1 | 2025-11-25 | Fixed `confirmed_at` not being set on confirmation |
| 1.0.2 | 2025-11-25 | Added `/pricing-info` endpoint |

---

## 🧪 Testing

### Test dengan cURL

```bash
# 1. Get pricing info
curl -X GET "http://localhost:8000/api/v1/mobile/resident/off-schedule-pickups/pricing-info" \
  -H "Authorization: Bearer {token}"

# 2. Create request
curl -X POST "http://localhost:8000/api/v1/mobile/resident/off-schedule-pickups" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "service_account_id": 1,
    "requested_pickup_date": "2025-11-26",
    "requested_pickup_time": "09:00",
    "note": "Test request"
  }'

# 3. Complete pickup (Collector)
curl -X PUT "http://localhost:8000/api/v1/mobile/collector/off-schedule-pickups/15/complete" \
  -H "Authorization: Bearer {collector_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "photo": "base64...",
    "waste_items": [
      {"waste_id": 1, "pocket_size_id": 1, "quantity": 2}
    ]
  }'

# 4. Confirm (Resident)
curl -X POST "http://localhost:8000/api/v1/mobile/resident/off-schedule-pickups/15/confirm" \
  -H "Authorization: Bearer {token}"
```

---

**Last Updated:** November 25, 2025
