# Perintah untuk AI:
Baca file ini sebelum memulai sesi. Lanjutkan fitur yang belum selesai sesuai plan di bawah.

---

# Catatan DB Test (19 Agustus 2026)

- `php artisan test` memakai DB **`webawanna_test`** (di-set di `phpunit.xml`), TERPISAH dari DB aplikasi `webawanna` — test TIDAK pernah membuat dummy di DB aplikasi.
- DB test sudah di-migrate + di-seed (DatabaseSeeder lengkap: user, produk, courier rules, export templates, warehouse rules, tracking status rules). Tanpa `RefreshDatabase` — data antar-run menumpuk di DB test (boleh, tidak mengganggu siapa pun).
- Reset DB test bila perlu: `CREATE DATABASE webawanna_test` → `$env:DB_DATABASE='webawanna_test'; php artisan migrate:fresh --seed`.
- `filecoba/verify_pipeline.php` tetap memakai DB aktif (.env = `webawanna`) tapi self-cleanup (hapus order CBC-* + balik jurnal).
- Catatan lama di bagian fitur yang menyebut "Test memakai DB `awannacoba`" tidak berlaku lagi (DB test = `webawanna_test`, DB aplikasi = `webawanna`).

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

## D. ✅ Data Mentah Order Online + Export Template Excel (7 Agustus 2026)

### Deskripsi
Upload file CSV data mentah order online ("Data dari Order Online") ke tabel `shipping_orders` per-batch. Setiap order otomatis mendapat courier dari tabel `courier_rules`. User lalu bisa mengekspor order per-batch ke template Excel aggregator (FLIK / SiCepat / SPX) dengan PhpSpreadsheet. Import sekaligus memperbarui `order_online_contacts` (mapping phone → CS).

> **Aturan kunci:** semua nama kode BERBAHASA INGGRIS (tabel, kolom, class, route, endpoint); label UI tetap Indonesia. Kolom `region_group` DIGANTI `courier`.

### Hierarki courier (tabel `courier_rules`, dievaluasi by `sort_order`)
- `bank_transfer` (semua provinsi) → **flix-tf**
- `cod` provinsi {BENGKULU, JAMBI, LAMPUNG, RIAU, SUMATRA BARAT, SUMATRA SELATAN, SUMATRA UTARA} → **flix-idx**
- `cod` provinsi {BANTEN, DKI JAKARTA, JAWA BARAT, JAWA TENGAH, JAWA TIMUR, DI YOGYAKARTA, BALI} → **sicepat** (template SiCepat)
- `cod` provinsi lainnya (Kalimantan, Sulawesi, Maluku, Papua, NTB, NTT, Aceh, Bangka, Kep. Riau, Gorontalo) → **flix-spx**
- Tidak ada rule cocok (provinsi tak dikenal / payment method lain) → **spx** (fallback)
- Admin bisa override manual per order; `courier = 'undeliverable'` + `courier_note` = label "paket tidak dapat terkirim" (tidak ikut export)

### Mapping courier → template export
- `flix-tf`, `flix-idx`, `flix-sicepat`, `flix-spx` → template **FLIK** (export terpisah per courier via dropdown)
- `spx` → template **SPX**
- `sicepat` → template **SiCepat** (courier DEFAULT Jawa+Bali sejak 10 Agustus; `flix-sicepat` TIDAK lagi dipilih otomatis, hanya tersedia sebagai override manual bila SiCepat bermasalah)
- `undeliverable` → TIDAK di-export

### Status order (enum `shipping_orders.status`)
- `processing` (CSV) → **`real`**
- `pending` + `payment_status=paid` → **`tembakan`**
- `pending` + `payment_status=unpaid` → **`belum_diproses`**
- `cancelled` → **`cancel`**
- `completed` → **di-skip saat parse** (tidak masuk DB; masuk laporan `skips`/`skipped`)
- Export HANYA memuat `real` + `tembakan` (`ShippingOrder::EXPORTABLE_STATUSES`); selain itu tidak ikut file.
- **`tembakan` SELALU courier `spx`** (terlepas dari provinsi/payment method); `real` pakai `courier_rules`.
- `courier` HANYA diisi untuk `real`/`tembakan`; `belum_diproses`/`cancel`/`duplikat` → `courier=null` (badge "courier —" di UI).
- Nilai `status` & `payment_status` mentah CSV tetap tersimpan di `raw_payload`.

### Kode Warehouse & split per gudang (export)
- **Kode Warehouse** = `OrderTemplateExportService::warehouseFor(product_code, sender)` → `KSP`→**Aurora**, `SH`→**GTM**, produk lain→**sender**. Kolom "Kode Warehouse" FLIK diisi per-baris dari sini. `warehouseFor()` memakai kode dasar (`explode('+', $code)[0]`) agar tahan `product_code` berformat kode varian (`KSP+1.50`→Aurora).
- Export dikelompokkan per gudang (`warehouseFor`): **1 gudang → .xlsx langsung**; **≥2 gudang → 1 ZIP** berisi file per gudang (alamat pickup tiap gudang berbeda, berlaku semua template).
- Nama file per gudang: `Ymd_<template>[_<courier>]_<warehouse>_<batch>.xlsx` (contoh `20260808_spx_eresgestore_74.xlsx`, `20260808_flik_flixtf_GTM_74.xlsx`); ZIP: `Ymd_<template>[_<courier>]_<batch>.zip`.
- **Khusus SPX biasa**: nomor HP dinormalisasi `phoneSpx()` → mulai `8` (hapus `0`/`62`/`+62`); provinsi/kota/kecamatan **CAPSLOCK**.
- **Kolom HP FLIK (10 Agustus)**: HANYA **1 kolom** `No HP Pelanggan (mulai dengan "62")` = `phone_normalized`. Kolom kedua yang berawalan `"8"` dihapus.
- **Nama produk kacamata (10 Agustus)**: untuk produk KMP/KSP/KBJ, kolom Nama Produk (FLIK idx 15) / Isi Paket (SiCepat idx 10) / Nama Barang (SPX idx 16) memakai `productDisplayName()` = `<nama> +<power> <qty> pcs` (power dari `product_variants.power`, format `+1.50`; suffix `N pcs` lama di-strip dulu agar tidak dobel). Produk non-kacamata memakai `product_name` apa adanya. Ambil power via eager-load `with('variant')` (anti N+1).
- **Dimensi & catatan kurir (9 Agustus)**: konstanta `PACK_DIMENSIONS=[10,8,6]` & `DEFAULT_COURIER_NOTE='HUBUNGI KONSUMEN SEBELUM DIKIRIM'`. Panjang/Lebar/Tinggi=10/8/6 di kolom masing-masing template (FLIK idx 11/12/13, SiCepat 12/13/14, SPX 13/14/15). Catatan kurir = `$o->courier_note ?: DEFAULT_COURIER_NOTE` di kolom FLIK `Alamat: Catatan Kurir` (idx 9), SiCepat `Catatan Pengiriman` (idx 20), SPX `Instruksi Pengiriman` (idx 21). Volume TIDAK dijumlahkan — tiap template punya kolom sendiri; kolom `Berat` tetap `weight`. (indeks FLIK bergeser −1 sejak kolom HP "8" dihapus)

### Parser kolom `product`, `variation`, & `meta_account` (9 Agustus)
- **`product_name` (kolom `product` CSV)**: hanya sampai spasi sebelum `-` (mis. `A.3 Kacamata Multifokus Photocromic - 13722` → `A.3 Kacamata Multifokus Photocromic`). Angka setelah `-` (mis. `13722`) disimpan ke kolom baru `meta_account` (hanya DB, tidak tampil di UI). Tanpa `-` → nama polos, `meta_account=null`.
- **`Dapat N` dari `variation`** (regex `/Dapat\s*(\d+)/i`, contoh `"PROMO Beli 1 Dapat 2"`): qty override (menang atas kolom CSV `quantity`) dan `product_name` jadi `"{nama} {N} pcs"` (mis. `A.3 Kacamata Multifokus Photocromic 2 pcs`). Tanpa Dapat → qty dari kolom CSV (default 1), nama polos.
- **`product_code` = KODE VARIAN** (`product_variants.code`, mis. `KMP+1.50`), bukan kode produk master — di-set di `import()` setelah `resolveVariant()` (via `$variantIndex[product_id]->firstWhere('id', variant_id)`). Deteksi duplikat `orderSignature()` menormalkan `explode('+', product_code)[0]` agar tetap memakai kode master (data lama & baru konsisten).

### Deteksi duplikat (12 Agustus; disesuaikan 13 Agustus — pemisah utama `order_id`)
- Signature duplikat = `phone_normalized|product_code|alamat` (normalized: lowercase/trim/kolaps spasi) → `OrderOnlineImportService::orderSignature()`; kode produk dipakai versi master (`explode('+')[0]`).
- Pencocokan terhadap seluruh DB dengan `created_at >= now() - 14 hari` (`DUP_WINDOW_DAYS=14`, 1 batch query `whereIn` phone via `loadDuplicateSignatures()`, kini mengembalikan `[signature => [order_id => true]]` dari SEMUA status).
- Berlaku untuk **SEMUA status** (termasuk `real`/`tembakan`), bukan hanya `belum_diproses`. Pembeda utamanya `order_id`:
  - **`order_id` BERBEDA** + signature sama → **`duplikat`** (courier=null, tidak ikut export; dua-duanya real COD di file sama kini tertangkap).
  - **`order_id` SAMA** (re-import) → **BUKAN duplikat**, masuk rule perbarui status / drop (`double_real`).
  - >14 hari cocok → **repeat order** (diproses normal).
- **Pengecualian `payment_method=bank_transfer`**: tidak pernah jadi `duplikat` (uang sudah diterima → repeat order), TAPI tetap menambah signature ke set → baris `cod`/lainnya dengan signature sama & order_id beda tetap bisa ditandai duplikat.
- Duplikat dalam 1 file yang sama juga tertangkap (signature ditambahkan ke set saat loop; butuh `phone_normalized` valid).

### Re-import data yang sama (by `order_id`, 9 Agustus; disesuaikan 12 Agustus)
- Baris `real`/`tembakan` **menghapus permanen** baris lama dengan `order_id` sama yang berstatus `belum_diproses` (di batch mana pun, dalam `DB::transaction` yang sama; `ShippingOrder` tanpa SoftDeletes). `cancel` dan `real` lama TIDAK dihapus/ditimpa. **Baris `duplikat` TIDAK ikut dihapus** — `duplikat` (by signature `phone+produk+alamat`) adalah order yang *berbeda* (order_id-nya sendiri), jadi tidak dianggap "baris lama dari order yang sama" saat order aslinya naik status. Sejak 12 Agustus `stale` hanya `where('status','belum_diproses')`.
- **Anti double-export**: jika `real`/`tembakan` dengan `order_id` sama SUDAH ada di batch lain → baris TIDAK di-insert (dihitung ke `double_real`); statistik batch `success_rows = inserted + updated + duplicates`, `failed_rows = double_real`. Pencocokan memakai 1 query batch `whereIn` `order_id` → `groupBy` (`$byOrderId`, anti N+1).
- Hasil import bertambah key `deleted` (baris `belum_diproses` lama yang dihapus) & `double_real`; pesan flash controller: `Baris belum diproses lama dihapus: N` dan `Real di-skip (sudah ada): N`.
- Konsekuensi keputusan 12 Agustus: jika order_id yang tadinya `duplikat` naik jadi `real`, baris `duplikat` lama tetap ada berdampingan dengan `real` baru (2 baris, aman — `duplikat` tidak exportable).

### Stok via product_code
- Import: `product_code` (CSV) di-resolve exact-match ke `products.code` → `product_id` (1 batch query `whereIn`); tak cocok → `product_id=null`.
- **Saat export**, `OrderTemplateExportService::reserveStock()` memanggil `StockService::recordOut(... 'order_online', order->id ...)` → jurnal `out` + kurangi stok.
- Stok kurang / produk belum di-link → order **dilewati** dari file + `stock_note` diisi (admin bisa edit `product_code` lalu re-export).
- Idempotent: UNIQUE `(reference, reference_id, type)` → re-export tidak menggandakan jurnal/stok.
- **Saat courier order diubah menjadi `undeliverable`** (paket tidak terkirim/tidak ter-cover aggregator), `OrderOnlineController::update()` memanggil `StockService::reverseReference('order_online', $order->id)` → jurnal `out` dihapus + stok dikembalikan. Ubah dari `undeliverable` ke courier normal TIDAK menambah stok (export-lah yang meng-reserve ulang).
- **Edit order tidak boleh menimpa varian** (fix 9 Agustus): form `update` selalu mengirim `product_code`, tapi `product_variant_id` HANYA di-re-resolve (kini ke varian hasil lookup `ProductVariant::where('code', product_code)`) jika `product_code` benar-benar berubah; jika berubah dan sudah ada jurnal `out` → `reverseReference()` dulu agar stok varian lama kembali. `recordOut()` juga menolak memindahkan jurnal `out` existing ke varian berbeda (hapus + recalc varian lama dulu) — cegah stok varian mangkrak (fisik 110 vs jurnal 111). Dropdown edit `product_code` di view menampilkan varian (`$p->variants`, `value=$v->code`).

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_07_120000_create_order_online_schema.php` | 1 migration final (konsolidasi 8 file lama): `order_online_import_batches` (+`sender`), `aggregator_sync_batches`, `courier_rules`, `shipping_orders` (status enum final, product_code/product_id/stock_note, semua index) |
| `database/migrations/2026_08_03_100000_create_stock_movements_table.php` | enum `reference` langsung `['purchase','shipment','adjustment','order_online']` (merge migration 100006) |
| `database/seeders/CourierRuleSeeder.php` | 35 rules awal (flix-tf + flix-idx 7 + flix-sicepat 7 + flix-spx 20) |
| `database/seeders/ProductSeeder.php` | 6 produk sesuai kode CSV (KMP/KSP/KBJ/KCHP/SH/KNGH); `KMPU→KMP`, `SNDR→SH` sudah di-rename di DB; **buat opening stock** via `StockService::recordIn(..., 'adjustment', $p->id, 'Stok awal (seeder)')` |
| `app/Models/OrderOnlineImportBatch.php` | relasi `shippingOrders()` |
| `app/Models/ShippingOrder.php` | relasi `importBatch()`, `handledByUser()`, `product()`; `STATUSES`, `EXPORTABLE_STATUSES`, `isExportable()` |
| `app/Models/AggregatorSyncBatch.php`, `CourierRule.php` | model sederhana |
| `app/Services/CourierRuleService.php` | `resolve(payment_method, province)`, cache per request, constants `COURIERS` |
| `app/Services/OrderOnlineImportService.php` | `parse/preview/import`; calibrate province (config regional), mapping status (completed skip), deteksi duplikat 14 hari, resolve `product_id` via `whereIn`, resolve CS batch via `users.nama/panggilan`, isi `order_online_contacts`, simpan `raw_payload` |
| `app/Services/OrderTemplateExportService.php` | `download(batch, template, courier?)` → .xlsx atau ZIP per gudang (PhpSpreadsheet); `WAREHOUSE_BY_PRODUCT` (KSP→Aurora, SH→GTM) + `warehouseFor()` (tahan kode varian via `explode('+')`); `PACK_DIMENSIONS=[10,8,6]` + `DEFAULT_COURIER_NOTE`; `phoneSpx()` (mulai 8) + CAPSLOCK utk SPX; filter `EXPORTABLE_STATUSES`; `reserveStock()` (recordOut + stock_note); Kelurahan kosong; nilai=amount (gross_revenue CSV) |
| `app/Http/Controllers/OrderOnlineController.php` | `index` (batch + orders + `$products` utk select), `preview`, `store` (sender required, tampilkan jumlah duplikat), `update` (edit courier/product_code; varian HANYA di-re-resolve bila product_code berubah via `ProductVariant::where('code')`, reverseReference dulu bila ada jurnal), `export` |
| `resources/views/order/index.blade.php` | Upload (sender wajib) + preview modal + daftar batch + tabel orders (badge status incl. duplikat/cancel/belum_diproses, kolom produk+stock_note) + edit courier & product_code (dropdown per varian **dari export_templates** + undeliverable) inline + dropdown export FLIK per courier |
| `database/migrations/2026_08_09_000000_add_meta_account_to_shipping_orders_table.php` | kolom `shipping_orders.meta_account` (string nullable) |
| `resources/views/layouts/app.blade.php` | Sidebar section Iklan → "Data Mentah" |
| `tests/Feature/OrderOnlineTest.php` | 45 test: courier resolve (incl. sicepat), status mapping (incl. completed skip, courier null), resolve product_id, render, duplikat (window/same file/repeat order/bedorder_id real COD/bank_transfer repeat & source/re-import order_id sama bukan duplikat), FLIK separated by courier+status, stok idempotent, skip stok kurang, undeliverable balikin stok, undeliverable→courier normal tidak dobel, undeliverable varian non-default balikin stok & varian tetap, edit courier product_code sama → varian tetap, ganti product_code dgn jurnal ada → stok varian lama balik, reimport real hapus belum_diproses lama, reimport real tidak dobel (double_real), warehouse mapping, ZIP split SH/KSP/sender, phoneSpx 8 + CAPSLOCK, filename sender, tembakan→spx, product meta_account split, dapat qty override + product_name, product_code = kode varian, warehouseFor varian, dimensi & catatan kurir per template, FLIK 1 kolom HP 62, nama kacamata +power, nama non-kacamata tetap |

### Endpoint
- `GET /orders` — daftar batch + orders (filter search/courier/status per batch)
- `POST /orders/preview` — preview CSV
- `POST /orders/import` — import → buat batch baru (body `sender` wajib)
- `PUT /orders/{shippingOrder}` — edit courier + courier_note + product_code
- `GET /orders/{batch}/export/{template}/{courier?}` — unduh .xlsx (`template`: flik/sicepat/spx; courier wajib utk FLIK)

### Penting
- **Server dev `php -S` wajib dijalankan dengan limit upload besar** — `upload_max_filesize` default CLI hanya 2M sedangkan file CSV mentah bisa >2MB → error "The file failed to upload." (HTTP 422). Start dari folder `public/`:
  ```
  /usr/bin/php8.3 -d upload_max_filesize=32M -d post_max_size=40M -d memory_limit=256M -S 127.0.0.1:8000 /home/gagalmasukptn/execute/cba/awannaweb/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
  ```
- `raw_payload` JSON menyimpan semua field CSV mentah lain (utm, coupon, dst) sebagai arsip.
- `handled_by` disimpan apa adanya dari file; `handled_by_user_id` di-resolve batch via `users.nama`/`panggilan`.
- Anti N+1: `userIndexByNama()` 1 query, dedupe contacts per phone_normalized.
- File mentah disimpan ke `storage/app/order-online/` via `store('order-online')`.
- `sender` (nama pengirim) wajib saat upload → `order_online_import_batches.sender` → kolom pertama template FLIK ("Kode Warehouse").
- **Export kosong / hanya header** = akibat `stockOf()=0` karena jurnal `stock_movements` kosong meski kolom `stok` terisi → `ProductSeeder` membuat opening stock (jurnal `in`/`adjustment` per produk, `reference_id=$p->id`).
- Test memakai DB `awannacoba` (tanpa refresh) → buat kode produk & `order_id` unik per test (prefix `uniqid()`); `created_at` di-set via `where()->update()` (tidak `$fillable`); CSV test harus punya kolom `address` (phone lokal pendek ternormalisasi jadi `''`).

## E. ✅ Skema Varian Produk + Stok per Varian + Inventory (8 Agustus 2026)

### Deskripsi
`products` kini master saja (nama, kode, harga, inventory_id) — kolom `stok`, `supplier_id`, `ukuran` dipindah. Stok & ukuran tinggal di `product_variants`; gudang induk `gudangs` di-rename penuh jadi `inventories`. Jurnal stok (`stock_movements`) & pembelian (`purchases`) sekarang **per varian** (`product_variant_id`), bukan per produk.

### Skema inti
- `inventories` (dari `gudangs`, semua kolom `gudang.*` → `inventory.*`): master tempat gudang; `products.inventory_id` FK.
- `product_variants`: `product_id`, `power` (decimal(5,2) = 1.00 s/d 3.00 step 0.25), `name`, `jenis` (nullable), `stock` (kolom fisik, dijaga `StockService`), `hpp`; index `(product_id, power)`. Kode varian = `{product.code}+{power}` (mis. `KSP+1.50`).
- `product_variant_items`: BOM optional `(variant_id, item_variant_id, qty)`.
- `stock_movements`: `product_variant_id` (FK cascade) + `inventory_id` (nullable, FK nullOnDelete); unique `(reference, reference_id, type)` tetap.
- `purchases`: `product_variant_id` (FK cascade) + `supplier_id`.
- `shipping_orders`: + `product_variant_id` (nullable FK nullOnDelete + index); `product_code` berisi kode varian (`product_variants.code`, mis. `KMP+1.50`) sejak 9 Agustus.
- `products`: `stok`/`supplier_id`/`ukuran` DIBUANG; relasi `inventory()`, `variants()`, `spendingHarians()`; accessor `stok` = sum `product_variants.stock`; `defaultVariant()`; scope `aktif` (ganti `aktif`).

### Varian default & ukuran
- Produk **berukuran** (`ProductSeeder::SIZED_PRODUCTS = ['KBJ','KMP','KSP']`) → 10 varian power `+1.00` s/d `+3.00` step 0.25.
- Produk **tanpa ukuran** (KCHP, SH, KNGH) → 1 varian default (`power` 0, kode `{product.code}`).
- `Product::defaultVariant()` = varian aktif dengan `power` terkecil (aktif = `jenis` bukan 'master').

### CSV `variation` → varian
- `OrderOnlineImportService` ekstrak power dari kolom `variation` (contoh: `"Ukuran: Usia 40-42 Tahun Plus +1.00, Warna: Grey Elegant"`) via regex `Plus\s*([+\-]?\d+(?:\.\d+)?)` → `resolveVariant()` (exact `power`, fallback `defaultVariant()`); diisi ke `product_variant_id`. Warna diabaikan.

### Stok via `StockService` (per varian)
- `recordIn(variantId, inventoryId, qty, unitPrice, reference, referenceId, note)` → jurnal `in` + tambah `product_variants.stock` + recalc HPP.
- `recordOut(...)` → validasi stok cukup (`\RuntimeException`), jurnal `out` + kurangi stok. **Anti silent-reassign:** jika sudah ada jurnal `out` untuk `(reference, reference_id, type)` dengan `product_variant_id` BERBEDA → hapus + `recalculateStock(varian lama)` dulu, baru catat ke varian baru (mencegah stok varian lama mangkrak saat re-export setelah varian order diubah).
- `reverseReference(reference, referenceId)` → hapus jurnal + kembalikan stok.
- `stockOf(variantId)`, `hppRataRata(variantId)`, `recalculateStock/recalculateHpp/recalculateAll`.
- Pemanggil: `PurchaseController::store/destroy`, `OrderTemplateExportService::reserveStock` (`$order->product_variant_id`), `ShipmentImportService` (`defaultVariant()`), `ProductController::variantStore` (`stock_awal`).

### Undeliverable → stok kembali (perilaku fix 9 Agustus)
- Ubah courier order → `undeliverable` ⇒ `OrderOnlineController::update()` memanggil `reverseReference('order_online', order->id)` → jurnal `out` dihapus + stok varian asal export kembali. Ubah `undeliverable` → courier normal TIDAK menambah stok (export yang reserve ulang via `recordOut` idempotent).
- `update()` HANYA me-re-resolve `product_variant_id` ke varian hasil lookup `ProductVariant::where('code', product_code)` jika `product_code` yang dikirim benar-benar berubah (form selalu mengirim `product_code`, jadi tanpa penjagaan ini varian order bisa diam-diam tertimpa ke default). Jika berubah dan sudah ada jurnal `out` → `reverseReference()` dulu (stok varian lama kembali) sebelum ganti varian.

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_07_06_154252_create_inventories_table.php` | dari `2026_07_28_000001_create_gudangs_table.php` (git-mv, anonymous class, dibuat sebelum products) |
| `database/migrations/2026_08_03_090000_create_product_variants_table.php` | dari `2026_08_05_100000_…`; power decimal(5,2) + index `(product_id,power)` |
| `database/migrations/2026_08_03_090001_add_jenis_and_bom_to_product_variants.php` | `jenis` nullable + tabel `product_variant_items` |
| `app/Models/Inventory.php` (baru), `app/Http/Controllers/InventoryController.php` (baru) | master inventory; routes `inventory.master*`, view `inventory/master.blade.php` |
| `app/Models/Product.php`, `ProductVariant.php`, `StockMovement.php`, `Purchase.php`, `Supplier.php`, `ShippingOrder.php` | relasi & fillable skema baru; `Supplier` tanpa `whitelists()/spendingHarians()`; `Product` tanpa `whitelists()/purchases()` |
| `app/Services/StockService.php` | ditulis ulang per-varian |
| `app/Services/OrderOnlineImportService.php` | `extractPower()`/`resolveVariant()`; `product_variant_id` di row & import |
| `app/Services/OrderTemplateExportService.php`, `ShipmentImportService.php`, `ProductNameMatcher.php` | pakai `$order->product_variant_id`/`defaultVariant()`; matcher `['id','name']` |
| `app/Http/Controllers/ProductController.php` | index/form skema baru + modal variant (`product.variant.store|update|destroy`) + `toggleStatus`/`toggleVariantStatus`; `variantStore` + `stock_awal` via `recordIn` |
| `app/Http/Controllers/PurchaseController.php`, `StockMovementController.php` | per-varian |
| `database/seeders/InventorySeeder.php` (baru), `ProductSeeder.php` | InventorySeeder dipanggil urutan #2 di `DatabaseSeeder`; `seedVariants()` + opening stock `recordIn('adjustment')` per variant |
| `tests/Feature/OrderOnlineTest.php` | helper `makeProduct()`/`variant()` per varian |

