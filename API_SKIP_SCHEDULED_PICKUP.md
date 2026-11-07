# API Documentation: Off-Schedule Pickup dengan Fitur Skip

## Overview

Fitur ini memungkinkan warga untuk membuat request pickup di luar jadwal dengan opsi untuk men-skip jadwal pickup reguler berikutnya dalam minggu yang sama.

## Base URL

```
/api/v1/mobile/resident/off-schedule-pickups
```

## Authentication

Semua endpoint memerlukan autentikasi Bearer Token.

```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. Get Pricing Info

Mendapatkan informasi biaya tambahan untuk pickup di luar jadwal.

| Method | Endpoint |
|--------|----------|
| GET | `/pricing-info` |

#### Headers

| Header | Value |
|--------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

#### Response Success (200)

```json
{
  "success": true,
  "data": {
    "off_schedule_surcharge": 5000,
    "surcharge_label": "Biaya tambahan pickup di luar jadwal",
    "note": "Biaya ini akan ditambahkan ke total tagihan Anda untuk setiap request pickup di luar jadwal."
  }
}
```

---

### 2. Preview Skip Scheduled

Preview jadwal pickup yang akan di-skip jika opsi `skip_next_scheduled` diaktifkan. Gunakan endpoint ini sebelum membuat request untuk menampilkan konfirmasi kepada user.

| Method | Endpoint |
|--------|----------|
| POST | `/preview-skip` |

#### Headers

| Header | Value |
|--------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `service_account_id` | integer | ✅ | ID service account warga |
| `requested_pickup_date` | date (Y-m-d) | ✅ | Tanggal request pickup |

#### Example Request

```json
{
  "service_account_id": 1,
  "requested_pickup_date": "2025-12-01"
}
```

#### Response Success (200) - Ada pickup yang akan di-skip

```json
{
  "success": true,
  "data": {
    "requested_pickup_date": "2025-12-01",
    "week_end_date": "2025-12-07",
    "scheduled_pickups_to_skip": [
      {
        "id": 6,
        "pickup_date": "2025-12-03",
        "day_name": "Wednesday",
        "schedule_time": "07:00",
        "status": "pending"
      }
    ],
    "total_pickups_to_skip": 1,
    "note": "Jadwal pickup di atas akan di-skip jika Anda memilih opsi skip_next_scheduled."
  }
}
```

#### Response Success (200) - Tidak ada pickup yang akan di-skip

```json
{
  "success": true,
  "data": {
    "requested_pickup_date": "2025-12-06",
    "week_end_date": "2025-12-07",
    "scheduled_pickups_to_skip": [],
    "total_pickups_to_skip": 0,
    "note": "Tidak ada jadwal pickup yang akan di-skip dalam minggu ini."
  }
}
```

---

### 3. Create Off-Schedule Pickup Request

Membuat request pickup di luar jadwal dengan opsi untuk skip jadwal pickup berikutnya dalam minggu yang sama.

| Method | Endpoint |
|--------|----------|
| POST | `/` |

#### Headers

| Header | Value |
|--------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `service_account_id` | integer | ✅ | ID service account warga |
| `requested_pickup_date` | date (Y-m-d) | ✅ | Tanggal request pickup (minimal hari ini) |
| `requested_pickup_time` | time (H:i) | ❌ | Waktu request pickup |
| `resident_note` | string | ❌ | Catatan dari warga |
| `skip_next_scheduled` | boolean | ❌ | Jika `true`, jadwal pickup berikutnya dalam minggu yang sama akan di-skip. Default: `false` |

#### Example Request - Dengan Skip

```json
{
  "service_account_id": 1,
  "requested_pickup_date": "2025-12-02",
  "requested_pickup_time": "09:00",
  "resident_note": "Sampah menumpuk, butuh diambil segera",
  "skip_next_scheduled": true
}
```

#### Response Success (201) - Dengan Skip

```json
{
  "success": true,
  "message": "Request pengambilan berhasil dikirim. Jadwal pickup berikutnya dalam minggu ini telah di-skip.",
  "data": {
    "id": 11,
    "service_account_id": 1,
    "service_account_name": "Warga A",
    "address": "Jl. Contoh No. 123",
    "pickup_type": "request",
    "request_status": "sent",
    "status": "pending",
    "bag_count": 0,
    "unit_price": 0,
    "base_amount": 0,
    "extra_fee": 0,
    "total_amount": 0,
    "requested_at": "2025-12-01T12:31:21+07:00",
    "requested_pickup_date": "2025-12-02",
    "requested_pickup_time": "09:00",
    "assigned_collector": null,
    "rejection_reason": null,
    "photo_url": null,
    "invoice": null
  },
  "skipped_pickups": [
    {
      "id": 6,
      "pickup_date": "2025-12-03",
      "day_name": "Wednesday"
    }
  ]
}
```

#### Example Request - Tanpa Skip

```json
{
  "service_account_id": 1,
  "requested_pickup_date": "2025-12-05",
  "requested_pickup_time": "10:00",
  "resident_note": "Request biasa",
  "skip_next_scheduled": false
}
```

#### Response Success (201) - Tanpa Skip

```json
{
  "success": true,
  "message": "Request pengambilan berhasil dikirim",
  "data": {
    "id": 12,
    "service_account_id": 1,
    "service_account_name": "Warga A",
    "address": "Jl. Contoh No. 123",
    "pickup_type": "request",
    "request_status": "sent",
    "status": "pending",
    "bag_count": 0,
    "unit_price": 0,
    "base_amount": 0,
    "extra_fee": 0,
    "total_amount": 0,
    "requested_at": "2025-12-01T12:32:10+07:00",
    "requested_pickup_date": "2025-12-05",
    "requested_pickup_time": "10:00",
    "assigned_collector": null,
    "rejection_reason": null,
    "photo_url": null,
    "invoice": null
  }
}
```

---

## Error Responses

### Validation Error (422)

**Service account tidak ditemukan:**
```json
{
  "success": false,
  "message": "Service account tidak ditemukan atau bukan milik Anda."
}
```

**Masih ada request yang belum selesai:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "service_account_id": [
      "Masih ada pengajuan pickup di luar jadwal yang belum selesai atau dibayar."
    ]
  }
}
```

