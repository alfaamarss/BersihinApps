# API Riwayat Setor Kolektor (TPS Deposits)

Dokumentasi API untuk fitur riwayat penyetoran sampah oleh kolektor ke TPS (Tempat Pembuangan Sementara) di aplikasi Smart Environment.

## Overview

Fitur ini mencatat aktivitas kolektor saat menyetor sampah hasil pengumpulan ke TPS. Setiap kali kolektor melakukan setor, sistem akan mencatat:
- Waktu penyetoran
- TPS tujuan
- Lokasi GPS saat setor
- Catatan tambahan (opsional)

---

## Database Schema

### Tabel: `tps_deposits`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| collector_id | bigint | Foreign key ke users (kolektor) |
| garbage_dump_id | bigint | Foreign key ke garbage_dumps (TPS) |
| deposited_at | datetime | Waktu penyetoran |
| latitude | decimal(10,8) | Koordinat latitude lokasi setor |
| longitude | decimal(11,8) | Koordinat longitude lokasi setor |
| notes | text (nullable) | Catatan tambahan |
| created_at | timestamp | Waktu record dibuat |
| updated_at | timestamp | Waktu record diupdate |

---

## Web Admin API

### Base URL
```
/admin
```

### Authentication
Memerlukan login sebagai admin dengan role `admin`.

---

### 1. Daftar Riwayat Setor Kolektor

Mendapatkan daftar riwayat penyetoran sampah oleh kolektor ke TPS.

**Endpoint:** `GET /admin/tps-deposits`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| collector_id | integer | No | Filter berdasarkan ID kolektor |
| garbage_dump_id | integer | No | Filter berdasarkan ID TPS |
| start_date | date | No | Filter tanggal mulai (YYYY-MM-DD) |
| end_date | date | No | Filter tanggal akhir (YYYY-MM-DD) |

**Response:** HTML View dengan data terfilter

**Contoh Request:**
```
GET /admin/tps-deposits?collector_id=5&start_date=2025-12-01&end_date=2025-12-03
```

---

## Mobile Collector API

### Base URL
```
{{base_url}}/api/v1/mobile/collector
```

Contoh: `http://127.0.0.1:8000/api/v1/mobile/collector`

### Authentication
Semua endpoint memerlukan authentication menggunakan Bearer Token.
```
Authorization: Bearer {token}
```

---

### 1. Daftar TPS yang Di-assign

Mendapatkan daftar TPS yang aktif dan di-assign ke collector yang sedang login.

> **Catatan:** API ini hanya menampilkan TPS yang sudah di-assign ke collector melalui menu admin. Jika collector belum di-assign ke TPS manapun, response akan mengembalikan array kosong.

**Endpoint:** `GET /api/v1/mobile/collector/tps`

