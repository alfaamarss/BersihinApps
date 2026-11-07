# API Pengaduan (Complaint) - Mobile

Dokumentasi API untuk fitur pengaduan di aplikasi mobile Smart Environment.

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

### 1. Daftar Pengaduan
Mendapatkan daftar pengaduan milik user yang sedang login.

**Endpoint:** `GET /resident/complaints`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter berdasarkan status: `open`, `in_progress`, `pending_confirmation`, `resolved`, `rejected` |
| type | string | No | Filter berdasarkan tipe pengaduan |
| service_account_id | integer | No | Filter berdasarkan service account |
| per_page | integer | No | Jumlah data per halaman (default: 15) |

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "complaints": [
      {
        "id": 1,
        "type": "sampah_tidak_diangkut",
        "description": "Sampah di depan rumah tidak diangkut sudah 3 hari",
        "address": "Jl. Merdeka No. 123",
        "latitude": -6.123456,
        "longitude": 106.123456,
        "status": "open",
        "resolution_photo": null,
        "rejection_reason": null,
        "photos": [
          {
            "id": 1,
            "url": "/storage/complaints/photo1.jpg",
            "order": 0
          }
        ],
        "service_account": {
          "id": 1,
          "name": "Rumah Pak Budi",
          "address": "Jl. Merdeka No. 123"
        },
        "assignee": null,
        "created_at": "2025-12-01T10:00:00+07:00",
        "updated_at": "2025-12-01T10:00:00+07:00"
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

### 2. Buat Pengaduan Baru
Membuat pengaduan baru.

**Endpoint:** `POST /resident/complaints`

**Content-Type:** `multipart/form-data`

**Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| service_account_id | integer | No | ID service account terkait |
| type | string | Yes | Tipe pengaduan (max: 100 karakter) |
| description | string | Yes | Deskripsi pengaduan (max: 1000 karakter) |
| address | string | No | Alamat lokasi kejadian |
| latitude | numeric | No | Latitude lokasi (-90 s/d 90) |
| longitude | numeric | No | Longitude lokasi (-180 s/d 180) |
| evidence_photos[] | file | No | Foto bukti (max: 5 foto, masing-masing max 5MB) |

**Tipe Pengaduan yang Tersedia:**
- `sampah_tidak_diangkut` - Sampah Tidak Diangkut ⭐ (memerlukan konfirmasi warga)
- `sampah_menumpuk` - Sampah Menumpuk
- `jadwal_tidak_sesuai` - Jadwal Tidak Sesuai
- `pelayanan_buruk` - Pelayanan Buruk
- `petugas_tidak_datang` - Petugas Tidak Datang
- `lainnya` - Lainnya

**Response Success (201):**
```json
{
  "success": true,
  "message": "Complaint submitted successfully.",
  "data": {
    "id": 1,
    "type": "sampah_tidak_diangkut",
    "description": "Sampah di depan rumah tidak diangkut sudah 3 hari",
    "address": "Jl. Merdeka No. 123",
    "latitude": -6.123456,
    "longitude": 106.123456,
    "status": "open",
    "resolution_photo": null,
    "rejection_reason": null,
    "photos": [
      {
        "id": 1,
        "url": "/storage/complaints/photo1.jpg",
        "order": 0
      }
    ],
    "service_account": {
      "id": 1,
      "name": "Rumah Pak Budi",
      "address": "Jl. Merdeka No. 123"
    },
    "created_at": "2025-12-01T10:00:00+07:00"
  }
}
```

---

### 3. Detail Pengaduan
Mendapatkan detail pengaduan berdasarkan ID.

**Endpoint:** `GET /resident/complaints/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "complaint": {
      "id": 1,
      "type": "sampah_tidak_diangkut",
      "description": "Sampah di depan rumah tidak diangkut sudah 3 hari",
      "address": "Jl. Merdeka No. 123",
      "latitude": -6.123456,
      "longitude": 106.123456,
      "status": "pending_confirmation",
      "resolution_photo": "/storage/complaints/resolutions/photo.jpg",
      "rejection_reason": null,
      "photos": [...],
      "service_account": {
        "id": 1,
        "name": "Rumah Pak Budi",
        "address": "Jl. Merdeka No. 123",
        "contact_phone": "08123456789",
        "area": {
          "id": 1,
          "name": "Area Selatan"
        }
      },
      "assignee": {
        "id": 5,
        "name": "Collector A"
      },
      "created_at": "2025-12-01T10:00:00+07:00",
      "updated_at": "2025-12-01T12:00:00+07:00"
    }
  }
}
```

---

### 4. Update Pengaduan
Mengupdate pengaduan yang masih berstatus `open`.

**Endpoint:** `PUT /resident/complaints/{id}`

**Content-Type:** `multipart/form-data`

**Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | No | Tipe pengaduan |
| description | string | No | Deskripsi pengaduan |
| address | string | No | Alamat lokasi |
| latitude | numeric | No | Latitude lokasi |
| longitude | numeric | No | Longitude lokasi |
| evidence_photos[] | file | No | Foto bukti baru |
| delete_photo_ids[] | integer | No | ID foto yang akan dihapus |

**Response Success (200):**
```json
{
  "success": true,
  "message": "Complaint updated successfully.",
  "data": {
    "id": 1,
    "type": "sampah_tidak_diangkut",
    "description": "Deskripsi yang sudah diupdate",
    ...
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Cannot update complaint with status: in_progress"
}
```

---

### 5. Batalkan Pengaduan
Membatalkan pengaduan yang masih `open` atau `in_progress`.

**Endpoint:** `POST /resident/complaints/{id}/cancel`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Complaint cancelled successfully.",
  "data": {
    "id": 1,
    "status": "rejected"
  }
}
```

---

### 6. Konfirmasi Penyelesaian ⭐ (NEW)
Mengkonfirmasi penyelesaian pengaduan oleh warga. Hanya berlaku untuk pengaduan dengan status `pending_confirmation` (khususnya tipe `sampah_tidak_diangkut`).

**Endpoint:** `POST /resident/complaints/{id}/confirm-resolution`

**Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| confirmed | boolean | Yes | `true` untuk konfirmasi selesai, `false` untuk menolak |
| rejection_note | string | No | Alasan penolakan (jika confirmed=false, max: 500 karakter) |

**Request - Konfirmasi Selesai:**
```json
{
  "confirmed": true
}
```

**Response Success - Konfirmasi Selesai (200):**
```json
{
  "success": true,
  "message": "Terima kasih! Pengaduan telah dikonfirmasi selesai.",
  "data": {
    "id": 1,
    "status": "resolved",
    "updated_at": "2025-12-01T14:00:00+07:00"
  }
}
```

**Request - Tolak Penyelesaian:**
```json
{
  "confirmed": false,
  "rejection_note": "Sampah masih belum diangkut sepenuhnya"
}
```

**Response Success - Tolak Penyelesaian (200):**
```json
{
  "success": true,
  "message": "Pengaduan dikembalikan ke status dalam proses.",
  "data": {
    "id": 1,
    "status": "in_progress",
    "updated_at": "2025-12-01T14:00:00+07:00"
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Pengaduan ini tidak dalam status menunggu konfirmasi."
}
```

---

### 7. Statistik Pengaduan
Mendapatkan statistik pengaduan milik user.

**Endpoint:** `GET /resident/complaints/statistics`

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "statistics": {
      "total": 10,
      "open": 2,
      "in_progress": 3,
      "resolved": 4,
      "rejected": 1
    }
  }
}
```

---

## Collector API (Petugas)

### 1. Daftar Pengaduan yang Ditugaskan
Mendapatkan daftar pengaduan yang ditugaskan ke collector yang sedang login.

**Endpoint:** `GET /collector/complaints`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter berdasarkan status |
| type | string | No | Filter berdasarkan tipe |
| per_page | integer | No | Jumlah data per halaman (default: 15) |

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "complaints": [
      {
        "id": 1,
        "type": "sampah_tidak_diangkut",
        "description": "Sampah di depan rumah tidak diangkut",
        "address": "Jl. Merdeka No. 123",
        "latitude": -6.123456,
        "longitude": 106.123456,
        "status": "in_progress",
        "resolution_photo": null,
        "photos": [...],
        "reporter": {
          "id": 2,
          "name": "Budi Santoso",
          "email": "budi@example.com"
        },
        "service_account": {
          "id": 1,
          "name": "Rumah Pak Budi",
          "address": "Jl. Merdeka No. 123",
          "latitude": -6.123456,
          "longitude": 106.123456,
          "contact_phone": "08123456789",
          "account_number": "SA001"
        },
        "created_at": "2025-12-01T10:00:00+07:00",
        "updated_at": "2025-12-01T11:00:00+07:00"
      }
    ],
    "meta": {...}
  }
}
```

---

### 2. Detail Pengaduan
Mendapatkan detail pengaduan yang ditugaskan.

**Endpoint:** `GET /collector/complaints/{id}`

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "complaint": {
      "id": 1,
      "type": "sampah_tidak_diangkut",
      "description": "...",
      "address": "...",
      "status": "in_progress",
      "resolution_photo": null,
      "photos": [...],
      "reporter": {...},
      "service_account": {
        ...
        "area": {
          "id": 1,
          "name": "Area Selatan"
        }
      },
      "created_at": "...",
      "updated_at": "..."
    }
  }
}
```

