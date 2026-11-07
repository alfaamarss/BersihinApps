# API Pengambilan Sampah (Waste Delivery) - Mobile

Dokumentasi API untuk fitur pengambilan sampah terjadwal di aplikasi mobile Smart Environment.

## Base URL
```
/api/mobile
```

## Authentication
Semua endpoint memerlukan authentication menggunakan Bearer Token.
```
Authorization: Bearer {token}
```

---

## Resident API (Warga)

### 1. Daftar Pengambilan Sampah
Mendapatkan daftar pengambilan sampah milik user.

**Endpoint:** `GET /resident/waste-deliveries`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter berdasarkan status |
| service_account_id | integer | No | Filter berdasarkan service account |
| start_date | date | No | Filter tanggal mulai (YYYY-MM-DD) |
| end_date | date | No | Filter tanggal akhir (YYYY-MM-DD) |
| per_page | integer | No | Jumlah data per halaman (default: 15) |

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "deliveries": [
      {
        "id": 1,
        "status": "collected",
        "scheduled_at": "2025-12-02T08:00:00+07:00",
        "total_amount": 15000.00,
        "note": null,
        "service_account": {
          "id": 1,
          "name": "Rumah Pak Budi",
          "address": "Jl. Merdeka No. 123",
          "area": {
            "id": 1,
            "name": "Kelurahan Sukamaju"
          }
        },
        "confirmation_status": "pending",
        "collected_at": "2025-12-02T08:30:00+07:00",
        "created_at": "2025-12-01T10:00:00+07:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

---

### 2. Detail Pengambilan Sampah
Mendapatkan detail pengambilan sampah berdasarkan ID.

**Endpoint:** `GET /resident/waste-deliveries/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "collected",
    "scheduled_at": "2025-12-02T08:00:00+07:00",
    "total_amount": 15000.00,
    "note": null,
    "service_account": {
      "id": 1,
      "name": "Rumah Pak Budi",
      "address": "Jl. Merdeka No. 123",
      "contact_phone": "08123456789",
      "area": {
        "id": 1,
        "name": "Kelurahan Sukamaju",
        "level": "kelurahan",
        "parent": {
          "id": 2,
          "name": "Kecamatan Cilandak"
        }
      }
    },
    "items": [
      {
        "id": 1,
        "quantity": 2,
        "unit_price": 5000.00,
        "total_price": 10000.00,
        "waste": {
          "id": 1,
          "category": "organik",
          "code": "ORG001"
        },
        "pocket_size": {
          "id": 1,
          "name": "Kantong Kecil",
          "capacity": 5
        }
      }
    ],
    "invoice": {
      "id": 1,
      "invoice_number": "INV-2025-0001",
      "total_amount": 15000.00,
      "status": "unpaid",
      "issued_at": "2025-12-02T09:00:00+07:00"
    },
    "created_at": "2025-12-01T10:00:00+07:00",
    "updated_at": "2025-12-02T08:30:00+07:00"
  }
}
```

---

### 3. Jadwal Pengambilan Berikutnya
Mendapatkan jadwal pengambilan sampah terdekat.

**Endpoint:** `GET /resident/waste-deliveries/next/pickup`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| service_account_id | integer | No | Filter berdasarkan service account |

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "next_pickup": {
      "id": 5,
      "status": "scheduled",
      "scheduled_at": "2025-12-03T08:00:00+07:00",
      "total_amount": 0.00,
      "service_account": {
        "id": 1,
        "name": "Rumah Pak Budi",
        "address": "Jl. Merdeka No. 123",
        "area": {
          "id": 1,
          "name": "Kelurahan Sukamaju",
          "parent": {
            "name": "Kecamatan Cilandak"
          }
        }
      },
      "collection_status": null
    }
  }
}
```

**Response - No Scheduled Pickup (200):**
```json
{
  "success": true,
  "data": {
    "next_pickup": null,
    "message": "No scheduled pickup found."
  }
}
```

---

### 4. Statistik Pengambilan
Mendapatkan statistik pengambilan sampah.

**Endpoint:** `GET /resident/waste-deliveries/statistics`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| service_account_id | integer | No | Filter berdasarkan service account |

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "statistics": {
      "total_deliveries": 25,
      "completed": 20,
      "scheduled": 3,
      "cancelled": 2,
      "total_earned": 250000.00
    }
  }
}
```

---

### 5. Konfirmasi Pengambilan ⭐ (NEW)
Mengkonfirmasi bahwa sampah sudah diambil oleh petugas.

**Endpoint:** `POST /resident/waste-deliveries/{id}/confirm`

**Catatan:**
- Hanya bisa dikonfirmasi jika status = `collected` dan confirmation_status = `pending`
- Setelah dikonfirmasi, status berubah menjadi `completed`

**Request:**
```
POST /resident/waste-deliveries/1/confirm
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Terima kasih! Pengambilan sampah telah dikonfirmasi.",
  "data": {
    "id": 1,
    "status": "completed",
    "confirmation_status": "confirmed",
    "confirmed_at": "2025-12-02T10:00:00+07:00",
    "completed_at": "2025-12-02T10:00:00+07:00"
  }
}
```

**Response Error - Belum Diambil (400):**
```json
{
  "success": false,
  "message": "Pengambilan belum dilakukan oleh petugas."
}
```

**Response Error - Sudah Dikonfirmasi (400):**
```json
{
  "success": false,
  "message": "Pengambilan sudah dikonfirmasi sebelumnya."
}
```

---

## Collector API (Petugas)

### 1. Daftar Pengambilan Hari Ini
Mendapatkan daftar pengambilan yang harus dilakukan oleh collector.