### Penting
- `migrate:fresh --seed` gagal jika FK `stock_movements→product_variants` dibuat sebelum tabel variants → urutan migration dijaga (inventories → products → variants → stock_movements).
- Route product = `Route::resource('product', ProductController::class)->except('show')->names('product')`.
- **Kolom varian bernama `code`/`name` (BUKAN `kode`/`nama`)** — view & controller wajib memakai `code`/`name` (tampilan, `data-*`, body fetch). Jika dipakai `kode`/`nama` → atribut null (tampil kosong) DAN `ProductVariant::create/update` silent-drop → varian gagal simpan.
- Toggle status langsung: `PATCH product/{product}/toggle-status` & `PATCH product/variant/{variant}/toggle-status` (flip active↔inactive, JSON `{success,status}`); toggle switch `.clay-toggle` di view product (kolom Status induk + varian).
- `ExampleTest` gagal 302 (redir login) — pre-existing, bukan karena perubahan skema.

## F. ✅ Produk BOX/LAP/KDF + Pengurangan Stok Otomatis (Kemasan & Split KBJ) (9 Agustus 2026)

### Deskripsi
Saat stok kacamata (KMP/KSP/KBJ) berkurang lewat pengiriman, stok kemasan ikut berkurang otomatis: **BOX + LAP = floor(qty/2)** (rasio diambil dari tabel `packaging_rules` — admin bisa ubah di halaman Gudang). **Aturan kemasan punya 2 JENIS (`rule_type`): `additional` dan `split`** — keduanya data-driven dari `packaging_rules`:
- **`additional`** (BOX/LAP): tiap `qty_per` unit barang inti → 1 unit pendamping keluar (target pakai varian DEFAULT).
- **`split`** (promo "Beli 1 Dapat 2"): barang inti dipecah — **main keluar `ceil(qty/qty_per)`, target keluar `floor(qty/qty_per)`** dengan varian target **POWER SAMA** (fallback power terkecil). Contoh `KMP → KDF qty_per=2`: order qty 2 → 1 KMP + 1 KDF keluar; qty 4 → 2 KMP + 2 KDF.
Berlaku di **dua alur**: export order-online (`reserveStock`) DAN import shipment ber-resi (`ShipmentImportService`). Split & kemasan HANYA memengaruhi stok/jurnal — isi template export tetap 1 baris qty asli.

> **Produk baru (seeder):** BOX 'Box Kacamata' & LAP 'Lap Pembersih' (aksesoris, non-sized, 1 varian default, stok 1000), KDF 'Kacamata Double Fokus' (kacamata pendamping, sized → 9 power, stok 1000 dibagi rata). KDF tidak memicu pengurangan saat terjual sendiri.

### Aturan (seed default, 13 Agustus)
| Produk | Pengurangan saat kirim qty |
|---|---|
| KMP | **KMP −ceil(qty/2); KDF −floor(qty/2)** (split promo); BOX −floor(qty/2); LAP −floor(qty/2) |
| KSP | produk −qty; BOX −floor(qty/2); LAP −floor(qty/2) |
| KBJ | **KBJ −ceil(qty/2); KDF −floor(qty/2)** (split promo); BOX −floor(qty/2); LAP −floor(qty/2) |
| lainnya | produk −qty (tanpa kemasan/split) |

Contoh: KMP qty 2 → 1 KMP + 1 KDF + 1 BOX + 1 LAP; KMP qty 1 → 1 KMP (0 KDF/BOX/LAP); KBJ qty 2 → 1 KBJ + 1 KDF + 1 BOX + 1 LAP.

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_09_100000_extend_stock_movements_unique_for_packaging.php` | unique `stock_movements_ref_unique` → `(reference, reference_id, type, product_variant_id)` — 1 order/shipment boleh punya banyak baris `out` (kacamata + KDF + BOX + LAP) |
| `database/migrations/2026_08_13_100004_add_rule_type_to_packaging_rules_table.php` | + `packaging_rules.rule_type` string default `additional` + index; nilai `additional` (1 pendamping per qty_per) & `split` (pecah inti+bonus, power sama) |
| `app/Services/StockService.php` | konstanta `KACAMATA_CODES` (label export) & `PACK_KDF_CODE`; **`recordOutWithPackaging()`** (1 transaksi: main + target split (ceil/floor, power sama via `variantForPower()`) + target additional (varian default); produk pendamping belum ada/stok kurang → `RuntimeException` → rollback atomik); `packagingRulesFor()` (DB-driven, cache per instance, anti N+1) + `defaultVariantOf()` + `variantForPower()`; key `updateOrCreate` `recordIn`/`recordOut` kini SERTA `product_variant_id`; anti silent-reassign di-scope per produk (`whereHas variant.product_id`) |
| `app/Services/OrderTemplateExportService.php` | `reserveStock` → `recordOutWithPackaging` (alur order-online) |
| `app/Services/ShipmentImportService.php` | 2 call site `import()` → `recordOutWithPackaging` (alur resi aggregator) |
| `database/seeders/ProductSeeder.php` | `SIZED_PRODUCTS` + `KDF`; tambah produk BOX/LAP/KDF + opening stock; `seedPackagingRules()` (6 rule additional KMP/KSP/KBJ→BOX/LAP qty_per=2 **+ 2 rule split KMP/KBJ→KDF qty_per=2**, idempotent) — dipanggil SETELAH produk dibuat (fix DB fresh) |
| `tests/Feature/OrderOnlineTest.php` | +6 test packaging/split (total 38) |

### Penting
- **Key `updateOrCreate` `recordOut` WAJIB menyertakan `product_variant_id`** — kalau tidak, baris `out` berikutnya untuk ref sama (mis. BOX) menimpa varian baris sebelumnya (KMP), lalu LAP menimpa BOX → `firstOrFail()` final gagal → transaksi rollback → tidak ada jurnal sama sekali (ekspor diam-diam tidak mengurangi stok apa pun).
- `powerList()` menghasilkan **9** power (1.00–3.00 step 0.25), bukan 10 → sized product total stok 999 (9×111).
- `reverseReference` (undeliverable / ganti produk) otomatis membalik SEMUA movement pendamping (delete semua + recalc tiap varian).
- Produk pendamping belum terdaftar/stok kurang → export: order dilewati + `stock_note` berisi pesan; import shipment: batch gagal (perilaku konsisten dgn stok produk utama kurang).

---

## F1. ✅ Rename Kolom Harga Order Online — `amount` & `shipping_cost` (13 Agustus 2026)

### Deskripsi
Kolom `shipping_orders.product_price` & `cod_amount` (diisi dari `product_price` CSV) dipindah makna & di-rename dalam satu migrasi (data lama sudah dihapus; idempotent untuk DB berisi draft):

| Lama (CSV `product_price`) | Baru | Sumber CSV |
|---|---|---|
| `product_price` (harga produk saja) | `amount` (nilai kotor = harga + ongkir) | `gross_revenue` (fallback `product_price` bila kolom kosong) |
| `cod_amount` (harga produk saat COD, 0 non-COD) | `shipping_cost` (ongkir terpisah) | `shipping_cost` |

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_13_000000_rename_shipping_order_price_columns.php` | rename `product_price→amount`, `cod_amount→shipping_cost` (decimal 14,2) + backfill dari `raw_payload` JSON (`gross_revenue`→amount, `shipping_cost`→shipping_cost; tanpa gross_revenue → amount tetap nilai lama; tanpa shipping_cost → 0) |
| `app/Services/OrderOnlineImportService.php` | `$amount = gross_revenue ?: product_price`; `$shippingCost = shipping_cost`; row pakai `amount`/`shipping_cost` (tanpa `product_price`/`cod_amount`) |
| `app/Models/ShippingOrder.php` | fillable & casts `amount` (`decimal:2`) + `shipping_cost` (`decimal:2`) |
| `app/Services/OrderTemplateExportService.php` | semua `$o->product_price` → `$o->amount` (nilai barang & nominal COD di FLIK/SiCepat/SPX = gross_revenue, termasuk ongkir) |
| `tests/Feature/OrderOnlineTest.php`, `AggregatorTrackingImportTest.php` | `createOrder`/seed pakai `amount => 10000` |
| `filecoba/generate_test_kit.php` + 7 file | CSV mentah memuat `shipping_cost=50000` + `gross_revenue=product_price+ongkir` (119000→169000); referensi export pakai `grossRevenue()` |

### Penting
- `raw_payload` tetap menyimpan semua kolom mentah CSV (termasuk `product_price` lama) — tidak hilang.
- `shipments.cod_amount` (tabel shipment aggregator) TIDAK berubah — hanya `shipping_orders`.
- Migrasi bisa dijalankan tanpa re-import: backfill membaca `raw_payload` per baris.
- `is_cod` tetap dipertahankan (penanda COD untuk template export).

## G. ✅ Upload Status Aggregator → awb/aggregator_status/delivered_at + Stok Return (10 Agustus 2026)

### Deskripsi
Admin upload file dashboard aggregator (FLIK / SiCepat / SPX, `.csv` atau `.xlsx`) lewat halaman Data Mentah → kolom `shipping_orders.awb`, `aggregator_status`, `delivered_at` terisi. Baris file dihubungkan ke order memakai **pencocokan 2 kolom**: `phone_normalized` (sudah di-filter batch `whereIn`) + `customer_name` (dari kolom "Nama Penerima" / "Nama Shopper" / "Recipient Name" file). 0 kandidat nama → `unmatched`; >1 kandidat nama sama → `ambiguous` (tidak diisi).

Saat `aggregator_status` berubah menjadi **`returned`**, stok yang di-reserve saat export (jurnal `order_online`) dikembalikan otomatis via `StockService::reverseReference` (idempotent — re-import file yang sama tidak menggandakan).

### Nilai `aggregator_status` (6 nilai INGGRIS, `ShippingOrder::TRACKING_STATUSES`)
| Nilai | FLIK (`Status`) | SICEPAT (`Status`) | SPX (`Tracking Status`) |
|---|---|---|---|
| `waiting_pickup` | Dikonfirmasi | Menunggu pickup | Pending Pickup |
| `in_transit` | Sedang Diantar | Proses pengiriman | In Transit / Delivering |
| `delivered` | Dicairkan / Terkirim | Terkirim | Delivered |
| `returning` | Dalam Transit Pengembalian | Proses retur | Returning |
| `returned` | Dikembalikan | Retur | Returned |
| `problem` | Dikonfirmasi/Sedang Diantar **+** [14] "Status Terakhir dari 3PL" berawalan "Problem" | Bermasalah | Pending Pickup/In Transit/Delivering **+** [13] "Delivery OnHold Reason" berisi |

