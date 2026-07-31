# Perintah untuk AI:
Baca file ini sebelum memulai sesi. Lanjutkan fitur yang belum selesai sesuai plan di bawah.

---

# Performance Optimization Rules

## 1. Database Indexes
Setiap kolom yang dipakai di `WHERE`, `JOIN`, `ORDER BY`, `GROUP BY`, atau `HAVING` WAJIB punya index. Termasuk kolom `tanggal`, `status`, `user_id`, `product_id`, dll.

## 2. No N+1 Queries
- Jangan pernah query di dalam loop (foreach/for)
- Gunakan `whereIn()` dengan batch data
- Gunakan `GROUP BY` + aggregate functions untuk summary
- Gunakan `with()` (eager loading) untuk relasi

## 3. Batch Query Pattern
```php
// ❌ BURUK: query dalam loop
foreach ($users as $user) {
    $spending = SpendingHarian::where('user_id', $user->id)->sum('spending');
}

// ✅ BAIK: batch 1 query
$totals = SpendingHarian::whereIn('user_id', $users->pluck('id'))
    ->selectRaw('user_id, SUM(spending) as total')
    ->groupBy('user_id')
    ->get()
    ->keyBy('user_id');
```

## 4. Bulk Insert > Loop Insert
Untuk menyimpan banyak data, gunakan `DB::transaction()` + `Model::insert()` atau `::create()` dalam transaction.

## 5. Monitoring
- Debugbar aktif di lokal (untuk lihat query count & time)
- Slow query logging di AppServiceProvider (query > 500ms tercatat)
- Cek `storage/logs/laravel.log` saat ada keluhan lemot

## 6. Regional & Spending Discrepancy
Gunakan `computeDiscrepancyBatch()` yang menerima pre-computed collection, bukan query per user.

## 7. Batch-Resolve Pattern (CS Lookup, dll)
Saat perlu resolve/map nilai dari Excel ke DB (misal handle_by → user CS):
```php
// ❌ BURUK: query per baris
foreach ($rows as $row) {
    $user = User::where('nama', $row['handle_by'])->first();
}

// ✅ BAIK: collect unique → 1 batch query → map
$uniqueValues = array_unique(array_filter(array_column($rows, 'handle_by')));
$users = User::whereIn('nama', $uniqueValues)->get()->keyBy('nama');
// lalu pakai $map[$value] di loop
```
Ini diterapkan di `UndelImportService@buildHandleByMap`.

## Referensi
- Migration indexes: `database/migrations/2026_07_25_005039_add_indexes_to_optimize_query_performance.php`
- Batch spending: `SpendingHarianController@indexGeneral`
- Batch summary: `TopUpController@index`

---

# Fitur Selesai (25 Juli 2026)

## A. ✅ Upload Excel Undel (Excel ke-2)
Update status `PaketTracking` dari file Excel Undel.

### Implementasi:
| File | Keterangan |
|---|---|
| `database/migrations/2026_07_25_010000_add_handle_by_to_paket_trackings.php` | Migration kolom `handle_by` + index `awb`, `handle_by` |
| `app/Models/PaketTracking.php` | Tambah `handle_by` ke `$fillable` |
| `app/Services/UndelImportService.php` | Parse Excel, resolve handle_by → user CS (batch), import by AWB |
| `app/Http/Controllers/GudangController.php` | `excelUndelPreview()`, `excelUndelImport()` |
| `resources/views/dashboard/admin.blade.php` | Card upload + modal + preview + import |

### Endpoint:
- `POST /gudang/kiriman/excel-undel-preview`
- `POST /gudang/kiriman/excel-undel-import`

### Flow:
Upload → Parse → Match AWB → Resolve handle_by (batch cocokkan nama CS) → Update status + handle_by + catatan_kurir → Report sukses/gagal

### Kolom Excel (dari Flik SPX):
| Excel Column | Index | DB Field |
|---|---|---|
| Tracking No. | 0 | `PaketTracking.awb` (match key) |
| Tracking Status | 5 | `PaketTracking.status` (update) |
| HANDLE BY | 17 | `PaketTracking.handle_by` (ditambahkan manual admin) |
| Delivery failed Reason | 45 | `PaketTracking.catatan_kurir` (update) |
| Recipient Phone Number | 16 | `PaketTracking.no_telp` |

---