---

### 3. Update Status Pengaduan
Mengupdate status pengaduan oleh collector.

**Endpoint:** `POST /collector/complaints/{id}/update-status`

**Content-Type:** `multipart/form-data`

**Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | Yes | Status baru: `in_progress` atau `resolved` |
| resolution_photo | file | No | Foto bukti penyelesaian (max: 5MB) |

**Catatan Penting:**
- Untuk tipe `sampah_tidak_diangkut`, ketika collector mengubah status ke `resolved`:
  - Status akan menjadi `pending_confirmation` (bukan langsung `resolved`)
  - Warga harus mengkonfirmasi penyelesaian melalui aplikasi mobile

**Request - Selesaikan Pengaduan:**
```
POST /collector/complaints/1/update-status
Content-Type: multipart/form-data

status: resolved
resolution_photo: [file]
```

**Response Success - Tipe sampah_tidak_diangkut (200):**
```json
{
  "success": true,
  "message": "Bukti penyelesaian terkirim. Menunggu konfirmasi warga.",
  "data": {
    "id": 1,
    "status": "pending_confirmation",
    "resolution_photo": "/storage/complaints/resolutions/photo.jpg",
    "updated_at": "2025-12-01T12:00:00+07:00"
  }
}
```

**Response Success - Tipe lainnya (200):**
```json
{
  "success": true,
  "message": "Status updated successfully.",
  "data": {
    "id": 1,
    "status": "resolved",
    "resolution_photo": "/storage/complaints/resolutions/photo.jpg",
    "updated_at": "2025-12-01T12:00:00+07:00"
  }
}
```