Raw status tak dikenal → `aggregator_status = null` (tetap dihitung `unmatched`). `delivered_at` diisi dari kolom waktu file hanya saat `delivered`: FLIK `Terakhir Update` (format `m/d/Y H:i`), SICEPAT `Tanggal Terkirim` (`d/m/Y H:i:s`), SPX `Delivered Time` (`d-m-Y H:i`). Parse datetime memakai urutan format per-sumber agar d/m (SICEPAT) tidak tertukar m/d (FLIK).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Services/AggregatorTrackingImportService.php` | `parse` (readRows via PhpSpreadsheet IOFactory csv/xlsx, detectSource 100% DB-driven dari `tracking_header_mappings`, mapHeaders DB-aware, mapStatus DB-driven), `import` (1 transaksi: batch `whereIn phone_normalized` → `resolveOrder` phone + customer_name → update awb/status/delivered_at → reverseReference bila jadi returned) |
| `app/Http/Controllers/OrderOnlineController.php` | `trackingImport` (validate `mimes:csv,txt,xlsx,xls` max 10MB, flash report: Total/Terisi/Stok dikembalikan/Ambigu/Tak cocok) |
| `app/Models/ShippingOrder.php` | const `TRACKING_STATUSES` |
| `resources/views/order/index.blade.php` | kartu "Upload Status Aggregator" (dropzone + JS fetch `orders.tracking-import`) |
| `routes/web.php` | `POST /orders/tracking-import` (`orders.tracking-import`) |
| `tests/Feature/AggregatorTrackingImportTest.php` | 9 test: mapStatus semua sumber, import per sumber (awb/status/delivered_at), returned balikin stok, idempotent re-import returned, tier-2 fallback, ambiguous, unmatched |

### Penting
- File dashboard aggregator **TIDAK memuat order_id kita** (SICEPAT "Nomor Referensi" & SPX "Customer Reference" kosong; FLIK Order ID = UUID) → pencocokan WAJIB signature, bukan order_id.
- Kolom `awb`/`aggregator_status`/`delivered_at` sudah ada (migration `2026_08_07_120000`), **tanpa migrasi baru**.
- xlsx: sel kosong bisa `null` → `normalizeHeader(?string)` dan `cellText()` (float → `%.0f`, eksponensial `2.63E17` diperluas) wajib tahan null/float.
- `delivered_at` hanya di-set saat `delivered`; jika status berubah keluar dari delivered, nilai lama dibiarkan.
- Test memakai DB `awannacoba` tanpa refresh → jangan pakai assert global count (AWB/status lintas-test terkontaminasi); scope ke order spesifik.

---

## H. ✅ Test Kit Pipeline di `filecoba/` (10 Agustus 2026)

### Deskripsi
Folder `filecoba/` berisi kit uji end-to-end pipeline order-online (import mentah → export 3 aggregator → tracking status + balik stok). Generator menulis 7 file CSV, lalu `verify_pipeline.php` menjalankan pipeline sungguhan (service asli) dan mencetak PASS/FAIL per langkah (saat ini **98/98 PASS**, bisa dijalankan ulang kapan saja).

### File
| File | Keterangan |
|---|---|
| `filecoba/generate_test_kit.php` | Generator mandiri (tanpa Laravel) → menulis 7 file di bawah |
| `filecoba/01_order_online_mentah.csv` | Data mentah 52 kolom, 10 order CBC-101..CBC-302 (pola `training/make_test_rules_csv.php`) |
| `filecoba/02_export_flik.csv` / `_sicepat` / `_spx` | **Referensi statis** format export RUNTIME saat ini (FLIK 1-kolom HP "62", bukan template lama 2-kolom) |
| `filecoba/03_tracking_flik.csv` / `_sicepat` / `_spx` | File dashboard aggregator (header asli: FLIK 29 kolom, SICEPAT 43, SPX 47) |
| `filecoba/verify_pipeline.php` | Boot Laravel → cleanup → import → export+diff → tracking → cek stok → idempotent |
| `filecoba/actual_export_*.csv` | Hasil export nyata dari service (artefak tiap run verify) |

### Skenario 10 order
- **CBC-101..105** (bank_transfer semua provinsi) → `flix-tf` → template FLIK: KMP+1.50, KMP+1 (qty2), KSP+2 (**Aurora**), KBJ+1.25 (qty2 → split), KCHP. FLIK jadi **2 gudang** (Aurora + GUDANG-PUSAT) → ZIP (rule F).
- **CBC-201..203** (cod Jawa/Bali) → `sicepat` → template SiCepat: KMP+1.75, KMP+2.25 (qty3), KCHP.
- **CBC-301/302** (pending+paid → `tembakan`) → selalu `spx` → template SPX: KBJ+1.50 qty2 (variation "Dapat 2"), KMP+1.25.
- Status tracking tersebar di 3 file mencakup **semua 6 nilai**: `waiting_pickup` (CBC-104), `in_transit` (CBC-102, CBC-202), `delivered` (CBC-101, CBC-201, CBC-301), `returning` (CBC-203), `returned` (CBC-103 FLIK, CBC-302 SPX — uji balik stok), `problem` (CBC-105 FLIK, 3PL "Problem...").

### Verifikasi yang dicakup
- Import: courier, status (real/tembakan), qty override "Dapat 2", `product_code` = kode varian (`KMP+1.5` dll), nama "... 2 pcs".
- Export: hasil service **identik baris-per-baris** dengan referensi statis (dimensi 10/8/6, berat 1, catatan kurir default, nama kacamata `+power N pcs`, SPX phone mulai 8 + CAPSLOCK, warehouse per-baris).
- Stok: `recordOutWithPackaging` (KBJ split: KBJ −ceil(qty/2), KDF −floor(qty/2), BOX/LAP −floor(qty/2)); `returned` → `reverseReference` balikin stok varian asal; delivered/in_transit tetap ter-reserve; **re-import idempotent** (`stock_returned=0`).

### Menjalankan
```
php filecoba/generate_test_kit.php        # tulis ulang 7 file (opsional)
php filecoba/verify_pipeline.php          # jalankan pipeline & cek PASS/FAIL
```
- Skrip memakai DB aktif (`.env`, `awannacoba`) dan **TIDAK** menjalankan seeder (precheck hanya memastikan `courier_rules` + produk/varian ter-seed; `CourierRuleSeeder` bersifat truncate). Bila kosong, seed manual dulu.
- Re-runnable: di awal verify, order CBC-* lama dihapus + jurnal `order_online` dibalik (`reverseReference`) sehingga stok kembali baseline.
- `product_price` DB → `amount` (gross_revenue CSV) → sel export "169000.00" (bukan "169000"); compare `delivered_at` sebagai string (kolom ber-cast datetime → Carbon).
- Step 6 (re-export setelah tracking): semua order yang sudah terisi `awb` TIDAK ikut di-export lagi (hanya header xlsx per template). Query di step ini wajib menyertakan filter `awb` kosong yang SAMA dengan `download()`.

---

## I. ✅ Order Ber-AWB: Non-exportable & Non-editable (11 Agustus 2026)

### Deskripsi
Order yang sudah punya resi (`shipping_orders.awb` terisi dari upload status aggregator) dianggap sudah dikirim → **TIDAK ikut diekspor** ke template aggregator (cegah reserve stok ganda / ekspor ulang barang yang sudah berangkat) dan **TIDAK bisa diedit** (courier/product_code dikunci; tombol Edit di UI diganti badge hijau AWB + status).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Services/OrderTemplateExportService.php` | `download()` menambah filter `->where(fn ($q) => $q->whereNull('awb')->orWhere('awb', ''))` sebelum `with('variant')` — order ber-AWB dilewati dari export & reserve stok; docblock diupdate |
| `app/Http/Controllers/OrderOnlineController.php` | `index()`: `courierCounts` pakai filter awb kosong SAMA (angka dropdown export konsisten); `update()`: guard `if (! empty($shippingOrder->awb)) return back()->withErrors(['order' => 'Order sudah memiliki resi (AWB), tidak bisa diedit.'])` |
| `resources/views/order/index.blade.php` | Kolom Aksi: `@if(!empty($o->awb))` → badge hijau `✓ {awb}` (`#d1fae5`/`#065f46`) + `aggregator_status` kecil; `@else` → `<details>` Edit lama; `@endif` |
| `tests/Feature/OrderOnlineTest.php` | +2 test: `test_export_excludes_orders_with_awb` (export hanya memuat order tanpa AWB; order ber-AWB TIDAK punya jurnal `out` — cek via `StockMovement` per `reference_id`, bukan stok varian bersama), `test_update_rejected_when_order_has_awb` (PUT → `assertSessionHasErrors('order')`, courier tetap) |
| `filecoba/verify_pipeline.php` | Step 6 baru: setelah tracking semua 10 order ber-AWB → re-export tiap template hanya menghasilkan baris header (0 order), query wajib memakai filter awb kosong yang sama |

### Penting
- `awb` bisa `null` atau `''` → filter pakai `whereNull('awb')->orWhere('awb','')`.
- **Jangan memakai stok varian bersama untuk cek "tidak di-reserve"** saat order ber-AWB & tanpa-AWB memakai varian sama (baris tanpa-AWB ikut mengurangi stok varian itu). Cek jurnal per `reference_id`.
- Re-export setelah tracking (order sudah ber-AWB) → `StockService` tidak lagi memanggil `recordOut` untuk order itu (sudah tidak lolos filter query), jadi `reserveStock` tetap idempotent.

---

## I. ✅ Bulk Edit Spending — FAB ✏️ Edit, edit per baris (11 Agustus 2026)

### Deskripsi
FAB bulk action di halaman Spending (`index-advertiser` & `index-general`) kini punya opsi **✏️ Edit** di samping Hapus. Modal mengenali data terpilih SATU PER SATU: dikelompokkan **per tanggal → per produk** (header tanggal hanya tampil bila pilihan lintas tanggal; pilihan lintas produk = grup per produk). Tiap baris menampilkan whitelist + nilai lama + input spending/lead/paid yang **sudah terisi nilai lama** (langsung bisa di-tweak). Simpan mengirim `items[<db-id>][spending|lead|paid|id]` per baris → `POST /spending/bulk-update`.

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/SpendingHarianController.php` | `bulkUpdate()` terima `items[]` per baris (id/spending/lead/paid); scope advertiser (miliknya) / CS (advertiser yang diampu); baris di luar scope dilewati (flash "N dilewati"); update per baris dalam 1 transaksi; dedup notifikasi CS per owner SETELAH commit; `recalculateWhitelistTotals()` helper (dipakai `store`/`bulkDestroy`/`bulkUpdate`) |
| `routes/web.php` | `POST /spending/bulk-update` → `spending.bulk-update` |
| `resources/views/spending/index-advertiser.blade.php` & `index-general.blade.php` | checkbox + `data-tanggal/product-id/product-name/product-code/whitelist-name/whitelist-code`; modal `.be-modal` render dinamis per grup (scroll `max-height:48vh`); tombol Simpan `type=button` → `reportValidity()` + `form.submit()` (deterministik, Enter-key juga jalan karena input sudah inline di form); `beEsc()` anti-XSS nama; grup produk diurutkan by nama |
| `tests/Feature/SpendingBulkUpdateTest.php` | 6 test: update per item + recalc whitelist, lintas produk/tanggal dgn keying form asli `items[<id>]`, scope advertiser, CS terpetakan, CS luar tim, payload invalid |

### Penting
- FAB bulk baru benar-benar menempel viewport setelah fix `animations.js` (PageTransition.enter membersihkan transform inline `#main-content`; transform pada ancestor membuat `position:fixed` terkurung sebagai containing block).
- `bulkDestroy` ikut di-scope CS + dedup notif (konsisten dengan `bulkUpdate`).
- Desain lama "nilai sama untuk semua baris" diganti per-baris; field kosong dicegah native validation (`required`).
- `recalculateWhitelistTotals(iterable $whitelistIds)`: 1 query aggregate → map → update (pola batch AGENTS.md).

---

## I. ✅ Fix: Upload Spending Meta — kolom produk skema baru (11 Agustus 2026)

### Deskripsi
Error `SQLSTATE[42S22]: Unknown column 'kode_produk'` saat upload file Meta Ads Manager → `SpendingHarianController` masih memakai kolom lama `kode_produk`/`nama_produk`, padahal `products` sudah migrasi ke skema baru **`code`/`name`** (fitur E).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/SpendingHarianController.php` | 4 titik diganti ke skema baru: `parseUpload` (`Product::aktif()->get(['id','code','name'])` + sort by `code`), `parseRegionalFile` (`$prod->name.' ('.$prod->code.')'`), `matchProduct` (`$p->code`) |
| `tests/Feature/SpendingUploadTest.php` | Test regresi alur upload: file Ads (nama file memuat kode whitelist + tanggal) + file regional (kolom `product`/`payment_status`/`created_at`) → `parseUpload` harus sukses tanpa SQL error, `regional_unmatched_count=0`, gabungan spending/lead/paid benar |

### Penting (pola test upload)
- `UploadedFile::fake()->createWithContent()` memanggil `basename()` pada nama file → **jangan pakai nama file ber-slash** (mis. `s/d`) karena terpotong; kode whitelist test memakai format numerik tanpa dash (`20xxxxxx`) agar cocok dengan `preg_split('/\s*-\s*/')` 3-area di `parseRegionalFile` (kode ber-dash seperti `WL-xxx` ikut terbelah).
- File nyata test di-sniff finfo → `text/plain` dan gagal validasi `mimes` → wajib `createWithContent()` (mime dari ekstensi nama).

---

## J. ✅ Ringkasan Periode — 4 Kartu Summary di Halaman Spending Advertiser (11 Agustus 2026)

### Deskripsi
Di antara card filter rentang periodik dan tabel utama, kini ada 4 kartu ringkasan: **Total Spending**, **Total Lead / Paid**, **CPA Lead / Paid**, dan **Paid Ratio** (dengan progress bar berwarna hijau/kuning/merah sesuai ambang tabel). Semua kartu mengikuti rentang `dari`/`sampai` yang dipilih karena dihitung dari koleksi `$rows` yang SUDAH ter-scope `user_id` + `whereBetween('tanggal')` — tanpa query tambahan (anti N+1).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/SpendingHarianController.php` | `indexAdvertiser()` hitung `$summary` (spending/lead/paid + paid_ratio & cpa_lead/cpa_paid dengan guard pembagi nol, round konsisten dengan tabel) dari `$rows`; di-pass via `compact('summary', ...)` |
| `resources/views/spending/index-advertiser.blade.php` | grid `.summary-grid` 4 kartu `.summary-card` (ikon gradient, hover lift, sub-label jumlah hari berisi data + rentang periode); CSS responsive `@media` 1100px/560px; progress bar `.summary-ratio-track`/`.summary-ratio-fill` |
| `tests/Feature/SpendingSummaryTest.php` | 2 test: kartu tampil dengan total periode (Rp 150.000, 15 lead/5 paid, ratio 33%), dan nilai berubah mengikuti rentang (rentang sempit → Rp 100.000/40%; data di luar rentang tidak bocor → Rp 0) |

### Penting
- Perhitungan konsisten dengan tabel: sum dulu, bagi kemudian (`paid/lead*100`, `spending/lead`, `spending/paid`) — bukan rata-rata dari ratio per tanggal.
- Empty state aman: tanpa data → `Rp 0`, `0%`, bar lebar 0.
- Hanya sisi advertiser (`index-advertiser`); halaman general (CS/admin) tidak diubah.

---

## K. ✅ Responsive Mobile — Halaman Spending Advertiser + Date Range Picker (11 Agustus 2026)

### Deskripsi
Semua elemen halaman spending advertiser (`index-advertiser`) kini fleksibel di segala ukuran layar (HP/tablet/desktop). Komponen `date-range-picker` ikut di-responsifkan global (berlaku juga di regional, team/performance, spending general) tanpa mengubah markup pemakaian.

### Perubahan
| File | Keterangan |
|---|---|
| `resources/views/components/date-range-picker.blade.php` | Trigger diberi class `.drp-trigger` (min-width 220px pindah dari inline ke CSS, label `.drp-label` `flex:1;min-width:0` + ellipsis agar bisa menyusut); popup diberi class `.drp-popup/.drp-popup-panel/.drp-panel-inner/.drp-presets/.drp-calarea/.drp-cals/.drp-footer` + `@push('styles')`: ≤640px popup jadi **bottom-sheet** (panel full-width, preset jadi chip horizontal scroll, kalender stack vertikal, footer sticky bawah), label trigger mengecil |
| `resources/views/spending/index-advertiser.blade.php` | Form filter diberi class `.filter-bar` (inline `flex-wrap` dipindah ke class agar media query bisa flip): ≤640px `flex-wrap:nowrap` → **rentang periodik + Reset + Input Spending tetap 1 jajar** (picker `flex:1`). Class `lvl2-header/lvl2-sub/lvl2-summary/lvl3` pada konten expand + CSS mobile: sel tabel utama & tabel whitelist lebih ramping (`!important` karena inline style), header produk boleh wrap, FAB `.bulk-bar` melayang penuh di bawah layar, modal `.dc-modal`/`.be-modal` jadi bottom-sheet ≤480px, kartu summary lebih padat |

### Penting (kaskade CSS)
- `@stack('styles')` dirender **SEBELUM** `<style>` layout → rule yang bentrok dengan media query layout (mis. padding `.clay-table` di 767px) WAJIB spesifisitas lebih tinggi (`table.clay-table`) atau `!important`; inline style di sel tabel juga hanya bisa ditimpa dengan `!important`.
- Popup DRP dibuka JS via inline `display:flex` (class `display:none` hanya default) — jangan sentuh mekanisme itu.
- `align-items`/`justify-content` popup dipindah dari inline ke class agar media query bottom-sheet bisa override.
- `.drp-trigger { min-width:0 }` di ≤640px berlaku global (semua halaman pemakai komponen) — di halaman tanpa `.filter-bar`, trigger hanya menyusut ke konten (aman, form tetap wrap).
- Footer popup sticky pakai `position:sticky; bottom:0` + `background:#fff` (di dalam panel scroll `overflow-y:auto`).

---

## L. ✅ Pencocokan Produk Kampanye Ads — kode di mana pun, diapit "-" (11 Agustus 2026)

### Deskripsi
`SpendingHarianController::matchProduct()` (dipakai `parseMetaFile`/`parseUpload` untuk mencocokkan nama kampanye Meta Ads → produk) sebelumnya hanya mencocokkan **kode di awal nama** (prefix) lalu fallback contains. Padahal penempatan kode produk di nama kampanye **berbeda-beda per advertiser**. Kabar baiknya semua advertiser memakai penanda **"-" di kiri-kanan kode** (contoh `INIT - 11/8/26 - KBJ - 1` → kode `KBJ` di tengah).

