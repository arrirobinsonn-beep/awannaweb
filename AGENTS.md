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
Saat perlu resolve/map nilai dari Excel/CSV ke DB (misal handle_by → user CS):
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

## Referensi
- Batch spending: `SpendingHarianController@indexGeneral`
- Batch summary: `TopUpController@index`

---

# Fitur Selesai

## A. ✅ Shipment Terpadu — FLIK / SiCepat / SPX (3 Agustus 2026)

### Deskripsi
Satu tabel `shipments` untuk gabungan data pengiriman dari 3 aggregator (FLIK, SiCepat, SPX). Kunci alami `(source, tracking_number)` unik. Setiap hari tarik 1 bulan ke belakang → UPSERT (data baru di-insert, data lama ditimpa bila berubah). Perubahan status dicatat ke `shipment_status_histories`.

> **Catatan penamaan:** semua tabel/kolom/class/method berbahasa Inggris. Tampilan ke user (label view) tetap bahasa Indonesia.

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_02_100000_create_pengirimans_table.php` | Tabel awal (sebelum rename) |
| `database/migrations/2026_08_03_000000_rename_pengiriman_tables_to_english.php` | Rename tabel/kolom ke Inggris (data dipertahankan) |
| `app/Models/Shipment.php` | `$table = 'shipments'`, kolom Inggris |
| `app/Models/ShipmentStatusHistory.php` | Riwayat status, FK cascade |
| `app/Services/ShipmentImportService.php` | `parse()` + `import()`: detectSource, alias map header, normalize, upsert, diff, history |
| `app/Http/Controllers/ShipmentController.php` | `index` (filter+paginate), `preview` (JSON), `store` (import) |
| `app/Console/Commands/ImportShipments.php` | `shipment:import {file?}` — dukung path absolut |
| `resources/views/shipment/index.blade.php` | Upload + preview modal + filter + datatable |
| `routes/web.php` | `shipment.index`, `shipment.preview`, `shipment.import` (URL tetap `/pengiriman`) |
| `routes/console.php` | `Schedule::command('shipment:import')->dailyAt('02:00')` |

### Mapping kolom (Indonesia → Inggris)
| Lama | Baru |
|---|---|
| `sumber` | `source` |
| `no_resi` | `tracking_number` |
| `kurir` | `courier` |
| `nama_penerima` | `recipient_name` |
| `telepon` | `phone` |
| `alamat_lengkap` | `full_address` |
| `kecamatan` | `district` |
| `kota` | `city` |
| `provinsi` | `province` |
| `kode_pos` | `postal_code` |
| `nama_produk` | `product_name` |
| `jumlah` | `quantity` |
| `ongkir` | `shipping_fee` |
| `nilai_paket` | `parcel_value` |
| `nominal_cod` | `cod_amount` |
| `catatan_kurir` | `courier_note` |
| `tanggal_buat` | `created_date` |
| `tanggal_pickup` | `pickup_date` |
| `tanggal_terkirim` | `delivered_date` |
| `file_sumber` | `source_file` |
| `pengiriman_id` (histories) | `shipment_id` |
| `dilihat` (histories) | `viewed_at` |

### Endpoint
- `GET /pengiriman` — daftar + filter (search/source/bulan/status)
- `POST /pengiriman/preview` — preview file CSV
- `POST /pengiriman/import` — import & upsert

### Penting (service import)
- `parse()` mengembalikan key `skips` (bukan `skip`)
- Diff membandingkan string (trim), float (cast), tanggal (format Y-m-d) terpisah — hindari false-positive cast decimal `89000.00` vs `89000`
- Deteksi sumber otomatis dari header (Tracking No → spx, Nomor Resi+Isi Paket → sicepat, Order ID+AWB → flik)

## C. ✅ Stok Jurnal + Barang Masuk (Purchase) (3 Agustus 2026)

### Deskripsi
Stok produk kini dihitung otomatis dari jurnal (`stock_movements`): MASUK (in) dikurangi KELUAR (out) per produk. Pembelian manual ("Barang Masuk") menambah stok + update HPP rata-rata tertimbang. Shipment ("barang keluar") di-link ke produk via `product_id` dan otomatis memotong stok lewat jurnal `out`.

> **Aturan kunci:** semua nama kode (tabel, kolom, class, method, route, key) BERBAHASA INGGRIS; label UI tetap Indonesia. Kolom tanggal bernama `date`.

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_03_100000_create_stock_movements_table.php` | Jurnal: `product_id`, `gudang_id` (nullable), `date`, `type` (in/out), `quantity` (unsigned), `unit_price` (decimal nullable), `reference` (purchase/shipment/adjustment), `reference_id`, `note`, `created_by`; UNIQUE `(reference, reference_id, type)` = idempotent |
| `database/migrations/2026_08_03_100001_create_purchases_table.php` | `date`, `supplier_id`, `product_id`, `quantity`, `unit_price`, `shipping_cost`, `note`, `created_by` |
| `database/migrations/2026_08_03_100002_add_product_id_to_shipments_table.php` | `shipments.product_id` (nullable FK) + index |
| `app/Models/StockMovement.php` | relasi product/gudang/creator |
| `app/Models/Purchase.php` | relasi product/supplier/creator |
| `app/Models/Shipment.php` | + `product_id` fillable, `product()` BelongsTo |
| `app/Models/Product.php` | + `stockMovements()` dan `purchases()` |
| `app/Services/StockService.php` | `recordIn/recordOut` (out validasi stok cukup), `reverseReference`, `recalculateStock`, `stockOf`, `hppRataRata`, `recalculateHpp`, `recalculateAll` |
| `app/Services/ProductNameMatcher.php` | `buildIndex/match/normalize`: exact → contains → Levenshtein ≤ 2 |
| `app/Services/ShipmentImportService.php` | `import()` cocokkan produk per baris; baris TIDAK cocok TIDAK disimpan (masuk `unmatched`), yang cocok set `product_id` + jurnal `out`; `matchReport()` untuk preview |
| `app/Http/Controllers/PurchaseController.php` | `index` (filter), `store` (tambah stok+HPP), `destroy` (balik jurnal) |
| `app/Http/Controllers/StockMovementController.php` | `index` filter jurnal |
| `app/Console/Commands/ImportShipments.php` | laporan `unmatched=N (tidak disimpan)` + list produk tak dikenal |
| `resources/views/purchase/index.blade.php` | Form Barang Masuk + datatable + Hapus |
| `resources/views/stock_movement/index.blade.php` | Tabel jurnal (MASUK/KELUAR badge) |
| `resources/views/layouts/app.blade.php` | Sidebar section "Gudang & Kiriman" |
| `resources/views/product/form.blade.php` | Stok manual → tampilan read-only (stok otomatis jurnal) |