## B. ✅ Dashboard CS
### Route:
- `GET /dashboard/cs` → `DashboardController@dashboardCs()`

### Implementasi:
| File | Keterangan |
|---|---|
| `app/Http/Controllers/DashboardController.php` | Method `dashboardCs()` + redirect role `cs` |
| `resources/views/dashboard/cs.blade.php` | View: 4 kartu stat, tracking resi, tabel undel + WA, top produk |
| `resources/views/layouts/app.blade.php` | Nav sidebar "📞 Dashboard CS" untuk role `cs` |

### Statistik per CS:
- Total Lead, Paid, Paid Ratio → dari `SpendingHarian` milik advertiser (via `$user->advertiser_id`)
- Total Undel: `PaketTracking where handle_by = nama_cs and status in undelivered list`
- WA button: `https://wa.me/62xxxx` per baris undel
- Tabel daftar undel + status + catatan_kurir
- Top Produk: `GROUP BY nama_produk` dari PaketTracking
- Tracking Resi Mandiri: search AWB → tampilkan detail via `paketDetail()` API

---

## C. ✅ Fitur Tambahan

### Hapus per Tanggal di Rincian Stok
- Tombol 🗑️ Hapus di setiap group tanggal → hapus semua movement produk+gudang+tanggal sekaligus
- `POST /gudang/rincian-stok/delete-date` → `GudangController@stokRincianDeleteDate`

---

## D. ✅ Auto-assign CS via Phone Number (Order Online → Kiriman Actual)

### Deskripsi
Adv upload Excel Order Online (dari OO) yang berisi nomor telepon + CS name. Data ini disimpan di tabel `order_online_contacts`. Saat admin upload Kiriman Actual, nomor telepon dari tiap resi dicocokkan dengan data Order Online → `handle_by` otomatis terisi.

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_07_25_020000_create_order_online_contacts_table.php` | Migration tabel `order_online_contacts` |
| `app/Models/OrderOnlineContact.php` | Model dengan fillable, relasi ke User (advertiser) |
| `app/Services/OrderOnlineImportService.php` | Parse Excel OO, normalisasi nomor, batch import (reset per adv) |
| `app/Http/Controllers/OrderOnlineController.php` | `preview()` + `import()` — upload & preview |
| `app/Http/Controllers/GudangController.php` | `kirimanExcelImport()` — tambah auto-set `handle_by` via phone lookup |
| `resources/views/regional/index.blade.php` | Tombol upload Order Online (khusus role adv) + modal preview + modal import |
| `routes/web.php` | `POST /order-online/preview`, `POST /order-online/import` |

### Endpoint
- `POST /order-online/preview` — preview file OO
- `POST /order-online/import` — import & replace data OO

### Flow
1. Adv upload OO Excel → parse kolom E (phone) + AH (CS name) → normalisasi nomor → simpan ke `order_online_contacts` (data lama dihapus dulu)
2. Admin upload Kiriman Actual → setelah insert `PaketTracking`, batch lookup `no_telp` yang dinormalisasi ke `order_online_contacts` → update `handle_by` yang null

### Normalisasi Nomor
```
08123456789  →  628123456789
+628123456789 → 628123456789
628-1234-56789 → 628123456789
```

### Kolom Excel OO (index 0-based)
| Index | Isi |
|---|---|
| 0 | Order ID |
| 2 | Nama Pembeli |
| 4 | No Telepon |
| 33 | Handle By / CS Name |

---

## E. ✅ Akumulasi Stok Rincian 1 Row per Tanggal (28 Juli 2026)

### Deskripsi
Import Kiriman sekarang mengakumulasi StockMovement jadi **1 baris per (product_id, gudang, tanggal)** — `barang_keluar` dijumlah, catatan digabung. View stok-rincian hanya tampilkan 1 baris per tanggal. Halaman edit: gudang & produk readonly.

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/GudangController.php:658` | `StockMovement::create()` → `firstOrNew()` + increment + concat catatan |
| `resources/views/gudang/stok-rincian.blade.php` | 1 baris akumulasi per tanggal, hapus checkbox/bulk-delete |
| `resources/views/gudang/stok-rincian-edit.blade.php` | Gudang & produk disabled |

### Catatan
- Data lama perlu re-import setelah reset
- Branch: `develop/arif`
- Commit: `56f61c2`

---

# Fitur Belum Selesai / Ide ke Depan