### Urutan pencocokan baru
1. **Token utuh**: nama dibaca SELURUHNYA, `preg_split('/\s*-\s*/')`, tiap token dibuang sufiks varian (`explode('+')[0]`, mis. `ksp+1.50` → `ksp`) lalu dibandingkan **exact** (case-insensitive) dengan kode produk. Token pertama yang cocok = produknya (produk sudah di-sort kode terpanjang dulu di `parseUpload`).
2. **Prefix** (format lama — kompatibel: `KSP Promo`, `KSP+1.50 - …` tetap cocok).
3. **Contains** (fallback terakhir, perilaku lama).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/SpendingHarianController.php` | `matchProduct()` ditulis ulang: token → prefix → contains |
| `tests/Feature/SpendingUploadTest.php` | +2 test: kode di tengah nama (`INIT - 11/8/26 - {code} - 1`) & token ber-sufiks varian (`{code}+1.50` di tengah) → produk & spending tercocokkan benar |

### Penting
- Token yang bukan alfanumerik persis (mis. `11/8/26` — memuat `/`) tidak bisa cocok dengan kode (semua kode alfanumerik: KMP/KSP/KBJ/KCHP/SH/KNGH/BOX/LAP/KDF + kode test `P…`), sehingga token angka/tanggal aman dari false positive.
- Test lama (`{code} Campaign`, format prefix) tetap lolos → tidak ada regresi format lama.
- Kode yang sama muncul 2× di satu nama → token pertama yang menang (sesuai kaidah "kode pertama = produk").

---

## M. ✅ Batas Tinggi Tabel Performa Team — ±7 baris (11 Agustus 2026)

### Deskripsi
Tabel performa team (`team/performance.blade.php`, sisi advertiser & CS) dibatasi tingginya agar hanya menampilkan **±7 baris data CS**, sisanya bisa di-scroll vertikal di dalam container (pola sama dengan batas 5 baris tabel spending).
## M. ✅ Halaman Admin Kelola Aturan Courier (Dinamis dari DB) (12 Agustus 2026)

### Deskripsi
Aturan auto-mapping kurir (tabel `courier_rules`) kini bisa dikelola langsung dari aplikasi lewat halaman **Aturan Courier** (`/courier-rules`) — tanpa ubah kode/seeder. Aturan tersimpan di DB sejak fitur D; halaman ini menutup celah "cara mengubahnya" (sebelumnya hanya via seeder yang truncate, atau akses DB manual).

### Cara kerja (tetap + perluasan fitur P)
- Evaluasi 2 fase (sejak 13 Agustus, fitur P): **fase 1 = rule khusus produk** (`product_code` terisi, selalu menang) → **fase 2 = rule umum** (`product_code` null, perilaku lama). Dalam tiap fase dievaluasi berurutan dari `sort_order` terkecil; rule pertama yang cocok (`payment_method` + `province`) menang. `payment_method`/`province` null = berlaku semua.
- Fallback bila tidak ada rule cocok: `spx` (konstanta `FALLBACK_COURIER`, belum dinamis).
- `CourierRuleService::resolve(paymentMethod, province, productCode?)` membaca DB per-request (cache per instance) → perubahan langsung berlaku untuk import order berikutnya.

### Implementasi
| File | Keterangan |
|---|---|
| `resources/views/team/performance.blade.php` | Kedua wrapper tabel (`<div style="overflow-x:auto;">`) diberi class `.perf-scroll-limit` (overflow-y:auto + scrollbar tipis); script baru: hitung `maxHeight = tinggi header + 7 baris data + tinggi baris GRAND TOTAL` (baris total sticky-bottom dihitung agar tidak menutupi baris ke-7), pass-2 setelah scrollbar muncul + re-measure saat resize; gaya JS `const`/arrow (konsisten dgn script donut di file yang sama) |

### Penting
- Baris data di-deteksi dari `tbody.rows` yang inline `position` BUKAN `sticky` (baris GRAND TOTAL memakai `position:sticky;bottom:0` inline) & bukan `display:none` — jika baris total dipindah ke CSS class, filter ini harus disesuaikan (sudah dicatat sebagai komentar di script).
- Header (2 baris, sticky top) & kolom sticky kiri/kanan tetap berfungsi di dalam container scroll karena tabel memakai `border-collapse:separate`.
- ≤7 CS / empty state → tanpa batasan (tinggi natural).

---

## J. ✅ Ringkasan Periode — 4 Kartu Summary di Halaman Spending Advertiser (11 Agustus 2026)

### Deskripsi
Halaman spending sisi advertiser kini punya 4 kartu summary (Total Spending, Total Lead/Paid, CPA Lead/Paid, Paid Ratio) di antara area filter rentang periodik dan tabel utama. Seluruh kartu terpengaruh rentang periodik yang sedang aktif. Warna paid ratio: <50% merah, 50–75% kuning, >75% hijau.
| `app/Http/Controllers/CourierRuleController.php` | `index/store/update/destroy/toggle/move`; normalisasi `payment_method` lowercase & `province` uppercase; validasi courier `in:COURIERS`; cek duplikat kombinasi (payment+province) |
| `routes/web.php` | `GET/POST /courier-rules`, `PUT /courier-rules/{rule}`, `PATCH …/toggle`, `POST …/move/{up|down}`, `DELETE …/courier-rules/{rule}` (nama `courier-rule.*`) |
| `resources/views/courier_rule/index.blade.php` | Form tambah (sort_order, payment_method+datalist, province+datalist master, courier select, aktif) + tabel rules (badge metode/provinsi/courier, toggle status, tombol ↑/↓ reorder, edit via modal, hapus) + info box cara kerja |
| `resources/views/layouts/app.blade.php` | Sidebar Data Master → "Aturan Courier" (owner/super_admin/mentor/admin) |
| `tests/Feature/CourierRuleTest.php` | 11 test: render, store+resolve dinamis, normalisasi, duplikat ditolak, validasi courier, update+resolve, toggle (nonaktif → fallback), destroy, move swap, sort_order kecil menang |

### Penting
- Test memakai DB aktif tanpa refresh (pola project) → setiap rule test memakai provinsi unik prefix `TEST PROVINCE` dan di-delete di akhir test agar tak mengganggu rules asli.
- Pindah prioritas (↑/↓) menukar `sort_order` dengan rule tetangga dalam 1 transaksi; baris pertama/last tombol di-disable.
- Rule dengan kombinasi (payment_method, province) sama ditolak (redundan — yang `sort_order` kecil selalu menang).
- Halaman hanya mengelola tabel `courier_rules`; daftar courier valid (`COURIERS`) & fallback (`FALLBACK_COURIER`) masih konstanta PHP (scope berikutnya bila ingin 100% dinamis).

---

## N. ✅ Aturan Export Dinamis — Upload Template CSV + Mapping Kolom (12 Agustus 2026)

### Deskripsi
Pemetaan kolom saat export `shipping_orders` → template courier (FLIK/SiCepat/SPX) **tidak lagi hardcoded** di `OrderTemplateExportService`. Admin meng-upload **template CSV** (baris pertama = header kolom) lewat menu **Aturan Export** (`/export-mapping`), lalu mencocokkan tiap header dengan **sumber isi**; mapping disimpan di DB (`export_template_mappings`) dan export mengikutinya.

### Sumber isi per kolom (dropdown di UI)
| source_type | Makna | Contoh |
|---|---|---|
| `column` | Kolom `shipping_orders` (registry `ExportMappingService::COLUMNS`, 25 kolom) | `customer_name`, `phone_normalized`, `amount` |
| `computed` | Nilai khusus hasil perhitungan (registry `COMPUTED`, 15 key) | `warehouse` (KSP→Aurora/SH→GTM), `product_name_display` (+power), `phone_spx` (mulai 8), `weight_1`, `pack_length/width/height` (10/8/6), `default_courier_note`, `cod_flag`, `cod_amount`, `payment_method_upper`, `province/city/district_upper` (CAPSLOCK), `order_id_50` |
| `static` | Teks tetap yang diketik admin | `'Barang'` (Jenis Paket SiCepat), `'N'` (Asuransi SPX) |
| `empty` | Dikosongkan | Kelurahan FLIK, kolom DO Balik SiCepat |

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/SpendingHarianController.php` | `indexGeneral()` hitung summary batch per rentang (1 query aggregate, bukan per baris) |
| `resources/views/spending/index.blade.php` | 4 kartu summary + logic warna ratio |

## K. ✅ Top Up: Area Sisa Saldo Berdampingan + Centang Whitelist (11 Agustus 2026)

### Deskripsi
Halaman `topup/create` kini punya **dua area berdampingan** (grid 1fr 1fr, collapse 1 kolom di ≤900px):
- **📋 Rencana Top Up per Whitelist** — tiap whitelist punya **checkbox** untuk memilih whitelist yang di-top up; input nominal `disabled` sampai dicentang. Total hanya menghitung baris tercentang.
- **💳 Input Sisa Saldo per Whitelist** — otomatis menampilkan whitelist yang ber-**spending kemarin** (aggregate `SUM(spending/lead/paid)` per whitelist, 1 query batch `whereDate` + `groupBy`), input sisa saldo default = `sisa_saldo` saat ini.

> Tahap ini **UI dulu**: `store()` belum menyimpan field `sisa_saldo[*]` (ada info kecil di view). Sisa saldo saat ini tetap di-input di tahap akhir (confirm setelah VA dibayar) — pemindahan penyimpanan ke awal menyusul.

### Penting
- Baris tidak dicentang → `syncRowState()` men-disable **input nominal DAN hidden `items[id][whitelist_id]`** sekaligus. Kalau hidden whitelist_id tidak ikut di-disable, baris tak dicentang tetap terkirim `items[id]` tanpa `nominal` → validasi `items.*.nominal required` gagal membingungkan.
- `sisa_saldo` adalah **accessor** (`total_topup − total_spending`), bukan kolom DB.
- Query spending kemarin memakai `whereDate('tanggal', now()->subDay())` + `whereIn` whitelist milik advertiser (aman saat whitelists kosong — Laravel `0=1`).
| `database/migrations/2026_08_12_100000_create_export_template_mappings_table.php` | `template` (flik/sicepat/spx), `column_index`, `header`, `source_type`, `source_value`, `is_active`; UNIQUE `(template, column_index)` |
| `app/Models/ExportTemplateMapping.php` | model sederhana |
| `app/Services/ExportMappingService.php` | registry `COLUMNS`/`COMPUTED`/`SOURCE_TYPES`; `mappingFor()` (cache per request, order by column_index); `parseTemplateFile()` (baca header CSV + BOM + buang trailing empty); `matchHeaders()` (bawa mapping lama by nama header saat upload ulang); `saveMapping()` (replace per template dlm 1 transaksi) |
| `database/seeders/ExportTemplateMappingSeeder.php` | seed 3 template bawaan (65 row: FLIK 16, SiCepat 27, SPX 22) — **meniru persis layout hardcoded lama** → export identik sebelum diedit |
| `app/Http/Controllers/ExportMappingController.php` | `index` (3 tab + mapping), `upload` (parse header, JSON), `save` (validasi sumber: kolom/computed harus ada di registry, static tidak boleh kosong) |
| `app/Services/OrderTemplateExportService.php` | `writeRows()` → `buildRows()` dari mapping DB; `resolveCell()`/`columnValue()`/`computedValue()` (transform lama dipindah ke sini); `flikRows/sicepatRows/spxRows` DIHAPUS |
| `resources/views/export_mapping/index.blade.php` | tabs FLIK/SiCepat/SPX, tombol upload CSV (fetch → draft), tabel header + dropdown sumber isi (optgroup kolom/nilai khusus/teks tetap), simpan per template |
| `resources/views/layouts/app.blade.php` | Sidebar Data Master → "Aturan Export" |
| `tests/Feature/ExportMappingTest.php` | 10 test: index, upload parse, upload bawa mapping lama, save, tolak kolom tak dikenal/static kosong, export custom mapping, **regresi layout seeded vs lama**, export tanpa mapping → RuntimeException, SPX transform (phone 8/CAPSLOCK/COD) |
| `tests/Feature/OrderOnlineTest.php` | +`setUp()` seed `ExportTemplateMappingSeeder` (export test butuh mapping DB) |
| `filecoba/verify_pipeline.php` | precheck `export_template_mappings`; panggil `buildRows()` (bukan `flikRows` dll); hasil **103/103 PASS** |

### Penting
- `buildRows(template, orders, sender)` mengembalikan `[header, ...data]` — dipakai `writeRows()` & pipeline (via reflection). `resolveCell` memakai `$sender` utk computed `warehouse` (Kode Warehouse FLIK).
- Kolom tanggal (`delivered_at`) diformat `Y-m-d H:i` saat ditulis.
- Export **gagal dengan pesan jelas** bila mapping template belum diatur (mis. user hapus semua baris mapping).
- Upload ulang template tidak menghilangkan mapping lama — `matchHeaders()` mencocokkan by nama header (normalized).
- Registry `COLUMNS`/`COMPUTED` adalah satu-satunya sumber kebenaran (dropdown UI = resolver service) — tambah key di dua tempat tsb bila perlu sumber baru.

---

## N1. ✅ Template Export BEBAS (Custom) + Halaman Kelola Terpisah (12 Agustus 2026)