### Endpoint
- `GET /barang-masuk` (purchase.index), `POST /barang-masuk` (purchase.store), `DELETE /barang-masuk/{purchase}` (purchase.destroy)
- `GET /jurnal-stok` (stock-movement.index)

### Penting
- `products.stok` diturunkan dari jurnal (sum in − out). Jurnal pembuka (opening stock, `reference=adjustment`) dibuat agar stok lama tetap utuh.
- `ProductController::store/update` TIDAK lagi memvalidasi `stok` (kolom diisi otomatis).
- Idempotensi import: UNIQUE `(reference, reference_id, type)` → re-import CSV tidak menduplikasi jurnal/shipment.
- `destroy` purchase: `reverseReference('purchase', id)` + recompute HPP + delete.

## B. ✅ Fitur yang DIHAPUS (3 Agustus 2026)

Fitur gudang/stok/kiriman lama dihapus total. Yang tersisa: `Product`, `Supplier`, dan `Gudang` (master tempat gudang, `gudang.master*`).

- Models dihapus: `PembelianBarang`, `StockMovement`, `StockRecap`, `KirimanActual`, `KirimanActualProduct`, `PaketTracking`, `RtsRecap`, `Dashboard`
- Services dihapus: `KirimanImportService`, `UndelImportService`, `OrderOnlineImportService`
- Controller dihapus: `OrderOnlineController`; command dihapus: `BackfillPaketTrackingProductId`
- Migration lama dihapus, views `gudang/*` dihapus (kecuali `master.blade.php`), dashboard `admin.blade.php` + `cs.blade.php` dihapus
- `OrderOnlineContact` model + tabel DIPERTAHANKAN (dipakai `RegionalController`/`TeamController`)
- `RegionalImportService` kini punya `normalizePhone()` sendiri (menggantikan `OrderOnlineImportService::normalizePhone`)
- `Product` model tidak lagi punya relasi `pembelianBarangs()` / `stockMovements()`

---

# Fitur Belum Selesai / Ide ke Depan
