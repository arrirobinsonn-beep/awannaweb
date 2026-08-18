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
- **Kode Warehouse** = `OrderTemplateExportService::warehouseFor(product_code, sender)` → `KSP`→**GTM**, `SH`→**Aurora**, produk lain→**sender**. Kolom "Kode Warehouse" FLIK diisi per-baris dari sini. `warehouseFor()` memakai kode dasar (`explode('+', $code)[0]`) agar tahan `product_code` berformat kode varian (`KSP+1.50`→GTM).
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

### Deteksi duplikat (hanya untuk `belum_diproses`)
- Signature duplikat = `phone_normalized|product_code|alamat` (normalized: lowercase/trim/kolaps spasi) → `OrderOnlineImportService::orderSignature()`; kode produk dipakai versi master (`explode('+')[0]`).
- Pencocokan terhadap seluruh DB dengan `created_at >= now() - 14 hari` (`DUP_WINDOW_DAYS=14`, 1 batch query `whereIn` phone via `loadDuplicateSignatures()`).
- ≤14 hari cocok → **`duplikat`** (courier=null, tidak ikut export); >14 hari → **repeat order** (diproses normal sebagai `belum_diproses`).
- Duplikat dalam 1 file yang sama juga tertangkap (signature ditambahkan ke set saat loop).
- `real`/`tembakan` TIDAK pernah ditandai duplikat (promosi) dan TIDAK menambah signature ke set.

### Re-import data yang sama (by `order_id`, 9 Agustus)
- Baris `real`/`tembakan` **menghapus permanen** baris lama dengan `order_id` sama yang berstatus `belum_diproses`/`duplikat` (di batch mana pun, dalam `DB::transaction` yang sama; `ShippingOrder` tanpa SoftDeletes). `cancel` dan `real` lama TIDAK dihapus/ditimpa.
- **Anti double-export**: jika `real`/`tembakan` dengan `order_id` sama SUDAH ada di batch lain → baris TIDAK di-insert (dihitung ke `double_real`); statistik batch `success_rows = inserted + updated + duplicates`, `failed_rows = double_real`. Pencocokan memakai 1 query batch `whereIn` `order_id` → `groupBy` (`$byOrderId`, anti N+1).
- Hasil import bertambah key `deleted` (baris lama yang dihapus) & `double_real`; pesan flash controller: `Baris belum diproses lama dihapus: N` dan `Real di-skip (sudah ada): N`.

### Stok via product_code
- Import: `product_code` (CSV) di-resolve exact-match ke `products.kode_produk` → `product_id` (1 batch query `whereIn`); tak cocok → `product_id=null`.
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
| `app/Services/OrderTemplateExportService.php` | `download(batch, template, courier?)` → .xlsx atau ZIP per gudang (PhpSpreadsheet); `WAREHOUSE_BY_PRODUCT` (KSP→GTM, SH→Aurora) + `warehouseFor()` (tahan kode varian via `explode('+')`); `PACK_DIMENSIONS=[10,8,6]` + `DEFAULT_COURIER_NOTE`; `phoneSpx()` (mulai 8) + CAPSLOCK utk SPX; filter `EXPORTABLE_STATUSES`; `reserveStock()` (recordOut + stock_note); Kelurahan kosong; nilai=product_price |
| `app/Http/Controllers/OrderOnlineController.php` | `index` (batch + orders + `$products` utk select), `preview`, `store` (sender required, tampilkan jumlah duplikat), `update` (edit courier/product_code; varian HANYA di-re-resolve bila product_code berubah via `ProductVariant::where('code')`, reverseReference dulu bila ada jurnal), `export` |
| `resources/views/order/index.blade.php` | Upload (sender wajib) + preview modal + daftar batch + tabel orders (badge status incl. duplikat/cancel/belum_diproses, kolom produk+stock_note) + edit courier & product_code (dropdown per varian) inline + dropdown export FLIK per courier |
| `database/migrations/2026_08_09_000000_add_meta_account_to_shipping_orders_table.php` | kolom `shipping_orders.meta_account` (string nullable) |
| `resources/views/layouts/app.blade.php` | Sidebar section Iklan → "Data Mentah" |
| `tests/Feature/OrderOnlineTest.php` | 38 test: courier resolve (incl. sicepat), status mapping (incl. completed skip, courier null), resolve product_id, render, duplikat (window/same file/repeat order/promosi), FLIK separated by courier+status, stok idempotent, skip stok kurang, undeliverable balikin stok, undeliverable→courier normal tidak dobel, undeliverable varian non-default balikin stok & varian tetap, edit courier product_code sama → varian tetap, ganti product_code dgn jurnal ada → stok varian lama balik, reimport real hapus belum_diproses lama, reimport real tidak dobel (double_real), warehouse mapping, ZIP split SH/KSP/sender, phoneSpx 8 + CAPSLOCK, filename sender, tembakan→spx, product meta_account split, dapat qty override + product_name, product_code = kode varian, warehouseFor varian, dimensi & catatan kurir per template, FLIK 1 kolom HP 62, nama kacamata +power, nama non-kacamata tetap |

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
Saat stok kacamata (KMP/KSP/KBJ) berkurang lewat pengiriman, stok kemasan ikut berkurang otomatis: **BOX + LAP = floor(qty/2)**. Khusus **KBJ selalu di-split**: KBJ = ceil(qty/2) + KDF = floor(qty/2) (KDF varian ber-power sama dengan varian KBJ, fallback power terkecil). Berlaku di **dua alur**: export order-online (`reserveStock`) DAN import shipment ber-resi (`ShipmentImportService`). Split & kemasan HANYA memengaruhi stok/jurnal — isi template export tetap 1 baris qty asli.