### Deskripsi
Template export tidak lagi terbatas 3 bawaan: tabel **`export_templates`** (key/name/couriers/is_active) jadi master; `export_template_mappings.template` menyimpan `key` (relasi string, tanpa alter tabel mapping). Admin bisa **buat template baru** (mis. JNE) yang langsung muncul sebagai tombol export di Data Mentah. Tampilan dipecah: **index = daftar** (kartu per template + tombol **Edit**/**Hapus**) dan **create = halaman terpisah** (`/export-mapping/create`); edit juga halaman terpisah (`/export-mapping/{tpl}/edit`). **Hapus = permanen** (mapping ikut hapus; SPX tetap jadi safety net bila template hilang).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/TopUpController.php` | `create()`: import `SpendingHarian`, query batch spending kemarin → `$sisaSaldoWhitelists` (properti dinamis `spending_kemarin`/`lead_kemarin`/`paid_kemarin`); `store()`: pesan error custom + atribut `items.{id}.nominal` → nama whitelist |
| `resources/views/topup/create.blade.php` | `.topup-split` grid dua card, checkbox `.wl-select`, JS `syncRowState()`/`hitungTotal()` (hitung baris tercentang saja); tinggi card disamakan via `align-items:stretch` + `height:100%` + `flex:1` pada daftar item |

> **Validasi submit top-up (fix 11 Agustus):** whitelist dicentang tapi nominal kosong → dulu error membingungkan `The items.8.nominal field is required.`. Kini: form pakai **`novalidate`** (native browser dimatikan agar guard JS yang jalan), guard submit mengecek `.wl-select:checked` dengan nominal kosong/negatif → `preventDefault` + alert nama whitelist (`WL_NAMES` dari `@json`) + highlight merah + scroll ke baris. `store()` punya pesan custom `Nominal top up untuk :attribute wajib diisi` dgn atribut dinamis `items.{id}.nominal` → nama whitelist. Saat validasi gagal, `old('items.{id}.nominal')` memulihkan centang + nilai input (`data-was-filled` + `restoreRowState()`), jadi user tidak mengetik ulang. |

> ⚠️ **Reserved word MySQL — kolom `lead` WAJIB backtick di raw SQL** (fix 11 Agustus): `spending_harians.lead` adalah **reserved word** di MySQL 8.4 → `SUM(lead)` tanpa backtick memicu `SQLSTATE[42000] 1064`. SELALU tulis `SUM(\`lead\`)` di `selectRaw` (contoh kanonik: `DashboardController`, `SpendingHarianController`, `RegionalController`). Kolom `spending` & `paid` aman tanpa backtick.
| `database/migrations/2026_08_12_120000_create_export_templates_table.php` | `key` unique, `name`, `couriers` (JSON), `is_active`; seed 3 bawaan (flik→4 flix-*, sicepat→[sicepat], spx→[spx]) |
| `app/Models/ExportTemplate.php` | fillable+casts `couriers` array; relasi `mappings()` (hasMany via `template`=key) |
| `app/Services/ExportMappingService.php` | + `templates()`, `template(key)` (cache), `couriersForTemplate(key)` (**DB-driven** + fallback `LEGACY_COURIERS` bila row terhapus), `createTemplate(name, couriers, items)` (key auto-slug `Str::slug`, couriers kosong → `[key]`), `updateTemplate`, `deleteTemplate` (transaksi hapus mapping+row) |
| `app/Http/Controllers/ExportMappingController.php` | `index` (list + `withCount` kolom), `create`, `store`, `edit`, `update`, `destroy` (permanen), `upload` (template **opsional** — create tanpa key → semua empty; edit dengan key → mapping lama terbawa) |
| `routes/web.php` | 7 route: index, create, store (POST), edit, update (PUT), destroy (DELETE), upload |
| `resources/views/export_mapping/index.blade.php` | Daftar kartu template: ikon, nama, key, jumlah kolom, badge courier, status mapping, tombol **✏️ Edit** & **🗑 Hapus** (confirm) + tombol **➕ Template Baru** |
| `resources/views/export_mapping/form.blade.php` | Editor bersama create/edit: Nama Template, Courier (koma; kosong→key), Upload CSV (fetch draft + carry-over), tabel mapping + `items[]` via JS submit |
| `app/Http/Controllers/OrderOnlineController.php` | `index` pass `$exportTemplates`; `export()` cek template via `ExportTemplate::where('key')` (custom OK), FLIK tetap butuh courier valid |
| `resources/views/order/index.blade.php` | Tombol export di-loop dari `$exportTemplates`: key `flik` → dropdown per courier (tetap), lainnya → tombol `📦 Export {name}` |
| `tests/Feature/ExportMappingTest.php` | 14 test: index list + aksi, create/edit render, upload parse + carry-over, store custom (slug key, couriers default), tolak dobel index/kolom tak dikenal, update, destroy permanen, **export template custom** (courier lain tidak ikut), regresi seeded layout, export tanpa mapping → RuntimeException, SPX transform |

### Penting
- `couriersForTemplate()` kini baca `export_templates.couriers` (fallback legacy utk 3 key bila row terhapus) — `OrderTemplateExportService` mendelegasikan ke `ExportMappingService`.
- Template custom: couriers kosong → `[key]` (nama template dipakai sebagai courier). Export memfilter order by courier tsb.
- `upload` endpoint: `template` opsional (create tidak punya key dulu).
- Test `OrderOnlineTest::test_order_page_renders` tetap lolos (`FLIK — flix-tf` masih dirender dari dropdown dinamis).
- Pipeline `filecoba/verify_pipeline.php` tetap **103/103 PASS** (precheck `export_template_mappings` + `buildRows` via reflection).

---

## O. ✅ Halaman Gudang — 3 Kategori Barang + Aturan Kemasan Dinamis + Acuan Restock (13 Agustus 2026)

### Deskripsi
Barang gudang dikelompokkan jadi **3 tipe** (kolom `products.goods_type`): **Barang Pasti** (`consumable`), **Barang Inti** (`core`), dan **Barang Additional** (`additional`). Halaman `/gudang` = **1 GUDANG 1 HALAMAN**: user memilih gudang (kartu picker, query `inventory_id`), lalu halaman hanya menampilkan isi gudang itu — Barang Pasti (semua produk consumable, stok per gudang ini, form +/− manual), Barang Inti (produk yang gudang induknya = gudang ini, mis. **SH → GTM**, **KSP → Aurora**), Barang Additional, dan **aturan kemasan** (global + khusus gudang ini). **CRUD PRODUK & VARIAN dipindahkan ke halaman Gudang** (halaman `/product` dihapus total) — saat tambah produk dari halaman gudang, `inventory_id` otomatis = gudang yang dibuka (tidak perlu pilih lagi, tidak ada risiko semua barang mengacu ke Gudang Pusat) dan **varian default dibuat otomatis** (kode = kode produk, power 0; stok awal opsional). Bersamaan dengan ini, mapping gudang di-*swap*: **KSP→Aurora, SH→GTM** (sebelumnya terbalik) dan `products.inventory_id` diselaraskan (seeder + data dev).

### Karakteristik tiap tipe
| Tipe | `goods_type` | Perilaku stok | Contoh |
|---|---|---|---|
| Barang Pasti | `consumable` | **Ada di tiap gudang**; stok **manual oleh admin PER GUDANG** (jurnal `manual` + `inventory_id` via halaman Gudang) — hitungan tidak menentu | Kertas Thermal (`KTH`), Lakban (`LAK`), Bubble Wrap (`BUB`) |
| Barang Inti | `core` | Berkurang **otomatis** saat export order-online / import shipment | KMP, KSP, KBJ, KDF, KCHP, SH, KNGH, KP |
| Barang Additional | `additional` | Berkurang **otomatis** mengikuti barang inti sesuai `packaging_rules` | BOX, LAP |

### Aturan kemasan dinamis (`packaging_rules`) — PER GUDANG
- Tabel `packaging_rules (source_product_id, target_product_id, inventory_id, qty_per, rule_type, is_active)` — `rule_type` = **`additional`** (setiap `qty_per` unit barang inti → 1 unit pendamping keluar, target varian default) atau **`split`** (barang inti dipecah: main = `ceil(qty/qty_per)`, target = `floor(qty/qty_per)`, varian target POWER SAMA fallback power terkecil). UNIQUE `(source, target, inventory_id)` (`inventory_id` NULL = berlaku semua gudang; MySQL membolehkan banyak NULL di unique).
- **Rule khusus gudang menimpa rule global** untuk kombinasi source→target yang sama (`packagingRulesFor(productId, inventoryId)` = rule spesifik `union` rule global, cache per instance).
- `StockService::recordOutWithPackaging()` membaca aturan **dari DB** (bukan hardcode): split rules menentukan qty utama (`ceil`) + target bonus (`floor`, power sama); additional rules menentukan target pendamping (varian default); rule nonaktif di-skip; target tanpa varian aktif → `RuntimeException` ("Produk BOX belum terdaftar") → rollback atomik. Semua movement (main/split/additional) tercatat dengan `inventory_id` = gudang induk produk inti (untuk stok per gudang yang benar).
- Seed default (idempotent di `ProductSeeder::seedPackagingRules()`, dipanggil SETELAH produk dibuat): 6 rule **additional** KMP/KSP/KBJ → BOX/LAP `qty_per=2` global + 2 rule **split** KMP/KBJ → KDF `qty_per=2` global (promo "Beli 1 Dapat 2"; KSP TIDAK dapat KDF). Admin bisa ubah rasio/jenis, nonaktifkan, tambah rule (global atau per gudang), atau hapus di halaman Gudang.
- **Split KBJ→KDF & KMP→KDF kini data-driven** (`rule_type=split`) — tidak ada lagi special-case hardcode di StockService (kecuali guard "KDF tidak dikirim sendiri").

### Stok per gudang & acuan re-stock (`products.min_stock`)
- Stok per gudang dihitung dari jurnal (`stock_movements.inventory_id`): Barang Pasti menampilkan tabel per inventory dengan form +/− manual **per gudang**; Barang Inti/Additional menampilkan kolom "Stok per Gudang" (chip per inventory). `StockService::stockOf(variantId, ?inventoryId)` memfilter per gudang.
- `recordIn`/`recordOut` menerima parameter baru `inventoryId` (paling akhir, backward-compatible); pembelian (`PurchaseController`) & reserve export/shipment ikut mencatat `inventory_id` gudang induk produk.
- `min_stock` di-set **per produk** di form Produk (satu acuan total). Halaman Gudang menampilkan badge **⚠ Perlu Restock** (merah) saat `total stok produk ≤ min_stock`; selain itu **Stok Aman** (hijau).

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_13_100000_add_goods_type_and_min_stock.php` | `products.goods_type` enum (consumable/core/additional, default core) + index; `products.min_stock` unsigned int default 0 |
| `database/migrations/2026_08_13_100001_create_packaging_rules_table.php` | tabel `packaging_rules` + UNIQUE `(source_product_id, target_product_id)` |
| `database/migrations/2026_08_13_100003_add_inventory_to_packaging_rules.php` | + `inventory_id` nullable (FK inventories, nullOnDelete) + UNIQUE `packaging_rules_combo_unique (source, target, inventory)` — idempotent, nama index eksplisit pendek (default-nya > 64 char MySQL) |
| `database/migrations/2026_08_13_100002_add_manual_to_stock_movements_reference.php` | enum `stock_movements.reference` + `'manual'` |
| `app/Models/PackagingRule.php` | model + relasi sourceProduct/targetProduct |
| `app/Models/Product.php` | `goods_type`/`min_stock` fillable + constants `GOODS_TYPES`/`GOODS_TYPE_LABELS` + relasi `packagingRulesAsSource()` |
| `app/Services/StockService.php` | `recordOutWithPackaging` DB-driven per gudang (`packagingRulesFor(productId, inventoryId)` rule spesifik menimpa global, cache per instance, `defaultVariantOf`, `kdfVariants`); `recordIn`/`recordOut`/`stockOf` + parameter `inventoryId`; semua movement reserve tercatat dgn gudang induk produk |
| `app/Http/Controllers/GudangController.php` | `index(Request)` — tanpa `inventory_id` → hanya picker; dengan `inventory_id` → scope per gudang (consumable semua, core/additional `where inventory_id`), rules global + khusus gudang, `perVariant` map stok gudang itu (1 query aggregate); `adjust` (manual in/out PER GUDANG, reference `manual` + `reference_id` acak + `inventory_id`); **`productStore/Update/Destroy`** (inventory dari form hidden = gudang dibuka, `productStore` auto buat varian default + stok awal via recordIn ke gudang itu), **`variantStore/Update/Destroy`**, **`toggleStatus/toggleVariantStatus`** (semua logika ProductController lama pindah ke sini); `packagingStore` (opsional inventory_id; duplikat cek per (source,target,inventory)), `packagingUpdate/Destroy` |
| `resources/views/gudang/index.blade.php` | Kartu **Pilih Gudang** (aktif state) di atas; tanpa pilihan → prompt pilih; dengan pilihan → header gudang + 3 section scoped + **CRUD produk** (tombol ＋ Tambah Produk per section, modal create/edit produk, baris expand varian + modal varian + toggle status) + kartu Aturan Kemasan (rule global + gudang ini, form tambah default gudang aktif). Modal produk menyertakan info "Gudang otomatis: {nama}" |
| `app/Http/Controllers/ProductController.php` + `resources/views/product/*` | **DIHAPUS** — route `product.*` & `product.variant.*` lama dihapus; diganti `gudang.product.*`/`gudang.variant.*` |
| `resources/views/layouts/app.blade.php` | link sidebar **Produk dihapus** (digantikan halaman Gudang) |
| `app/Http/Controllers/ProductController.php` + `resources/views/product/form.blade.php` | validasi & input `goods_type` (select) + `min_stock` (number) |
| `database/seeders/ProductSeeder.php` | `goods_type` di semua produk (update idempotent saat re-seed, min_stock admin tidak ditimpa); **gudang induk per produk** (SH→GTM, KSP→Aurora, sisanya → Gudang Pusat — selaras `WAREHOUSE_BY_PRODUCT`, di-update saat re-seed); produk baru KTH/LAK/BUB (consumable, 1 varian, stok 1000); opening stock consumable **dibagi rata per inventory** (reference_id unik `variant_id*100+inventory_id`), produk lain tercatat ke gudang induk; `seedPackagingRules()` (6 rule global) |
| `resources/views/layouts/app.blade.php` | sidebar Gudang & Kiriman → link **Gudang** (🏬) |
| `routes/web.php` | `/gudang`, `/gudang/adjust`, `/gudang/packaging-rules` (+PUT/DELETE) |
| `app/Services/OrderTemplateExportService.php` | `WAREHOUSE_BY_PRODUCT` di-swap: **KSP→Aurora, SH→GTM** |
| `tests/Feature/OrderOnlineTest.php` | +8 test: qty_per dinamis, rule nonaktif, render gudang + adjust, **adjust per gudang**, CRUD rule (global + per gudang), **rule per gudang menimpa global**, badge restock, **scoping per gudang** (GTM = SH, Aurora = KSP, tanpa pilihan = picker saja); 3 test warehouse disesuaikan swap |
| `filecoba/generate_test_kit.php` + `02_export_flik.csv` | `warehouseFor` KSP→Aurora/SH→GTM + referensi statis di-update |

### Penting
- **Enum `stock_movements.reference`** — penyesuaian manual memakai nilai `'manual'` dengan `reference_id` acak (`random_int`) agar tidak menimpa jurnal opening stock (`adjustment` + `reference_id=variant_id`) dan tidak saling menimpa.
- **Nama index MySQL ≤ 64 char** — default unique `packaging_rules_source_product_id_target_product_id_inventory_id_unique` melebihi batas → pakai nama eksplisit `packaging_rules_combo_unique`. Migrasi 100003 idempotent (guard `hasColumn` + cek nama index) karena versi awal sempat ter-apply sebagian.
- **`recordOutWithPackaging` tanpa hardcode** — penambahan produk inti baru cukup tambahkan rule di halaman Gudang; stok produk yang tidak punya rule hanya berkurang qty-nya sendiri.
- Re-seed `ProductSeeder` **tidak menimpa `min_stock`** admin (hanya `goods_type` yang dipastikan konsisten).
- Test packaging lama tetap hijau: rule default qty_per=2 + `ensureCatalog()` me-reseed rule tiap test (updateOrCreate).
- Pipeline `filecoba/verify_pipeline.php` tetap **103/103 PASS**; `actual_export_*.csv` artefak regenerasi memakai gudang baru (Aurora utk KSP).
- Penyesuaian manual per gudang: stok total varian = jumlah semua gudang (kolom `product_variants.stock` tetap total; detail per gudang dihitung dari jurnal via `stockOf(variantId, inventoryId)`).
- Halaman Gudang tidak menampilkan SEMUA gudang sekaligus — pilih dulu gudangnya (1 gudang = 1 halaman). Produk inti/additional milik satu gudang; Barang Pasti muncul di semua gudang dengan stok per gudang masing-masing.
- **Produk baru di halaman Gudang** → gudang yang dibuka jadi gudang UTAMA (pivot `is_primary`) + **varian default otomatis** (agar stok bisa langsung dicatat). Edit `goods_type` di modal produk bisa memindahkan produk antar section (gudang tetap).

---

## P. ✅ Relasi Many-to-Many Produk ↔ Gudang + Stok per Gudang (13 Agustus 2026)

### Deskripsi
Relasi `products → inventories` diubah dari 1-ke-banyak (`products.inventory_id`) menjadi **many-to-many** — 1 produk bisa terdaftar di banyak gudang, dengan penanda gudang **UTAMA** (`is_primary`) yang dipakai export/fulfillment & pencatatan stok otomatis. Stok kini disimpan **per varian × gudang** di tabel tersendiri. `products.inventory_id` DIHAPUS.

### Skema baru
- `product_inventory` (pivot): `(product_id, inventory_id, is_primary)` UNIQUE `(product_id, inventory_id)`. `is_primary` = gudang utama (0/1 baris per produk — dijaga di titik tulis).
- `product_variant_inventory`: `(product_variant_id, inventory_id, stock)` UNIQUE `(product_variant_id, inventory_id)` — **cache stok per varian per gudang**, disinkronkan `StockService` dari jurnal (`stock_movements` TETAP sumber kebenaran).
- `products.inventory_id` di-drop (migrasi `2026_08_13_110001`). Backfill (migrasi `110000`): tiap produk → pivot dari `inventory_id` lama (`is_primary=1`); Barang Pasti (consumable) di-attach ke SEMUA inventory (mempertahankan perilaku lama); stok per (varian, gudang) diagregasi dari jurnal (movement `inventory_id` NULL dianggap gudang utama produk).

### Perilaku
- **Export (`warehouseFor`)** — nama gudang per order kini dari **gudang utama produk** (`Product::where(code)->first()->primaryInventory->first()->name`), cache per instance (anti N+1); fallback `WAREHOUSE_BY_PRODUCT` (KSP→Aurora, SH→GTM) → sender. Konsekuensi: produk non-spesial (KMP/KBJ/KCHP dll.) yang tadinya sender kini tampil `Gudang Pusat` (nama inventory). ZIP split per gudang tetap bekerja.
- **`recordOutWithPackaging`** memakai `product->primaryInventoryId()` sebagai gudang pencatatan movement (bukan `inventory_id`).
- **`recordIn`/`recordOut`** — bila `inventoryId` null, di-resolve ke gudang utama produk; setelah recalc total, `syncVariantInventoryStocks()` meng-upsert cache per (varian, gudang). `reverseReference` & `recalculateAll` ikut menyinkronkan cache.
- **Halaman Gudang** — scoping per gudang via `whereHas('inventories')` (bukan `where inventory_id`); `perInventoryStockByVariant` membaca tabel cache (1 query ringan). Modal edit produk punya seksi **Gudang Produk**: checkbox gudang + radio gudang utama, dikirim ke `gudang.product.update` (`inventory_ids[]` + `primary_inventory_id`). Detach gudang → hapus baris pivot + cache stok gudang tsb (jurnal tidak diubah).
- **Pembelian (`PurchaseController`)** mencatat stok ke `primaryInventoryId()`.
- **Seeder** — `syncInventoryMembership()`: inti/additional → gudang induk (primary), consumable → semua gudang (primary = pertama); re-seed memaksa primary ke bawaan tapi TIDAK menghapus keanggotaan tambahan admin; opening stock consumable tetap dibagi rata per gudang.

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_13_110000_create_product_inventory_tables.php` | tabel pivot + cache stok + backfill dari `products.inventory_id` & jurnal |
| `database/migrations/2026_08_13_110001_drop_inventory_id_from_products.php` | drop `products.inventory_id` |
| `app/Models/ProductInventory.php`, `ProductVariantInventory.php` | model pivot & cache stok |
| `app/Models/Product.php` | `inventories()`/`primaryInventory()`/`primaryInventoryId()`/`stockAt()`; `inventory_id` dikeluarkan dari fillable; relasi `inventory()` dihapus |
| `app/Models/Inventory.php` | `products()` → BelongsToMany |
| `app/Models/ProductVariant.php` | `inventoryStocks()` + `stockAt()` |
| `app/Services/StockService.php` | `syncVariantInventoryStocks()`; resolve `inventoryId` null → gudang utama; `recordOutWithPackaging` pakai `primaryInventoryId()` |
| `app/Http/Controllers/GudangController.php` | scoping `whereHas('inventories')`, `perVariant` dari cache, `productStore` attach pivot (primary), `productUpdate` terima `inventory_ids`/`primary_inventory_id`, `productDestroy` redirect pakai primary |
| `app/Http/Controllers/PurchaseController.php` | `primaryInventoryId()` |
| `app/Services/OrderTemplateExportService.php` | `warehouseFor` DB-driven (gudang utama produk), cache per instance |
| `database/seeders/ProductSeeder.php` | `syncInventoryMembership()` + opening stock via gudang utama |
| `resources/views/gudang/index.blade.php` | seksi Gudang Produk di modal edit (checkbox + radio primary) + `data-wh`/`data-primary-wh` |
| `tests/Feature/OrderOnlineTest.php` | +`test_warehouse_export_uses_primary_inventory`, `test_product_belongs_to_multiple_warehouses_with_per_warehouse_stock`; update test warehouse (KMP→Gudang Pusat), rule per gudang (pivot), gudang page (attach KTH) |
| `filecoba/generate_test_kit.php` | `warehouseFor` kit → `Gudang Pusat` untuk non-KSP/SH; referensi statis diregenerasi |

### Penting
- **Jurnal tetap sumber kebenaran** — `product_variant_inventory.stock` & `product_variants.stock` keduanya cache; jangan edit manual langsung.
- **`primaryInventory()` dipanggil sebagai PROPERTY** (koleksi) di service/view, bukan method (relasi) — `->primaryInventory?->first()?->name`, bukan `->primaryInventory()->name` (BelongsToMany tidak punya atribut `name`).
- Test M2M yang mengubah primary produk WAJIB mengembalikan primary asli di `finally` (kalau tidak, test berikutnya yang bergantung gudang utama ikut gagal).
- Test pakai DB `awannacoba` tanpa refresh → migrasi 110000/110001 otomatis ter-apply sebelum test (tabel sudah ada).
- `products.inventory_id` tidak boleh dipakai lagi di kode baru — ganti dengan `primaryInventoryId()` (gudang utama) atau membership `inventories()`.

---

## Q. ✅ Master Produk Terpusat — Halaman Produk Sendiri; Gudang Hanya Attach (13 Agustus 2026)

### Deskripsi
Membuat/mengubah produk & varian kini **hanya** di halaman master **Produk** (`/product`, sidebar Data Master). Halaman **Gudang** TIDAK lagi membuat produk — tombol "＋ Tambah Produk ke Gudang" hanya **mendaftarkan produk yang SUDAH ADA** (beserta variannya dari `product_variants`) ke gudang yang dibuka. Ini membalik keputusan fitur O (yang sempat memindahkan CRUD produk ke halaman Gudang) — semua produk di gudang kini teratur karena master data terpusat.

### Perilaku
- **Master Produk (`/product`)**: daftar + filter (search/tipe/status), kolom Kode, Nama, Tipe, Gudang utama + jumlah gudang, Stok total, Min. Stok (badge Restock), HPP, Harga Jual, Status (toggle), Aksi (Varian/Edit/Hapus). Modal buat/edit produk (tanpa gudang & tanpa stok awal — produk belum terdaftar di gudang mana pun saat dibuat; **varian default otomatis** agar stok bisa dicatat). Kelola varian (add/edit/delete/toggle) di baris expand. Stok per gudang tetap dikelola di Gudang/Barang Masuk — varian master dibuat dengan `stock=0`.
- **Halaman Gudang**: tombol per section "＋ Tambah Produk ke Gudang" → modal pilih produk master yang **belum terdaftar** di gudang ini (difilter per section via JS, `data-goods-type`), `stock_awal` opsional (varian default, jurnal `adjustment`), checkbox "Jadikan gudang utama". Produk pertama terdaftar otomatis jadi gudang utama. Tombol `🏷` per baris → modal **Kelola Gudang Produk** (centang gudang + radio utama). Tombol `🗑` → **Lepas dari gudang ini** (bukan hapus produk). Baris expand varian **read-only** (stok per gudang). Status produk/varian & edit field produk TIDAK ada di halaman Gudang lagi.
- **Controller**: `ProductController` baru (index/store/update/destroy/toggle + variant CRUD); `GudangController` hanya `productAttach`/`productWarehousesUpdate`/`productDetach` (method lama `productStore`/`productUpdate`/`productDestroy`/`variant*`/`toggle*` DIHAPUS).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/ProductController.php` | (baru) master produk + varian; varian default otomatis saat create |
| `app/Http/Controllers/GudangController.php` | `productAttach` (attach produk master + stok awal opsional + auto-primary pertama), `productWarehousesUpdate`, `productDetach`; index kirim `$availableProducts` |
| `routes/web.php` | `product.*` + `product.variant.*` (baru); `gudang.product.attach/warehouses/detach` (ganti store/update/destroy/toggle & variant lama) |
| `resources/views/product/index.blade.php` | (baru) master produk + modal + varian |
| `resources/views/gudang/index.blade.php` | modal attach & kelola gudang (form biasa), varian read-only, tombol detach |
| `resources/views/layouts/app.blade.php` | sidebar Data Master → link **Produk** (📦) |
| `tests/Feature/OrderOnlineTest.php` | +`test_gudang_attach_existing_product_to_warehouse`, `test_gudang_product_warehouses_update_and_detach`, `test_product_master_variant_crud`, `test_product_master_page_create_update_toggle_destroy`; test scoping pakai hitungan per section (nama produk lintas-gudang tampil di dropdown attach) |

### Penting
- Produk di halaman Gudang yang diklik `🗑` = **lepas dari gudang** (`gudang.product.detach`, kirim `inventory_id`), BUKAN hapus — hapus produk hanya di halaman Produk (`product.destroy`, soft delete).
- Dropdown attach memuat SEMUA produk aktif yang belum terdaftar di gudang itu (semua tipe) → test `assertDontSee` berbasis nama produk antar-gudang TIDAK valid; gunakan hitungan section (header "Barang Inti: N").
- `stock_awal` attach memakai `reference='adjustment'` + `reference_id` acak (`random_int`) agar tidak menimpa jurnal opening stock seeder (pola sama dengan penyesuaian manual).
- Ini membalik bagian fitur O yang memindahkan CRUD produk ke halaman Gudang; halaman `/product` dihidupkan kembali dengan skema M2M (fitur P): tanpa `inventory_id`, tanpa stok awal.

### Penting: gudang UTAMA (`is_primary`) HANYA untuk Barang Inti (core)
- Label "gudang utama" **hanya berlaku produk `core`** — Barang Pasti (consumable, ada di semua gudang) & Barang Additional (mengikuti barang inti) **tidak pernah** punya gudang utama (is_primary selalu false). Migrasi `2026_08_13_120000_primary_only_for_core` membersihkan data lama; seeder/`productAttach`/`productWarehousesUpdate` menjaganya (non-core: `is_primary=false`, radio/checkbox utama disembunyikan di UI, `Product::primaryInventoryId()` return null untuk non-core).
- **`Product::primaryInventoryId()`** guard memakai `goods_type !== null && goods_type !== 'core'` — instance baru dari `create()` punya `goods_type` null di memori (DB default 'core' tidak di-refresh ke model) sehingga TIDAK boleh salah terblokir.

---

## R. ✅ Barang Masuk (Purchase) — Opsi Gudang Tujuan (13 Agustus 2026)

### Deskripsi
Halaman **Barang Masuk** kini punya pilihan **Gudang Tujuan** — stok pembelian dicatat ke gudang yang dipilih (stok per gudang), bukan lagi selalu ke gudang utama produk (yang hanya ada untuk Barang Inti).

### Perubahan
- Migrasi `2026_08_13_130000_add_inventory_id_to_purchases`: `purchases.inventory_id` nullable FK (nullOnDelete) + index — data lama tetap valid.
- `Purchase` model: `inventory_id` fillable + relasi `inventory()`.
- `PurchaseController::store` validasi `inventory_id` **required** + `recordIn(..., $purchase->inventory_id)`; catatan jurnal menyertakan nama gudang. `index` eager-load `inventory` + filter `inventory_id` + pass `$inventories`.
- View `purchase/index.blade.php`: select **GUDANG TUJUAN** (required) di form + JS autofill gudang utama produk saat varian dipilih (`data-primary-wh` di `<optgroup>`; Barang Pasti/Additional tidak punya primary → tetap pilih manual), kolom **Gudang** di tabel (badge 🏭), filter gudang di bilah filter.
- Test `test_purchase_records_stock_to_selected_warehouse`: pembelian ke gudang B (bukan gudang utama A) → `purchases.inventory_id` = B, stok B bertambah, gudang utama A tetap 0, index menampilkan/filter gudang.

### Penting
- `inventory_id` WAJIB di form (produk tanpa gudang utama — Barang Pasti/Additional — harus dipilih manual; autofill hanya untuk Barang Inti).
- HPP tetap per produk (rata-rata tertimbang semua jurnal masuk) — tidak berubah.
- `purchase.destroy` tetap `reverseReference('purchase', id)` (jurnal dihapus apa adanya, `inventory_id` ikut terhapus).

## P. ✅ Aturan Gudang Dinamis (produk → gudang) + Rule Courier Khusus Produk (13 Agustus 2026)

### Deskripsi
Dua aturan "data mentah" yang tadinya hardcoded kini dinamis dari DB:
1. **Aturan Gudang** (`warehouse_rules`, halaman `/warehouse-rules`): mapping kode produk → nama gudang (kolom "Kode Warehouse" export) — pengganti konstanta `WAREHOUSE_BY_PRODUCT` (SH→GTM, KSP→Aurora).
2. **Rule courier khusus produk** (`courier_rules.product_code`): mis. produk **SH → SELALU `flix-tf`**, dievaluasi di fase terpisah sehingga **tidak terpengaruh aturan provinsi** (aturan provinsi Jawa → sicepat, Sumatera → flix-idx, dst. tidak berlaku untuk SH).

### Cara kerja courier 2 fase (`CourierRuleService::resolve`)
- **Fase 1 — rule khusus produk**: rule dengan `product_code` terisi & cocok dengan kode produk order (normalisasi uppercase + buang sufiks `+varian`). Rule pertama yang cocok menang — SELALU didahulukan dari fase 2.
- **Fase 2 — rule umum**: `product_code` null (perilaku lama: `payment_method` + `province`, `sort_order` terkecil menang).
- Tanpa rule cocok → fallback `spx`.
- `OrderOnlineImportService` meneruskan `product_code` (master dari CSV) ke `resolve()`; `tembakan` tetap SELALU `spx` (tidak masuk fase manapun).

### Cara kerja warehouse (`OrderTemplateExportService::warehouseFor`)
Prioritas: **rule dinamis** (`warehouse_rules` aktif, product_code cocok) → **gudang utama produk** (pivot `is_primary`) → mapping kode lama (`WAREHOUSE_BY_PRODUCT`) → **sender**. Admin menonaktifkan rule agar produk jatuh ke gudang utama/sender.

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_13_140000_create_warehouse_rules_table.php` | `warehouse_rules`: `product_code` (unique), `warehouse`, `is_active` |
| `database/migrations/2026_08_13_140001_add_product_code_to_courier_rules.php` | `courier_rules.product_code` nullable + index |
| `app/Models/WarehouseRule.php` | model sederhana |
| `app/Services/WarehouseRuleService.php` | `resolve(productCode)` + `rules()` index by product_code (cache per instance, anti N+1); normalisasi uppercase/`explode('+')` |
| `app/Services/CourierRuleService.php` | `resolve(paymentMethod, province, productCode)` 2 fase (produk → umum); `matches()` helper |
| `app/Services/OrderOnlineImportService.php` | `resolve(..., $row['product_code'])` |
| `app/Services/OrderTemplateExportService.php` | `warehouseFor()` cek `warehouse_rules` lebih dulu (via `WarehouseRuleService`, cache per instance) |
| `app/Http/Controllers/CourierRuleController.php` | validasi + normalisasi `product_code` (uppercase, buang `+varian`); cek duplikat kombinasi (payment+province+product_code) |
| `app/Http/Controllers/WarehouseRuleController.php` (baru) | `index/store/update/destroy/toggle`; duplikat per `product_code`; normalisasi uppercase/`explode('+')` |
| `resources/views/courier_rule/index.blade.php` | Field **Kode Produk** (datalist produk) di form & modal edit, kolom badge produk di tabel, info box cara kerja 2 fase |
| `resources/views/warehouse_rule/index.blade.php` (baru) | Form tambah (kode produk + gudang + aktif) + tabel (badge, toggle, edit modal, hapus) + info box prioritas |
| `routes/web.php` | `/warehouse-rules` (GET/POST/PUT/PATCH toggle/DELETE, nama `warehouse-rule.*`) |
| `resources/views/layouts/app.blade.php` | Sidebar Data Master → **Aturan Gudang** (di bawah Aturan Courier) |
| `database/seeders/WarehouseRuleSeeder.php` (baru) | 2 rule bawaan idempotent: SH→GTM, KSP→Aurora (updateOrCreate, tidak menimpa tambahan admin) |
| `database/seeders/CourierRuleSeeder.php` | + rule `product_code='SH'` → `flix-tf` (payment/province null = semua) di sort_order 1 |
| `tests/Feature/CourierRuleTest.php` | +3 test: rule produk menang atas provinsi (termasuk nonaktif → jatuh provinsi), store normalisasi product_code, duplikat kombinasi produk |
| `tests/Feature/WarehouseRuleTest.php` (baru) | 7 test: index, store+resolve dinamis, normalisasi, duplikat, update, toggle, destroy |
| `tests/Feature/OrderOnlineTest.php` | +2 test: import SH (cod Jawa Barat) → flix-tf & KMP → sicepat; warehouse rule menimpa gudang utama (termasuk nonaktif → Gudang Pusat) |

### Penting
- **Evaluasi 2 fase** — rule produk tidak bergantung posisi `sort_order` vs rule umum; selalu menang. Ini kunci "tidak terpengaruh aturan courier provinsi".
- `product_code` di-normalisasi saat simpan (uppercase, `explode('+')[0]`) DAN saat evaluasi — kode varian `SH+1.25` tetap cocok rule `SH`.
- `CourierRuleSeeder` tetap truncate (defaults menang); `WarehouseRuleSeeder` idempotent `updateOrCreate` (tambahan admin dipertahankan).
- Test courier produk memakai kode unik (bukan `SH` — DB dev sudah punya rule seed SH→flix-tf).
- Test import SH memakai **phone unik per run** — phone tetap + produk `SH` + alamat tetap akan kena deteksi duplikat signature dari run sebelumnya (order_id beda) → courier null.
- Suite: **122 pass** (hanya `ExampleTest` 302 pre-existing) · pipeline `verify_pipeline.php` **103/103 PASS**.

---

## S. ✅ Aturan Status Aggregator Dinamis — Mapping Status Dashboard → Status Sistem (13 Agustus 2026)

### Deskripsi
Mapping raw status file dashboard aggregator (FLIK / SiCepat / SPX) → `shipping_orders.aggregator_status` tidak lagi hardcoded di `AggregatorTrackingImportService::mapStatus`. Aturan tersimpan di tabel **`tracking_status_rules`** dan dikelola admin lewat halaman **Aturan Status** (`/tracking-status-rules`, sidebar Data Master).

**Halaman dipisah PER DASHBOARD** (FLIK / SiCepat / SPX) — karena nama kolom & isi file tiap ekspedisi berbeda-beda: `index` = kartu per dashboard (jumlah mapping header + jumlah aturan status, tombol Edit), `edit` = halaman per dashboard berisi **2 hal**:
1. **🧩 Mapping Kolom Database → Header CSV** (14 Agustus — UI DIBALIK dari pola export template): kolom kiri = **kolom DATABASE (teks statis, BUKAN form** — DB tidak mungkin berubah), kolom kanan = **dropdown header CSV dari file upload**. Admin **upload file dashboard aslinya** → sistem mengekstrak header (`extractHeaders` — sumber dideteksi otomatis, mapping lama ikut terbawa per kolom DB) → untuk tiap kolom DB pilih header yang mengisinya (`tracking_header_mappings` 9 kolom: tracking_number/phone/**customer_name**/address/product_name/quantity/status/problem/delivered_date) → **Simpan Mapping** (bulk replace; 1 header hanya boleh dipakai 1 kolom — duplikat ditolak + dicegah JS). Kolom dibiarkan kosong → tidak diisi.
2. **🗂 Aturan Status** (raw → sistem) khusus sumber itu: tabel rule (toggle/↑↓/edit modal/hapus) + form tambah manual (collapsible, `source` terkunci = dashboard yang dibuka).
`AggregatorTrackingImportService::mapHeaders()` kini **membaca mapping DB** (menang atas alias hardcoded untuk header yang sama; header lain tetap fallback alias) — jadi import tracking ikut kolom yang dipilih admin.