**Tanggal tidak valid:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "requested_pickup_date": [
      "The requested pickup date field must be a date after or equal to today."
    ]
  }
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

---

## User Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    User Flow Diagram                         │
└─────────────────────────────────────────────────────────────┘

1. User buka form request pickup di luar jadwal
                    │
                    ▼
2. User pilih tanggal pickup
                    │
                    ▼
3. App panggil POST /preview-skip
   untuk cek jadwal yang akan di-skip
                    │
                    ▼
    ┌───────────────────────────────┐
    │  Ada jadwal yang akan di-skip? │
    └───────────────────────────────┘
           │                │
          Ya               Tidak
           │                │
           ▼                ▼
4a. Tampilkan dialog    4b. Lanjut submit
    konfirmasi skip         tanpa opsi skip
           │
           ▼
    ┌─────────────────────┐
    │ User pilih skip?     │
    └─────────────────────┘
           │         │
          Ya        Tidak
           │         │
           ▼         ▼
5a. Submit dengan    5b. Submit dengan
    skip_next_scheduled:   skip_next_scheduled:
    true                   false
           │         │
           └────┬────┘
                ▼
6. App panggil POST / untuk create request
                │
                ▼
7. Request berhasil dibuat
   (+ jadwal di-skip jika dipilih)
```

---

## Catatan Penting

### Scope Skip
- Hanya pickup **setelah** tanggal request **sampai akhir minggu** (Minggu) yang akan di-skip
- Pickup pada tanggal yang sama dengan request **tidak** akan di-skip

### Status Skipped
Pickup yang di-skip akan memiliki:
- `status: "skipped"`
- `confirmation_status: "skipped"`
- Catatan otomatis di `resident_note`: "Di-skip karena request pickup di luar jadwal pada DD/MM/YYYY"

### Batasan
- User hanya bisa memiliki **1 request off-schedule yang aktif** (belum selesai/dibayar) per service account
- Tanggal request minimal adalah hari ini (`after_or_equal:today`)

---

## Testing dengan cURL

### 1. Get Pricing Info
```bash
curl -X GET "http://localhost:8000/api/v1/mobile/resident/off-schedule-pickups/pricing-info" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 2. Preview Skip
```bash
curl -X POST "http://localhost:8000/api/v1/mobile/resident/off-schedule-pickups/preview-skip" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"service_account_id": 1, "requested_pickup_date": "2025-12-01"}'
```

### 3. Create Request dengan Skip
```bash
curl -X POST "http://localhost:8000/api/v1/mobile/resident/off-schedule-pickups" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "service_account_id": 1,
    "requested_pickup_date": "2025-12-02",
    "requested_pickup_time": "09:00",
    "resident_note": "Sampah menumpuk",
    "skip_next_scheduled": true
  }'
```

---

## Changelog

| Tanggal | Versi | Perubahan |
|---------|-------|-----------|
| 2025-12-01 | 1.0.0 | Initial release - Fitur skip scheduled pickup pada off-schedule request |