**Full URL:** `{{base_url}}/api/v1/mobile/collector/tps`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Daftar TPS berhasil diambil",
  "data": {
    "tps_list": [
      {
        "id": 1,
        "name": "TPS Sukamaju",
        "address": "Jl. Raya Sukamaju No. 10",
        "latitude": -6.2345678,
        "longitude": 106.8765432,
        "capacity_volume": 100.00,
        "capacity_weight": 5000.00,
        "status": "active",
        "kecamatan": {
          "id": 1,
          "name": "Kecamatan Cilandak"
        },
        "kelurahan": {
          "id": 5,
          "name": "Kelurahan Sukamaju"
        },
        "rw": {
          "id": 10,
          "name": "RW 05"
        }
      }
    ]
  }
}
```

**Response Empty (200):**
```json
{
  "success": true,
  "message": "Daftar TPS berhasil diambil",
  "data": {
    "tps_list": []
  }
}
```

---

### 2. Submit Setor ke TPS

Mencatat aktivitas setor sampah ke TPS oleh kolektor.

> **Catatan:** Collector hanya bisa setor ke TPS yang sudah di-assign ke mereka. Jika mencoba setor ke TPS yang tidak di-assign, akan mendapatkan error 403.

**Endpoint:** `POST /api/v1/mobile/collector/tps-deposits`

**Full URL:** `{{base_url}}/api/v1/mobile/collector/tps-deposits`

**Request Body:**
```json
{
  "garbage_dump_id": 1,
  "latitude": -6.2345678,
  "longitude": 106.8765432,
  "notes": "Setor sampah organik dan anorganik"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| garbage_dump_id | integer | Yes | ID TPS tujuan (harus TPS yang di-assign ke collector) |
| latitude | decimal | Yes | Koordinat latitude lokasi setor |
| longitude | decimal | Yes | Koordinat longitude lokasi setor |
| notes | string | No | Catatan tambahan |

**Error Response (403 - Not Assigned):**
```json
{
  "success": false,
  "message": "Anda tidak di-assign ke TPS ini",
  "errors": {
    "garbage_dump_id": ["Anda tidak memiliki izin untuk setor ke TPS ini"]
  }
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Setor ke TPS berhasil dicatat",
  "data": {
    "id": 1,
    "collector_id": 5,
    "garbage_dump_id": 1,
    "deposited_at": "2025-12-03T10:30:00+07:00",
    "latitude": -6.2345678,
    "longitude": 106.8765432,
    "notes": "Setor sampah organik dan anorganik",
    "garbage_dump": {
      "id": 1,
      "name": "TPS Sukamaju",
      "address": "Jl. Raya Sukamaju No. 10"
    }
  }
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "garbage_dump_id": ["TPS tidak ditemukan atau tidak aktif"],
    "latitude": ["Latitude harus berupa angka"],
    "longitude": ["Longitude harus berupa angka"]
  }
}
```

---

### 3. Riwayat Setor Kolektor (Self)

Mendapatkan riwayat setor sampah milik kolektor yang sedang login.

**Endpoint:** `GET /api/v1/mobile/collector/tps-deposits`

**Full URL:** `{{base_url}}/api/v1/mobile/collector/tps-deposits`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| garbage_dump_id | integer | No | Filter berdasarkan ID TPS |
| start_date | date | No | Filter tanggal mulai (YYYY-MM-DD) |
| end_date | date | No | Filter tanggal akhir (YYYY-MM-DD) |
| per_page | integer | No | Jumlah data per halaman (default: 15) |

**Response Success (200):**
```json
{
  "success": true,
  "message": "Riwayat setor berhasil diambil",
  "data": {
    "deposits": [
      {
        "id": 1,
        "deposited_at": "2025-12-03T10:30:00+07:00",
        "latitude": -6.2345678,
        "longitude": 106.8765432,
        "notes": "Setor sampah organik dan anorganik",
        "garbage_dump": {
          "id": 1,
          "name": "TPS Sukamaju",
          "address": "Jl. Raya Sukamaju No. 10"
        }
      },
      {
        "id": 2,
        "deposited_at": "2025-12-02T15:00:00+07:00",
        "latitude": -6.2345678,
        "longitude": 106.8765432,
        "notes": null,
        "garbage_dump": {
          "id": 2,
          "name": "TPS Cilandak",
          "address": "Jl. Cilandak Raya No. 5"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 2
    }
  }
}
```

---

### 4. Detail Setor

Mendapatkan detail record setor berdasarkan ID.

**Endpoint:** `GET /api/v1/mobile/collector/tps-deposits/{id}`

**Full URL:** `{{base_url}}/api/v1/mobile/collector/tps-deposits/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Detail setor berhasil diambil",
  "data": {
    "id": 1,
    "collector_id": 5,
    "deposited_at": "2025-12-03T10:30:00+07:00",
    "latitude": -6.2345678,
    "longitude": 106.8765432,
    "notes": "Setor sampah organik dan anorganik",
    "garbage_dump": {
      "id": 1,
      "name": "TPS Sukamaju",
      "address": "Jl. Raya Sukamaju No. 10",
      "latitude": -6.2340000,
      "longitude": 106.8760000,
      "kecamatan": {
        "id": 1,
        "name": "Kecamatan Cilandak"
      },
      "kelurahan": {
        "id": 5,
        "name": "Kelurahan Sukamaju"
      }
    },
    "created_at": "2025-12-03T10:30:00+07:00"
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Data setor tidak ditemukan"
}
```

---

## Error Codes

| HTTP Code | Description |
|-----------|-------------|
| 200 | Request berhasil |
| 201 | Resource berhasil dibuat |
| 400 | Bad Request - Parameter tidak valid |
| 401 | Unauthorized - Token tidak valid atau expired |
| 403 | Forbidden - Tidak memiliki akses |
| 404 | Not Found - Resource tidak ditemukan |
| 422 | Unprocessable Entity - Validasi gagal |
| 500 | Internal Server Error |

---

## Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLOW SETOR KE TPS                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────────┐ │
│  │   Kolektor   │────▶│  Ambil List  │────▶│  Pilih TPS       │ │
│  │   (Mobile)   │     │  TPS Aktif   │     │  Tujuan          │ │
│  └──────────────┘     └──────────────┘     └────────┬─────────┘ │
│                                                      │           │
│                                                      ▼           │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────────┐ │
│  │   Record     │◀────│   Submit     │◀────│  Ambil Lokasi    │ │
│  │   Tersimpan  │     │   Setor      │     │  GPS             │ │
│  └──────────────┘     └──────────────┘     └──────────────────┘ │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                      ADMIN WEB                            │   │
│  │   - Lihat semua riwayat setor                             │   │
│  │   - Filter berdasarkan kolektor, TPS, tanggal             │   │
│  │   - Monitoring aktivitas kolektor                         │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Notes untuk Tim Mobile

1. **GPS Location**: Pastikan aplikasi mobile meminta permission GPS sebelum submit setor
2. **Validasi TPS**: Hanya TPS dengan status `active` yang dapat dipilih sebagai tujuan
3. **Timestamp**: Field `deposited_at` akan diisi otomatis oleh server dengan waktu saat request diterima
4. **Offline Support**: Pertimbangkan untuk menyimpan data setor secara lokal jika tidak ada koneksi internet, kemudian sync saat online

---

## Implementation Status

| Feature | Web Admin | Mobile API |
|---------|-----------|------------|
| Daftar TPS | ✅ Implemented | ✅ Implemented |
| Submit Setor | N/A | ✅ Implemented |
| Riwayat Setor (View) | ✅ Implemented | ✅ Implemented |
| Filter & Pagination | ✅ Implemented | ✅ Implemented |

---

## Related Endpoints

- **TPS Management**: `/admin/garbage-dumps` - CRUD untuk data TPS
- **Collector Dashboard**: `/api/v1/mobile/collector/dashboard` - Dashboard kolektor