### Cara kerja (`TrackingStatusRuleService::resolve`)
- Evaluasi per sumber, urut dari `sort_order` terkecil; **rule pertama yang cocok menang**.
- `raw_status` di-normalisasi lowercase saat simpan & saat cocok; `match_type` **exact** (sama persis) atau **contains** (status memuat teks).
- **Aturan bermasalah** memakai `problem_mode=required`: rule hanya cocok bila kolom masalah file terpenuhi — `problem_keyword` **null** = kolom cukup TIDAK kosong (SPX `Delivery OnHold Reason`); **terisi** = dicocokkan sesuai `problem_match_type` (14 Agustus): **`contains`** = kolom MENGANDUNG keyword (default), **`starts_with`** = kolom DIAWALI keyword — **keunikan FLIK**: status kolom normal, masalah ada di kolom 3PL TERPISAH (header beda, `problem` mapping) yang isinya diawali `Problem...`. Bila tidak terpenuhi, rule dilewati → jatuh ke rule normal utk status yang sama. Karena itu rule problem diberi `sort_order` kecil (dievaluasi duluan).
- Tidak ada rule cocok → `null` (raw tak dikenal, `aggregator_status` tidak diisi).
- Rule dikelompokkan per `source` + cache per instance (anti N+1, pola AGENTS.md).