---

### 4. Statistik Pengaduan Collector
Mendapatkan statistik pengaduan yang ditugaskan ke collector.

**Endpoint:** `GET /collector/complaints/statistics`

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "statistics": {
      "total": 15,
      "in_progress": 5,
      "resolved": 10
    }
  }
}
```

---

## Status Pengaduan

| Status | Label | Deskripsi |
|--------|-------|-----------|
| `open` | Menunggu | Pengaduan baru dibuat, belum ditangani |
| `in_progress` | Dalam Proses | Pengaduan sedang ditangani oleh petugas |
| `pending_confirmation` | Menunggu Konfirmasi | Petugas sudah upload bukti, menunggu konfirmasi warga (khusus tipe `sampah_tidak_diangkut`) |
| `resolved` | Selesai | Pengaduan telah diselesaikan |
| `rejected` | Ditolak | Pengaduan ditolak atau dibatalkan |

---

## Flow Pengaduan

### Flow Normal (Semua Tipe Kecuali sampah_tidak_diangkut)
```
Warga buat pengaduan (open)
         ↓
Admin assign collector (in_progress)
         ↓
Collector upload bukti & selesaikan (resolved)
```

### Flow Khusus Tipe "Sampah Tidak Diangkut"
```
Warga buat pengaduan (open)
         ↓
Admin assign collector (in_progress)
         ↓
Collector upload bukti (pending_confirmation)
         ↓
    ┌────┴────┐
    ↓         ↓
Warga      Warga
konfirmasi  tolak
(resolved) (in_progress) → Collector harus menangani lagi
```

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Cannot update complaint with status: in_progress"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Complaint not found."
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "description": ["The description field is required."],
    "type": ["The type field is required."]
  }
}
```

---

## Contoh Penggunaan (cURL)

### Buat Pengaduan
```bash
curl -X POST "http://localhost:8000/api/mobile/resident/complaints" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "type=sampah_tidak_diangkut" \
  -F "description=Sampah tidak diangkut sudah 3 hari" \
  -F "address=Jl. Merdeka No. 123" \
  -F "latitude=-6.123456" \
  -F "longitude=106.123456" \
  -F "evidence_photos[]=@/path/to/photo1.jpg" \
  -F "evidence_photos[]=@/path/to/photo2.jpg"
```

### Konfirmasi Penyelesaian
```bash
curl -X POST "http://localhost:8000/api/mobile/resident/complaints/1/confirm-resolution" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"confirmed": true}'
```

### Collector Update Status
```bash
curl -X POST "http://localhost:8000/api/mobile/collector/complaints/1/update-status" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "status=resolved" \
  -F "resolution_photo=@/path/to/resolution.jpg"
```