> **Produk baru (seeder):** BOX 'Box Kacamata' & LAP 'Lap Pembersih' (aksesoris, non-sized, 1 varian default, stok 1000), KDF 'Kacamata Double Fokus' (kacamata pendamping KBJ, sized → 9 power, stok 1000 dibagi rata). KDF tidak memicu pengurangan saat terjual sendiri.

### Aturan
| Produk | Pengurangan saat kirim qty |
|---|---|
| KMP, KSP | produk −qty; BOX −floor(qty/2); LAP −floor(qty/2) |
| KBJ | KBJ −ceil(qty/2); KDF −floor(qty/2); BOX −floor(qty/2); LAP −floor(qty/2) |
| lainnya | produk −qty (tanpa kemasan/split) |

Contoh: KBJ qty 2 → 1 KBJ + 1 KDF + 1 BOX + 1 LAP; qty 1 → 1 KBJ (0 KDF/BOX/LAP).

### Implementasi
| File | Keterangan |
|---|---|
| `database/migrations/2026_08_09_100000_extend_stock_movements_unique_for_packaging.php` | unique `stock_movements_ref_unique` → `(reference, reference_id, type, product_variant_id)` — 1 order/shipment boleh punya banyak baris `out` (kacamata + BOX + LAP [+KDF]) |
| `app/Services/StockService.php` | konstanta `KACAMATA_CODES`/`PACK_*_CODE`/`PACK_QTY_PER=2`; **`recordOutWithPackaging()`** (1 transaksi: main + KDF(bila KBJ) + BOX + LAP; produk pendamping belum ada/stok kurang → `RuntimeException` → rollback atomik); `packagingVariants()` cache per instance (anti N+1); key `updateOrCreate` `recordIn`/`recordOut` kini SERTA `product_variant_id`; anti silent-reassign di-scope per produk (`whereHas variant.product_id`) |
| `app/Services/OrderTemplateExportService.php` | `reserveStock` → `recordOutWithPackaging` (alur order-online) |
| `app/Services/ShipmentImportService.php` | 2 call site `import()` → `recordOutWithPackaging` (alur resi aggregator) |
| `database/seeders/ProductSeeder.php` | `SIZED_PRODUCTS` + `KDF`; tambah produk BOX/LAP/KDF + opening stock |
| `tests/Feature/OrderOnlineTest.php` | +6 test packaging/split (total 38) |