**Endpoint:** `GET /collector/schedules/deliveries`

**Response:** Lihat dokumentasi Schedule API.

---

### 2. Detail Pengambilan
Mendapatkan detail pengambilan sampah.

**Endpoint:** `GET /collector/deliveries/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "scheduled",
    "scheduled_at": "2025-12-02T08:00:00+07:00",
    "total_amount": 0.00,
    "note": null,
    "service_account": {
      "id": 1,
      "name": "Rumah Pak Budi",
      "address": "Jl. Merdeka No. 123",
      "contact_phone": "08123456789",
      "latitude": -6.123456,
      "longitude": 106.123456,
      "area": {
        "id": 1,
        "name": "Kelurahan Sukamaju",
        "parent": {
          "id": 2,
          "name": "Kecamatan Cilandak"
        }
      },
      "rw": {
        "id": 1,
        "name": "RW 01"
      }
    },
    "items": [...],
    "confirmation_status": "pending",
    "collected_at": null,
    "photo_url": null
  }
}
```

---

### 3. Update Status Pengambilan
Mengupdate status pengambilan sampah.

**Endpoint:** `PATCH /collector/deliveries/{id}/status`

**Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | Yes | Status baru: `on_progress`, `collected`, atau `cancelled` |
| note | string | No | Catatan tambahan (max: 500 karakter) |

**Status Transitions yang Diperbolehkan:**
| From | To |
|------|-----|
| `scheduled` | `on_progress`, `cancelled` |
| `on_progress` | `collected`, `cancelled` |

**Request - Mulai Pengambilan:**
```json
{
  "status": "on_progress"
}
```

**Request - Selesai Mengambil:**
```json
{
  "status": "collected",
  "note": "Sampah diambil 2 kantong"
}
```

**Response Success - Status Collected (200):**
```json
{
  "success": true,
  "message": "Sampah berhasil diambil. Menunggu konfirmasi dari warga.",
  "data": {
    "id": 1,
    "status": "collected",
    "confirmation_status": "pending",
    "scheduled_at": "2025-12-02T08:00:00+07:00",
    "collected_at": "2025-12-02T08:30:00+07:00",
    "note": "Sampah diambil 2 kantong",
    "service_account": {
      "id": 1,
      "name": "Rumah Pak Budi",
      "address": "Jl. Merdeka No. 123"
    }
  }
}
```

**Response Error - Invalid Transition (400):**
```json
{
  "success": false,
  "message": "Cannot change status from 'scheduled' to 'collected'"
}
```

---

## Status Pengambilan

### Status (status)
| Status | Label | Deskripsi |
|--------|-------|-----------|
| `scheduled` | Terjadwal | Pengambilan sudah dijadwalkan |
| `on_progress` | Dalam Perjalanan | Collector sedang menuju lokasi |
| `collected` | Sudah Diambil | Sampah sudah diambil, menunggu konfirmasi warga |
| `completed` | Selesai | Warga sudah mengkonfirmasi pengambilan |
| `cancelled` | Dibatalkan | Pengambilan dibatalkan |
| `skipped` | Dilewati | Pengambilan dilewati |

### Confirmation Status (confirmation_status)
| Status | Label | Deskripsi |
|--------|-------|-----------|
| `pending` | Menunggu | Menunggu konfirmasi dari warga |
| `confirmed` | Dikonfirmasi | Warga sudah mengkonfirmasi |
| `skipped` | Dilewati | Pengambilan dilewati |
| `no_waste` | Tidak Ada Sampah | Tidak ada sampah untuk diambil |

---

## Flow Pengambilan Sampah

```
┌─────────────┐
│  scheduled  │  Jadwal pengambilan dibuat otomatis
└──────┬──────┘
       │
       ▼ Collector mulai perjalanan
┌─────────────┐
│ on_progress │  Collector dalam perjalanan ke lokasi
└──────┬──────┘
       │
       ▼ Collector selesai mengambil sampah
┌─────────────┐
│  collected  │  Sampah sudah diambil, menunggu konfirmasi
│  (pending)  │  confirmation_status = pending
└──────┬──────┘
       │
       ▼ Warga konfirmasi via mobile app
┌─────────────┐
│  completed  │  Pengambilan selesai
│ (confirmed) │  confirmation_status = confirmed
└─────────────┘
```

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Pengambilan belum dilakukan oleh petugas."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "You are not assigned to collect from this area."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Delivery not found."
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "status": ["The status field is required."]
  }
}
```

---

## Contoh Penggunaan (cURL)

### Collector - Update Status ke Collected
```bash
curl -X PATCH "http://localhost:8000/api/mobile/collector/deliveries/1/status" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status": "collected", "note": "Sampah diambil 2 kantong"}'
```

### Resident - Konfirmasi Pengambilan
```bash
curl -X POST "http://localhost:8000/api/mobile/resident/waste-deliveries/1/confirm" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Resident - Cek Jadwal Berikutnya
```bash
curl -X GET "http://localhost:8000/api/mobile/resident/waste-deliveries/next/pickup" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Notifikasi (Recommended Implementation)

Untuk pengalaman pengguna yang lebih baik, disarankan mengirim notifikasi:

1. **Ke Warga** saat `status = collected`:
   - Title: "Sampah Anda Sudah Diambil"
   - Body: "Petugas sudah mengambil sampah Anda. Tap untuk konfirmasi."

2. **Ke Collector** saat warga konfirmasi:
   - Title: "Pengambilan Dikonfirmasi"
   - Body: "Warga sudah mengkonfirmasi pengambilan di {alamat}."