### Nilai `aggregator_status` (6 nilai INGGRIS, `ShippingOrder::TRACKING_STATUSES`)
`waiting_pickup` · `in_transit` · `delivered` · `returning` · `returned` · `problem` — dropdown Status Sistem di UI dibatasi ke daftar ini.

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_13_150000_create_tracking_status_rules_table.php` | `source`, `raw_status`, `match_type` (exact/contains), `status`, `problem_mode` (none/required), `problem_keyword` nullable, `sort_order`, `is_active`; UNIQUE `(source, raw_status, match_type, problem_mode, status)` |
| `database/migrations/2026_08_14_100000_create_tracking_header_mappings_table.php` (baru) | `source`, `header` (normalized), `db_column`; UNIQUE `(source, header)` nama eksplisit `tracking_header_mappings_combo_unique` |
| `database/migrations/2026_08_14_100001_add_problem_match_type_to_tracking_status_rules.php` (baru) | + `problem_match_type` string default `contains` (nilai `contains`/`starts_with`) — cara cocok kolom masalah terhadap keyword |
| `app/Models/TrackingHeaderMapping.php` (baru) | konstanta **`COLUMNS`** (registry **9** kolom: tracking_number/phone/**customer_name**/address/product_name/quantity/status/problem/delivered_date) — sumber kebenaran dropdown & validasi |
| `app/Models/TrackingStatusRule.php` | konstanta `SOURCES` (backward-compat) + **`validSources()`** di controller (SOURCES lama + ExportTemplate keys) / `MATCH_TYPES`/`PROBLEM_MODES`/**`PROBLEM_MATCH_TYPES`** + casts |
| `app/Services/TrackingStatusRuleService.php` (baru) | `resolve(source, rawStatus, ?problemColumn)` — evaluasi sort_order, `problemColumnMatches(column, keyword, matchType)` (keyword null → non-kosong; `contains` → mengandung; `starts_with` → diawali) |
| `app/Services/AggregatorTrackingImportService.php` | `mapStatus(source, rawStatus, problemColumn)` → delegasi ke service (argumen `true` lama tetap diterima = paksa `problem`); `isProblem()` dihapus; `normalizeRow` kirim teks kolom masalah langsung + baca **`customer_name`/`name_norm`**; **`resolveOrder`** hanya **phone + customer_name** — nama kosong → unmatched; >1 kandidat nama sama → ambiguous; **`extractQuantity`** + pola **`Dapat N`** (Beli 1 Dapat 2 → qty 2, konsisten `OrderOnlineImportService`); **`mapHeaders` DB-aware** (`headerMappingFor(source)` cache per instance → mapping DB MENANG per header, sisanya fallback alias); **`extractHeaders(filePath)`** (header list + `mapping` db_column→header utk carry-over UI dibalik); **`saveHeaderMapping(source, items)`** (items `{db_column, header}`, bulk replace dlm 1 transaksi, **1 header utk 1 kolom** → RuntimeException); **`extractDefaultMapping(filePath)`** (header → db_column murni alias utk seeder) |
| `app/Http/Controllers/TrackingStatusRuleController.php` (baru) | `index` (kartu per dashboard + hitungan via 2 aggregate groupBy + **export template reference** dari `ExportTemplate`) + **`edit($source)`** (per dashboard: mapping kolom DB + rules khusus sumber; `mapping` pluck header by db_column) + **`upload`** (JSON `{source, headers[], mapping{}}` — file di-`store` dulu agar path punya ekstensi, validasi sumber = dashboard yang dibuka) + **`saveMapping`** (validasi `db_column required in:COLUMNS`, header nullable; catch RuntimeException → error mapping) + `store/update/destroy/toggle/move` (+ validasi/normalisasi `problem_match_type`); **`validSources()`** = SOURCES lama + ExportTemplate keys (dinamis — template baru otomatis valid) |
| `resources/views/tracking_status_rule/index.blade.php` (baru) | Kartu per dashboard (gradient, ikon, jumlah mapping header & aturan status, **template export reference** — nama/couriers, atau ⚠️ belum ada template) + tombol ➕ template baru yang belum punya tracking rules — pola export template |
| `resources/views/tracking_status_rule/edit.blade.php` (baru) | Per dashboard: kartu **Mapping Kolom Database → Header CSV** (kiri kolom DB teks statis, kanan select header dari file; upload → isi dropdown + pre-select carry-over; JS cegah header dobel; simpan → hidden `items[]`) + kartu **Aturan Status** (tabel rule khusus sumber + form tambah manual `source` hidden + field `problem_match_type` di form & modal edit) |
| `routes/web.php` | `GET /tracking-status-rules`, `GET /{source}/edit`, `POST /upload`, `POST /{source}/mapping` + POST/PUT/PATCH toggle/POST move/DELETE (nama `tracking-status-rule.*`) |
| `resources/views/layouts/app.blade.php` | Sidebar Data Master → **Aturan Status** (di bawah Aturan Gudang) |
| `database/seeders/TrackingStatusRuleSeeder.php` (baru) | 23 rule bawaan idempotent (updateOrCreate by kombinasi): FLIK 8 (incl. 2 problem `dikonfirmasi`/`sedang diantar` + 3PL DIAWALI `problem` via `starts_with`, sort 1), SICEPAT 6, SPX 9 (incl. 3 problem `pending pickup`/`in transit`/`delivering` + OnHold terisi, sort 1) |
| `tests/Feature/TrackingStatusRuleTest.php` (baru) | 17 test: index kartu, **edit per dashboard scoped** (kolom DB kiri + rule flik tidak tampil di spx), **upload header CSV + carry-over per db_column**, **save mapping → dipakai import** (header tak standar dikenali via DB, incl. `customer_name`), **duplikat header ditolak**, store+resolve dinamis, problem required (**starts_with**: diawali vs mengandung), prioritas, contains, toggle, destroy, duplikat, move, update, import ikut rules DB |
| `tests/Feature/AggregatorTrackingImportTest.php` | `test_map_status_english_values` disesuaikan — argumen `true` diganti string kolom masalah; +2 test **matching nama pelanggan** (nama memutuskan 2 order HP+produk+qty sama; nama menang walau alamat file beda); **+3 test fallback produk tak dikenal** (nama promo FLIK tetap match via phone+qty+nama; qty diekstrak dari "Beli 1 Dapat 2"; 2 kandidat tanpa pembeda tetap ambiguous) |
| `filecoba/verify_pipeline.php` | + precheck `tracking_status_rules` + cleanup `tracking_header_mappings` di awal (agar import tracking selalu pakai alias bawaan) |

### Penting
- **Batas key MySQL (3072 bytes utf8mb4)**: kolom unik 5 string() default (255 char) melebihi batas → `source`/`match_type`/`status`/`problem_mode` dibatasi `string(20)`, `raw_status`/`problem_keyword` `string(191)`; UNIQUE `tracking_status_rules_combo_unique` (`source, raw_status, match_type, problem_mode, status`) tetap 5 kolom (dipakai `updateOrCreate` seeder).
- **Migrasi gagal di tengah**: bila `alter table ... add unique` error, tabel sudah terbuat tapi migrasi tidak tercatat → `Schema::dropIfExists('tracking_status_rules')` dulu, baru `php artisan migrate`.
- **`upload` wajib `store` file dulu** (pola `trackingImport`): temp upload browser TIDAK punya ekstensi (`/tmp/phpXXXX`) → `readRows()` menolak "Format file tidak didukung" (422). `UploadedFile::fake()->createWithContent()` juga menghasilkan pathname tanpa ekstensi — test upload harus lewat controller (yang store), bukan panggil service dgn pathname langsung.
- **`saveHeaderMapping` = bulk replace per sumber** dalam 1 transaksi: hapus semua mapping lama sumber itu lalu buat dari `items` — aman re-upload file yang sama (idempotent); item tanpa kolom dilewati (header jadi tidak dipakai).
- **Mapping DB menang per header, bukan menggantikan alias total**: `mapHeaders` merge — header yang di-map admin dipakai apa adanya, header lain tetap dicocokkan alias bawaan (mapping sebagian tidak memutus kolom lain). Konsistensi: pipeline & test `save mapping` WAJIB hapus mapping di `finally` (DB aktif tanpa refresh) agar run berikutnya tidak terkontaminasi.
- **DB column registry** (`TrackingHeaderMapping::COLUMNS`) satu-satunya sumber kebenaran — tambah kolom baru di registry + `mapHeaders`/`normalizeRow` bila perlu.
- **UI mapping DIBALIK dari export template** (14 Agustus): kiri = kolom DB TEKS statis (DB tidak berubah), kanan = select header CSV. `saveHeaderMapping` tetap menyimpan `(source, header, db_column)` — items dari form `{db_column, header}`; **satu header hanya boleh dipakai satu kolom** (unique `(source, header)`) → validasi service + cegah duplikat di JS (option disabled).
- **Keunikan FLIK kini data-driven** (`problem_match_type=starts_with`): status kolom normal (`Dikonfirmasi`/`Sedang Diantar`) + kolom 3PL TERPISAH (di-map ke `problem`) diawali `Problem...` → `problem`. Aggregator baru cukup konfigurasi mapping header + rules, tanpa ubah kode.
- `AggregatorTrackingImportService::mapStatus` menerima argumen ketiga string (kolom masalah) ATAU `true` (kompatibilitas lama: paksa `problem`).
- Aturan bermasalah harus `sort_order` KECIL dari rule normal utk status yang sama (kalau tidak, rule normal menang duluan). Seeder meletakkan problem di sort 1.
- Status tak dikenal → `aggregator_status=null` (tetap dihitung `unmatched` di laporan import). `delivered_at` tetap hanya diisi saat status `delivered`.
- Test memakai DB aktif tanpa refresh → rule test memakai `raw_status` unik prefix `teststatus` dan di-delete di akhir test; service resolver di-test dengan instance BARU (cache per instance).
- **Fallback produk tak dikenal (15 Agu)**: dashboard FLIK asli berisi nama PROMO di kolom "Nama Produk" (`Promo: PROMO Beli 1 Dapat 2 - Rp 129.000...`) yang TIDAK bisa dicocokkan ke tabel products → `product_id=null` → dulu semua baris jadi `unmatched` walau order-nya ada di DB. Sekarang `resolveOrder` hanya pakai phone + customer_name (produk tidak relevan untuk pencocokan); `extractQuantity` juga paham "Dapat N". Verifikasi nyata: `training/02_flik.csv` 7 baris → 4 matched (2 unmatched sah: belum_diproses & phone tidak ada; 1 ambiguous: 2 order identik).
- **`TrackingHeaderMappingSeeder` (15 Agu)**: baca `training/templateTracking/header_*.csv` → isi `tracking_header_mappings` via `extractDefaultMapping()` (murni alias, idempotent updateOrCreate). FLIK 8, SiCepat 8, SPX 9 kolom. Dipanggil di `DatabaseSeeder` (8d).
- **Courier dropdown dinamis dari `export_templates` (15 Agustus)**: `OrderOnlineController::index()` kumpulkan courier dari `ExportTemplate::where('is_active')` → `flatMap(couriers)` → unique + push `undeliverable` → pass `$courierList` ke view. View ganti hardcode `CourierRuleService::COURIERS` → `$courierList`. `update()` validasi courier juga dinamis dari `ExportTemplate`. Saat admin tambah template baru (mis. `idxeveropro` → `IDEXPRESS`), courier itu langsung muncul di dropdown tanpa ubah kode.
- **Tracking SOURCES dinamis dari `export_templates` (15 Agustus)**: `TrackingStatusRuleController::validSources()` = `TrackingStatusRule::SOURCES` (backward-compat) + `ExportTemplate::pluck('key')` (unique). Template baru (mis. `idxeveropro`) otomatis valid untuk aturan tracking. Index view tampilkan **template export reference** (nama + couriers) + tombol ➕ untuk template yang belum punya tracking rules. Tidak ada migrasi baru — `source` column `string(20)` menerima nilai bebas.
- Suite: **158 pass** (hanya `ExampleTest` 302 pre-existing) · pipeline `verify_pipeline.php` **104/104 PASS**.

---

## T. ✅ Laporan Operasional — Dashboard Hari Ini + Detail per Pengirim (13 Agustus 2026)

### Deskripsi
Dashboard admin (general) kini menampilkan 4 kartu operasional **hari ini** yang bisa diklik: **Barang Keluar**, **Barang Masuk**, **Resi**, dan **Metode Pembayaran (COD · Bank Transfer)**. Klik kartu → halaman **Laporan Operasional** (`/laporan-operasional`) yang merinci **per nama pengirim** (`order_online_import_batches.sender`) pada rentang tanggal (date-range picker, default hari ini): total pengeluaran (nilai order / gross revenue), jumlah resi, jumlah COD & bank_transfer — plus di baris paling bawah **TOTAL KESELURUHAN**: uang masuk vs HPP (margin) berdampingan.

### Keputusan performa (jawaban atas pertanyaan "berat tidak?")
- **Semua angka pakai QUERY AGREGAT (GROUP BY/SUM di SQL)** — total hanya **3 query** utk seluruh halaman (stok hari ini 1, order hari ini 1, laporan per sender 1) + total keseluruhan dihitung dari collection hasil GROUP BY (**tanpa query tambahan**, pola batch AGENTS.md). Tidak ada query per baris/per pengirim dalam loop.
- **TIDAK pakai `whereDate()`** — Laravel menerjemahkannya ke `DATE(col)=?` yang mematikan index → pakai range `>=`/`<` (created_at `>= today 00:00` & `< tomorrow`).
- **Index baru**: `shipping_orders.created_at` (migration `2026_08_13_160000`) — sebelumnya hanya `last_synced_at` yang ber-index; filter "hari ini"/rentang butuh index ini (aturan AGENTS.md #1).
- **Tabel ringkasan TIDAK dibuat** — skala data masih kecil (ratusan order/hari); aggregate langsung dari jurnal/sumber kebenaran selalu konsisten. Baru buat summary table kalau data sudah jutaan baris DAN dashboard terasa lambat (cek slow query > 500ms di `storage/logs/laravel.log`).
- EXPLAIN: query filter+join memakai full scan pada tabel kecil (210 baris, 6ms) — normal; index `created_at` siap dipakai optimizer saat tabel membesar.

### Definisi angka
- **Barang keluar/masuk hari ini** = `stock_movements` (`type` in/out, `date` = hari ini) → SUM quantity.
- **Resi** = order yang sudah punya `awb` terisi (non-kosong).
- **Metode pembayaran** = `shipping_orders.payment_method` (`cod` / `bank_transfer`, disimpan lowercase).
- **Total pengeluaran / uang masuk** = `SUM(shipping_orders.amount)` (gross revenue).
- **HPP** = `SUM(quantity × products.purchase_price)` (HPP per PRODUK, bukan per varian).

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_13_160000_add_created_at_index_to_shipping_orders.php` | index `shipping_orders.created_at` (dipakai filter hari ini/rentang) |
| `app/Http/Controllers/OperationalReportController.php` (baru) | `index(Request)` — parse & balik `dari/sampai`, 2 aggregate hari ini + 1 aggregate laporan per sender (JOIN batches + LEFT JOIN products utk HPP), total dihitung dari collection; `parseDate()` validasi Y-m-d dgn fallback hari ini |
| `app/Http/Controllers/DashboardController.php` | `dashboardGeneral()` + `opsHariIni` (keluar/masuk/resi/cod/bank_transfer) dari 2 aggregate (`StockMovement` by date, `ShippingOrder` by created_at range) |
| `resources/views/dashboard/general.blade.php` | 4 kartu stat operasional (klik → laporan dgn dari/sampai=hari ini) di atas kartu Spending |
| `resources/views/laporan/operasional.blade.php` (baru) | Kartu ringkasan PERIODE TERPILIH + date-range picker + tabel per pengirim (pengeluaran, resi `N / total`, COD, TF, uang masuk, HPP) + tfoot TOTAL KESELURUHAN & baris Margin (uang masuk − HPP, % ) |
| `resources/views/laporan/batch.blade.php` (baru, 14 Agustus) | **Detail per batch/pengirim** — kartu ringkasan (order/resi, qty terjual, uang masuk, margin) + tabel **Barang Terjual & Rincian Varian**: grup per produk master, baris per varian (badge power) + nama terjual + qty/order + jumlah order + qty terjual + uang masuk + HPP |
| `resources/views/laporan/batch.blade.php` (+14 Agustus) | **Tombol 📋 Copy ke WhatsApp** — teks laporan lengkap dibangun SERVER-SIDE (`$copyText` di `@php`, format WA: ringkasan + barang per varian + total) disimpan di `<textarea hidden>` (dibaca JS) + `<pre>` fallback; JS `navigator.clipboard` → fallback `document.execCommand('copy')` → kalau gagal, `<pre>` ditampilkan utk blok manual |
| `resources/views/layouts/app.blade.php` | Sidebar Gudang & Kiriman → **Laporan Operasional** (owner/super_admin/admin) |
| `routes/web.php` | `GET /laporan-operasional` (`operational-report.index`); `GET /laporan-operasional/{batch}` (`operational-report.batch`) |
| `tests/Feature/OperationalReportTest.php` (baru) | 5 test: kartu dashboard render, laporan per sender + totals, rentang tanggal, empty state, total per sender = sum |
| `tests/Feature/OperationalReportTest.php` (+4, 14 Agustus) | link baris → detail batch, detail memisahkan pcs (2 vs 4), detail hormati rentang tanggal |

### Penting
- **Kartu di halaman laporan MENGIKUTI periode terpilih** (`dari`/`sampai`), bukan hardcoded hari ini — saat user ganti range, kartu barang keluar/masuk/resi ikut menyesuaikan; label otomatis jadi "Hari Ini" (default) atau "Periode Terpilih" (range lain). Dashboard tetap menampilkan kartu "hari ini".
- **Fix 14 Agustus — kartu tampil 0 terus padahal query benar**: kartu stat memakai `data-counter` (JS animasi angka dari 0), tapi di lingkungan tanpa build Vite (`public/build/manifest.json` tidak ada) `@vite` tidak me-render `app.js`/`animations.js` → angka visible tetap `0` apa pun hasil query. Solusi: **teks awal kartu diisi nilai asli** (`data-counter="{{ $nilai }}">{{ $nilai }}`): tanpa JS langsung tampil benar, dengan JS animasi counter tetap berjalan (override textContent). Diterapkan ke semua kartu `data-counter` di `dashboard/general`, `dashboard/keuangan`, `laporan/operasional`, `regional/index`, `team/performance`.
- `stock_movements.date` bertipe datetime → range stok memakai eksklusif `< besok` (sama seperti created_at) agar movement di hari `sampai` tidak terlewat.
- `payment_method` disimpan **lowercase** (`cod`/`bank_transfer`) — CASE SUM memakai literal lowercase.
- Kolom "Resi" menampilkan `N ber-resi / total order` (rese = ber-awb).
- **Detail per batch (14 Agustus)**: nama pengirim & kolom Total Pengeluaran di tabel laporan adalah link → `operational-report.batch` dgn `dari/sampai` diteruskan. Kunci pemisah varian: kacamata promo "Dapat N" disimpan sbg `product_name = "... N pcs"` + `quantity = N`, jadi GROUP BY menyertakan `product_name` + `quantity` (qty per order) — varian sama (mis. KMP+1.50) dgn isi 2 pcs dan 4 pcs tampil **baris terpisah**. JOIN `products` (LEFT) utk nama/kode master + `product_variants` (LEFT) utk power/badge.
- **HANYA order yang DIPROSES (14 Agustus)**: scope `ShippingOrder::processed()` = status `real`/`tembakan` DAN courier ≠ `undeliverable` (paket tak terkirim/tak ter-cover aggregator). Order `cancel`/`belum_diproses`/`duplikat` TIDAK pernah diproses → dikecualikan dari SEMUA angka operasional: kartu dashboard (`orderHariIni`), laporan per sender (`$orderPeriode` + `$rows`), detail batch (`$rows` + `resi`). Kolom `status`/`courier` dikualifikasi `shipping_orders.*` karena scope dipakai pada query ber-JOIN (products/batches juga punya `status`).
- **`created_at` di query detail batch WAJIB dikualifikasi** `shipping_orders.created_at` — setelah LEFT JOIN `products`/`product_variants` (keduanya punya `created_at`) kolom jadi ambigu (SQLSTATE 1052).
- Test memakai DB bersama tanpa refresh → data di-isolasi dgn tanggal unik prefix `2026-01-XX` + `uniqid()` di sender/order_id, batch di-delete di `finally`; empty-state memakai rentang `2019-01-01..31`.
- Sidebar hanya utk role yang punya akses Gudang & Kiriman (owner/super_admin/admin); route tetap bisa dibuka role lain yg login (profil lengkap).
- **Copy ke WhatsApp (14 Agustus)**: `$copyText` dibangun dari `$rows`/`$grouped` yang SAMA dengan tabel (format angka identik `number_format(...,0,',','.')`, pemisah pcs per varian tetap beda baris), jadi isi yang di-paste = persis isi halaman. `isSecureContext` diperiksa — di HTTP non-localhost `navigator.clipboard` undefined → otomatis fallback execCommand; gagal total → `<pre id="copy-report-fallback">` tampil utk seleksi manual. Textarea tersembunyi (`position:fixed;left:-9999px`) bukan `display:none` agar `select()`/`execCommand` tetap jalan.
- Suite: **145 pass** (hanya `ExampleTest` 302 pre-existing) · pipeline `verify_pipeline.php` **104/104 PASS**.

---

## U. ✅ Sektor Keuangan — Akun, Kategori, Transfer Antar Akun, Bukti Transfer (19 Agustus 2026)

### Deskripsi
Modul keuangan 4 tabel: **`accounts`** (master rekening/cash/aggregator + `current_balance`), **`transaction_categories`** (kategori transaksi, `type` in/out), **`account_transfers`** (operan saldo antar akun), **`bank_transfers`** (transaksi masuk/keluar per akun + bukti gambar). Alur: CS upload bukti transfer pembeli (type=in) → status `pending` (saldo BELUM berubah) → role Keuangan/Owner **approve** (saldo bertambah, gambar bukti DIHAPUS dari disk) atau **reject** (wajib `rejection_note`, saldo tetap, gambar disimpan agar CS bisa lihat; notifikasi terkirim ke CS). Transaksi **keluar** dicatat langsung oleh approver (status langsung `approved`, saldo berkurang, tanpa gambar/approval). `current_balance` dihitung otomatis dari transaksi via `FinanceService` (single source of truth).