### Penting
- **Key `updateOrCreate` `recordOut` WAJIB menyertakan `product_variant_id`** — kalau tidak, baris `out` berikutnya untuk ref sama (mis. BOX) menimpa varian baris sebelumnya (KMP), lalu LAP menimpa BOX → `firstOrFail()` final gagal → transaksi rollback → tidak ada jurnal sama sekali (ekspor diam-diam tidak mengurangi stok apa pun).
- `powerList()` menghasilkan **9** power (1.00–3.00 step 0.25), bukan 10 → sized product total stok 999 (9×111).
- `reverseReference` (undeliverable / ganti produk) otomatis membalik SEMUA movement pendamping (delete semua + recalc tiap varian).
- Produk pendamping belum terdaftar/stok kurang → export: order dilewati + `stock_note` berisi pesan; import shipment: batch gagal (perilaku konsisten dgn stok produk utama kurang).

---

## G. ✅ Upload Status Aggregator → awb/aggregator_status/delivered_at + Stok Return (10 Agustus 2026)

### Deskripsi
Admin upload file dashboard aggregator (FLIK / SiCepat / SPX, `.csv` atau `.xlsx`) lewat halaman Data Mentah → kolom `shipping_orders.awb`, `aggregator_status`, `delivered_at` terisi. Baris file dihubungkan ke order memakai **signature**: Tier 1 = `phone_normalized + product_id + quantity + alamat` (normalisasi lowercase/kolaps spasi), Tier 2 (fallback) = `phone_normalized + product_id + quantity` bila tier 1 kosong dan kandidat unik. 0 kandidat → `unmatched`; >1 kandidat → `ambiguous` (tidak diisi).

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
| `app/Services/AggregatorTrackingImportService.php` | `parse` (readRows via PhpSpreadsheet IOFactory csv/xlsx, detectSource header tak selalu baris 1 — SPX di baris 3, mapHeaders aliases, mapStatus, isProblem), `import` (1 transaksi: batch `whereIn phone_normalized` + `ProductNameMatcher` → `resolveOrder` tier1/tier2 → update awb/status/delivered_at → reverseReference bila jadi returned) |
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
- **CBC-101..105** (bank_transfer semua provinsi) → `flix-tf` → template FLIK: KMP+1.50, KMP+1 (qty2), KSP+2 (**GTM**), KBJ+1.25 (qty2 → split), KCHP. FLIK jadi **2 gudang** (GTM + GUDANG-PUSAT) → ZIP (rule F).
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
- `product_price` DB decimal → sel export "119000.00" (bukan "119000"); compare `delivered_at` sebagai string (kolom ber-cast datetime → Carbon).

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

### Implementasi
| File | Keterangan |
|---|---|
| `app/Http/Controllers/TopUpController.php` | `create()`: import `SpendingHarian`, query batch spending kemarin → `$sisaSaldoWhitelists` (properti dinamis `spending_kemarin`/`lead_kemarin`/`paid_kemarin`); `store()`: pesan error custom + atribut `items.{id}.nominal` → nama whitelist |
| `resources/views/topup/create.blade.php` | `.topup-split` grid dua card, checkbox `.wl-select`, JS `syncRowState()`/`hitungTotal()` (hitung baris tercentang saja); tinggi card disamakan via `align-items:stretch` + `height:100%` + `flex:1` pada daftar item |

> **Validasi submit top-up (fix 11 Agustus):** whitelist dicentang tapi nominal kosong → dulu error membingungkan `The items.8.nominal field is required.`. Kini: form pakai **`novalidate`** (native browser dimatikan agar guard JS yang jalan), guard submit mengecek `.wl-select:checked` dengan nominal kosong/negatif → `preventDefault` + alert nama whitelist (`WL_NAMES` dari `@json`) + highlight merah + scroll ke baris. `store()` punya pesan custom `Nominal top up untuk :attribute wajib diisi` dgn atribut dinamis `items.{id}.nominal` → nama whitelist. Saat validasi gagal, `old('items.{id}.nominal')` memulihkan centang + nilai input (`data-was-filled` + `restoreRowState()`), jadi user tidak mengetik ulang. |

> ⚠️ **Reserved word MySQL — kolom `lead` WAJIB backtick di raw SQL** (fix 11 Agustus): `spending_harians.lead` adalah **reserved word** di MySQL 8.4 → `SUM(lead)` tanpa backtick memicu `SQLSTATE[42000] 1064`. SELALU tulis `SUM(\`lead\`)` di `selectRaw` (contoh kanonik: `DashboardController`, `SpendingHarianController`, `RegionalController`). Kolom `spending` & `paid` aman tanpa backtick.

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