### Skema (migration `2026_08_19_000000_create_finance_tables.php`)
- `accounts`: `name`, `type` (bank/cash/ewallet/aggregator), `current_balance` decimal(15,2), `status` (active/inactive) + index.
- `transaction_categories`: `name`, `type` (in/out) + UNIQUE `(name, type)`.
- `account_transfers`: `from_account_id`/`to_account_id` FK RESTRICT, `amount`, `transfer_date` (datetime), `description`, `created_by` + index (from, to, transfer_date).
- `bank_transfers`: `account_id`, `category_id` FK RESTRICT, `type` (in/out), `amount` decimal(15,2), `transaction_date` (datetime), `description`, `image_url` nullable, `status` (pending/approved/rejected), `rejection_note` nullable, `created_by` + index (status, account_id, type, transaction_date).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Models/Account.php` | `TYPES`/`TYPE_LABELS`/`STATUSES`, scopeAktif, `type_label`, relasi bankTransfers/transfersFrom/transfersTo |
| `app/Models/TransactionCategory.php` | `TYPES`, relasi bankTransfers |
| `app/Models/AccountTransfer.php`, `BankTransfer.php` | `STATUS_LABELS` + isPending/isApproved/isRejected; casts `transaction_date:datetime`, `amount:decimal:2` |
| `app/Services/FinanceService.php` | `applyBankTransfer`/`reverseBankTransfer`/`applyAccountTransfer` (+`RuntimeException` saldo cukup)/`reverseAccountTransfer` — SEMUA update `accounts.current_balance` lewat sini (DB::transaction + `lockForUpdate`) |
| `app/Http/Controllers/FinanceAccountController.php` | CRUD + toggle; hapus diblokir (flash error) bila punya transaksi |
| `app/Http/Controllers/FinanceCategoryController.php` | CRUD + cek duplikat (name+type); hapus diblokir bila dipakai transaksi |
| `app/Http/Controllers/FinanceTransferController.php` | store (validasi saldo cukup) / destroy (balikin saldo) / index |
| `app/Http/Controllers/BankTransferController.php` | `APPROVERS = ['owner','super_admin','admin','keuangan']`; CS: HANYA type=in + gambar WAJIB + hanya lihat milik sendiri (`where created_by`); approve → hapus gambar + null image_url; reject → `rejection_note` wajib + notif `bank_transfer_rejected`; destroy → reverse saldo; notifyCreator via model `Notification` |
| `routes/web.php` | grup `prefix('keuangan')->name('finance.')`: resource akun/kategori (params `account`/`category`), transfer, bukti-transfer |
| `resources/views/finance/accounts/index.blade.php`, `categories/index.blade.php`, `transfers/index.blade.php`, `bank_transfers/index.blade.php` | pola clay-card/clay-table + modal edit + JS filter kategori by tipe |
| `resources/views/layouts/app.blade.php` | Sidebar Keuangan: Akun Keuangan, Kategori Transaksi, Transfer Antar Akun, Bukti Transfer (owner/super_admin/mentor/keuangan) + Upload Bukti Transfer (cs) |
| `resources/views/notifications/index.blade.php` | `@case('bank_transfer_approved') ✅` & `@case('bank_transfer_rejected') ❌` |
| `tests/Feature/FinanceTest.php` | 14 test: CRUD akun (hapus diblokir), kategori (duplikat), transfer saldo cukup/tidak + destroy balikin, CS upload pending (saldo tetap) → approve (saldo naik + gambar terhapus) / reject (note + notif + gambar disimpan), out langsung kurangi saldo, CS dilarang out, guard role advertiser, approve hanya approver |

### Endpoint
- `GET/POST /keuangan/akun`, `PUT/DELETE /keuangan/akun/{account}`, `PATCH /keuangan/akun/{account}/toggle`
- `GET/POST /keuangan/kategori`, `PUT/DELETE /keuangan/kategori/{category}`
- `GET/POST /keuangan/transfer`, `DELETE /keuangan/transfer/{transfer}`
- `GET/POST /keuangan/bukti-transfer`, `POST /keuangan/bukti-transfer/{bankTransfer}/approve|reject`, `DELETE .../destroy`

### Penting
- **Semua perubahan `current_balance` WAJIB lewat `FinanceService`** (jangan `$account->update` manual di controller) - ini satu-satunya titik yang konsisten + lockForUpdate.
- Approve = hapus `image_url` dari disk (`Storage::disk('public')->delete`); reject TIDAK menghapus gambar (CS perlu melihat buktinya). Alur ini disengaja, beda dari dugaan awal.
- Resource route param: `->parameters(['akun' => 'account'])` - tanpanya `route('finance.accounts.update', ['account' => ...])` di JS view error "Missing parameter: akun" (URL tetap `/keuangan/akun/...`).
- Gambar disimpan ke disk `public` folder `bukti-transfer`; `php artisan storage:link` sudah dijalankan.
- Approval tidak mengirim notifikasi `approved` (hanya reject  CS); status lihat di halaman.
- `transaction_date` disimpan datetime (jam ikut); form date dikirim `Y-m-d`  jam 00:00.
- **Keterangan = template chat CS** (19 Agustus): `description` max **5000** char (kolom `text`); CS menempel template konfirmasi pesanan utuh. Klik **keterangan / thumbnail bukti** (khusus role approver)  **modal detail**: foto besar  keterangan (`white-space:pre-wrap`, baris baru terjaga)  tombol **? Download Bukti** (`<a download>`) + **?? Salin Keterangan** (clipboard + fallback execCommand). `data-desc` di-encode `rawurlencode` (newline & quote aman di atribut HTML)  `decodeURIComponent` + `textContent` di JS (anti-XSS). CS melihat keterangan/bukti polos (tanpa klik).
- **Saldo awal rekening (saldo endap BNI/dll.)** diisi di field `current_balance` saat **tambah akun** (menu Keuangan  Akun Keuangan  Tambah). Saat membuat akun bank baru (mis. BNI), masukkan saldo yang sudah ada di rekening tersebut ke field **Saldo Awal (current_balance)**. Angka ini jadi basis saldo buku; transaksi selanjutnya (bukti transfer, transfer antar akun) akan menambah/mengurangi dari sini. Jika akun sudah dibuat tapi saldo awal salah, edit akun & ubah `current_balance` langsung (tanpa lewat FinanceService, karena ini penyesuaian buku awal bukan transaksi).
- Test memakai DB bersama tanpa refresh  nama akun/kategori pakai `uniqid()`, gambar via `Storage::fake('public')`.
- Suite: **159 pass** (hanya `ExampleTest` 302 pre-existing).

---

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

## U. ✅ Iklan Produk Testing — Status iklan testing/running + CPA terpisah (20 Agustus 2026)

### Deskripsi
Produk kini punya **status iklan** (`ad_status`): **testing** (fase uji coba) atau **running** (sudah aktif diiklankan). Saat admin menambahkan produk baru, status iklan default-nya `testing` — admin/SuperAdmin/mentor bisa mengubahnya ke `running` via toggle di halaman Produk. Spending iklan produk testing **tidak dimasukkan ke perhitungan CPA Lead/Paid** (hanya spending yang ditampilkan, tanpa CPA).

### Skema
- `products.ad_status` varchar(10) default `testing`, index — enum `testing`/`running`
- Kolom `status` (active/inactive) TIDAK berubah — tetap untuk enable/disable produk

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_20_000000_add_ad_status_to_products_table.php` | + `ad_status` default `testing`; backfill existing → `running` |
| `app/Models/Product.php` | constants `AD_STATUS_TESTING`/`AD_STATUS_RUNNING`, `AD_STATUSES`, `AD_STATUS_LABELS`; `scopeAdStatus()`, `isTesting()`, `isRunning()`; `$fillable` + `ad_status` |
| `app/Http/Controllers/ProductController.php` | `validateProduct()` + `ad_status` in:testing,running; default `testing` saat create; `toggleAdStatus()` flip testing↔running |
| `app/Http/Controllers/SpendingHarianController.php` | `indexAdvertiser()` — `computeSummary()` helper; split `$rows` → `$runningRows`/`$testingRows`; 2 summary: `$runningSummary` (Spending+Lead+Paid+CPA) & `$testingSummary` (Spending saja; CPA ikut terhitung tapi tak dipakai global) |
| `database/seeders/ProductSeeder.php` | + `ad_status = running` saat re-seed (produk existing dianggap sudah melalui fase testing) |
| `routes/web.php` | `PATCH /product/{product}/toggle-ad-status` (`product.toggle-ad-status`) |
| `resources/views/product/index.blade.php` | Kolom "Iklan" (toggle + badge 🟢Running/🔬Testing), filter dropdown "Semua Iklan", modal form + field `ad_status` |
| `resources/views/spending/index-advertiser.blade.php` | Tab 🔵Running / 🔬Testing di atas tabel; **4 kartu summary mengikuti tab aktif** (nilai kedua tab di-render ke `data-run`/`data-test`, JS `applySummary()` menukar saat tab berpindah — label badge, title, & progress bar ikut); tabel Testing terpisah dengan **Lead/Paid/Ratio/CPA dihitung sendiri** (tak masuk global) |
| `resources/views/spending/index-general.blade.php` | Badge 🔬Testing di samping nama produk (CS/admin) |
| `tests/Feature/SpendingSummaryTest.php` | Product helper + `ad_status => 'running'` |
| `tests/Feature/SpendingUploadTest.php` | Product helper + `ad_status => 'running'` |
| `tests/Feature/SpendingBulkUpdateTest.php` | Product helper + `ad_status => 'running'` |

### Endpoint
- `PATCH /product/{product}/toggle-ad-status` — flip testing↔running (JSON `{success, ad_status}`)

### Penting
- **Default `testing`** — produk baru HARUS diubah admin ke `running` setelah melalui fase testing. Semua produk existing sudah di-backfill ke `running` (seeder + migrasi).
- **CPA hanya dari Running utk CHART** — chart selalu menampilkan data Running saja (testing tidak pernah masuk chart). **4 kartu summary kini mengikuti tab aktif**: tab Running aktif → kartu menampilkan `$runningSummary`; tab Testing aktif → kartu menampilkan `$testingSummary` (keduanya dihitung `computeSummary`, CPA ikut terhitung). Implementasi: kedua set nilai di-render ke atribut `data-run`/`data-test` di tiap elemen kartu + `applySummary(tab)` di JS (dipanggil dari `switchAdTab`) menukar `textContent`, warna badge `.sc-tab-badge`, `title`, dan style progress bar.
- **Chart 4 garis**: Lead/Paid Running (solid: ungu `#8b5cf6` & teal `#4ECDC4`, fill) + Lead/Paid Testing (putus-putus `borderDash:[6,4]`: oranye `#f97316` & kuning `#fbbf24`, tanpa fill). Data dihitung **per produk per tanggal** (`$chartRunLead/RunPaid/TestLead/TestPaid` dari `by_product` filter `ad_status`) — garis Running kini murni running (sebelumnya memakai total harian yang ikut testing di hari campuran).
- **Tab Running/Testing** — JavaScript `switchAdTab()` toggle visibility `#adtabcontent-running` dan `#adtabcontent-testing`. Default = Running.
- **parseUpload** tidak berubah — semua produk (testing & running) tetap ter-cocokkan saat upload file spending.
- **`Product::AD_STATUS_TESTING`** = `'testing'` — guard di `primaryInventoryId()` tidak terpengaruh (guard pakai `goods_type`, bukan `ad_status`).
- **Test** memakai `ad_status => 'running'` agar summary tetap menampilkan data. Suite **143 pass** (hanya `ExampleTest` 302 pre-existing).

---

## V. ✅ Regional Running-Only — Lead/Paid Produk Testing Dilewati (3 September 2026)

### Deskripsi
File yang diunggah di halaman **Detail Per Daerah** (`/regional`) adalah file yang SAMA dengan file regional halaman Spending (input form nomor 2). Karena itu tiap baris memuat nama produk (kolom `product`, format `P.1 - Nama Produk - 22760`) yang bisa dicocokkan ke DB. Keputusan user: **tabel utama hanya menampilkan lead/paid produk ber-status iklan RUNNING**; lead/paid produk TESTING tidak diperlukan di halaman ini → dilewati (tidak dihitung & tidak disimpan).

### Implementasi
| File | Keterangan |
|---|---|
| `app/Services/RegionalImportService.php` | `parseExcel()` + deteksi kolom `product`/`produk` (exact lalu contains, header row 1); per baris, nama produk dipecah 3 area `-` (area 1 teritorial, area 2 nama → `ProductNameMatcher::match` exact→contains→levenshtein, area 3 kode whitelist diabaikan) → `product_status` (running/testing/null) via `Product::pluck('ad_status','id')` (1 query batch, anti N+1); baris testing dihitung ke `skipped_testing`; `previewData()` TIDAK menghitung baris `product_status=testing` di agregasi provinsi (CS stats & phone mapping TETAP mencakup semua baris — performa CS & kontak tidak dipilah status); return `skipped_testing` di parse & preview |
| `app/Http/Controllers/RegionalController.php` | `preview()` passthrough `skipped_testing` ke JSON (save/index TIDAK berubah — items preview sudah running-only) |
| `resources/views/regional/index.blade.php` | Preview modal: variabel `previewSkippedTesting` + catatan amber `.preview-testing-note` "🔬 N lead produk Testing dilewati (tabel hanya menampilkan produk Running)" di bawah statistik |
| `tests/Feature/RegionalImportTest.php` | 4 test: preview mengecualikan testing (2 running lead/paid terhitung, `skipped_testing=2`), save menyimpan hanya running (RegionalReport lead 2/paid 1), save mengganti baris tanggal yang sudah ada (`updated=1, imported=0`, tidak dobel, tidak nyasar tanggal lain — JS asli memfilter `lead>0 || paid>0`), file tanpa kolom product → semua baris dihitung (backward-compatible) |
| `resources/views/regional/index.blade.php` | **Konfirmasi simpan** (follow-up): handler `previewSave` kini POST `dates[]` + `user_id` ke `regional.check-existing` (route & `checkExistingDates()` sudah ada), lalu modal `#modal-save-confirm` menampilkan daftar tanggal yang akan disimpan — hijau "BARU → AKAN DITAMBAH" / merah "SUDAH ADA → AKAN DIGANTI" + warning bila ada existing; tombol "💾 Ya, Simpan" → `doSave()`. Tanggal simpan diambil PERSIS dari `data-tanggal` tabel preview (tidak dihitung ulang) |

### Follow-up (investigasi "data nyasar ke tanggal 2 Sep")
- **Kesimpulan**: sistem TIDAK menggeser tanggal — file yang diunggah memang memuat tanggal 2 September (bukti: data lama Sept 1 created 01-09 23:20 tidak tersentuh; data baru Sept 2 created 03-09 00:06 = persis waktu simpan). `parseDate` akurat di 8 format + end-to-end file asli `training/DataDariOrderOnline(mentah).csv` ter-parse persis.
- **Pembersihan data uji coba**: `regional_reports` & `regional_cs_stats` user 6 tanggal 2026-09-02 dihapus (kontak tidak disentuh).

### Penting
- **Produk tak dikenal (nama tidak cocok DB) TETAP dihitung** — hanya produk yang jelas ber-status `testing` yang dilewati (konservatif, perilaku lama tidak berubah).
- Kolom product TIDAK wajib: bila tidak ada, semua baris dihitung seperti sebelumnya.
- **Konsekuensi + penyesuaian**: karena regional_reports kini hanya memuat running, perhitungan discrepancy ikut diselaraskan — sisi SPENDING pembanding kini HANYA produk running (`whereHas('product', ad_status=running)`) di **4 titik**: `SpendingHarianController::computeDiscrepancy`, `computeDiscrepancyBatch`, `RegionalController::index` (alarm banner), dan `checkDiscrepancy` (badge sidebar). Spending produk testing TIDAK lagi memicu alarm ketidaksesuaian. (Konsekuensi di paragraf lama — "alarm bisa nyala bila spending punya testing" — sudah TIDAK berlaku sejak penyesuaian ini.)
- **Banner discrepancy 2 kelompok** (follow-up): `computeDiscrepancy`/`computeDiscrepancyBatch`/`RegionalController::index` kini mengembalikan tambahan `missingSpendingDates` — tanggal yang punya data REGIONAL tapi spending-nya KOSONG (`regLead|regPaid > 0 && spLead==0 && spPaid==0`) dipisah dari `discrepancies` (kedua sisi punya data tapi selisih). SATU banner (tetap merah, `@if($hasDiscrepancy)` yang sama) kini punya 2 area dipisah garis putus-putus: (1) "Ketidaksesuaian Data Ditemukan!" + rincian angka per tanggal, (2) "Data Belum Ditambahkan" + kalimat "...belum mengisi data spending iklan tanggal {d M Y}". Berlaku di `spending/index-advertiser`, `spending/index-general` (`data['missing_spending_dates']`), dan `regional/index`.
- **Jebakan teknis view**: (1) sintaks **inline `@php($x = ...)`** di Blade GAGAL compile bila ekspresi memuat array literal + chained index (parse error unexpected end of file) → WAJIB blok `@php ... @endphp`; (2) `translatedFormat` mengikuti locale app (test = en) dan proyek TIDAK punya paket carbon locale id (`locale('id')` malah menghasilkan "02 Agt") → format tanggal Indonesia memakai array bulan manual `(int) substr($tgl,8,2) . ' ' . $BULAN_ID[(int) substr($tgl,5,2)] . ' ' . substr($tgl,0,4)`.
- Badge baris "DATA BELUM DIISI" TIDAK dipasang — tanggal dengan spending kosong tidak punya baris di tabel (tabel hanya merender tanggal berdata), jadi badge mustahil tampil; kebutuhan dipenuhi banner saja.
- `ProductNameMatcher` dipakai ulang (sama persis dengan halaman spending) — skema `code`/`name`/`ad_status`.
- Suite: **148 pass** (hanya `ExampleTest` 302 pre-existing).

---

# Fitur Belum Selesai / Ide ke Depan

## 📌 Sesi Berikutnya (setelah 27 Agustus): Lanjutan Sektor Keuangan

Modul dasar keuangan (fitur U) SELESAI: akun, kategori, transfer antar akun, bukti transfer + approval. Sisa yang bisa dikerjakan berikutnya:

- ✅ **FIXED (27 Agustus)**: `SpendingHarian::approve()` no-op — sudah diperbaiki:
  - Tambah kolom `status` (default `pending`) ke tabel `spending_harians` via migration `2026_08_27_120350`
  - Tambah `status` ke `$fillable` di model `SpendingHarian`
  - Tambah badge Status + tombol Approve per baris di `spending/index-general.blade.php`
  - Route `PATCH spending/{spending}/approve` → set `status = 'approved'`

- ✅ **FIXED (27 Agustus)**: Halaman `/orders` — cards rapih (sistem mini-stat) + grafik tren harian interaktif Chart.js (drag-to-scroll, toggle legend, 4 dataset: Total/Real/Tembakan/Lead). Courier cards: filter null key, tiap courier punya warna + ikon berbeda.

- **Dashboard keuangan** — sudah dicek: `dashboardKeuangan()` sudah kirim `$topAdvertiser`, semua blade compile OK. Jika masih 500 saat login, cek error log aktual.

- **Top-up belum punya test** — `TopUpController` (proposal → approve → bayar → va-paid → confirm) belum ter-cover suite.
- Pertanyaan yang belum dijawab user: apakah saldo akun boleh negatif (rekening vs cash), kolom bank/atas nama di `accounts`, dan apakah reject perlu alur CS upload ulang (edit bukti yang ditolak).

- Tabel keuangan yang relevan saat ini: `spending_harians`, `top_up_proposals`, `top_up_proposal_items`, `whitelists` (total_topup/total_spending/nominal_terakhir_topup), `users` (role advertiser/keuangan), `notifications`, `regional_reports`, `regional_cs_stats`; sisi operasional pendukung: `shipping_orders`, `shipments`, `stock_movements`, `purchases`, `products`, `product_variants`.
- Alur top-up yang ada: proposal → approve → bayar → va-paid → confirm saldo. Alur spending: input harian → approve (role keuangan/owner).
- Login demo: `owner@awanna.id` / `password` (semua user seeder pakai `password`).

**Langkah besok:** (1) user kasih daftar tabel + alur keuangan versi mereka → (2) cocokkan dengan modul U yang sudah dibangun → (3) tentukan apa yang baru/diubah → (4) bangun + test.
