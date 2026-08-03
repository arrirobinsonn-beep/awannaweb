# MEMORY — 03 Agustus 2026

## Session (lanjutan): Stok Jurnal + Barang Masuk (Purchase) + Shipment auto-kurangi stok

- Migrasi baru dijalankan di DB `awannacoba`: `stock_movements`, `purchases`, `shipments.product_id` (3 migration 2026_08_03_1000xx)
- `ProductController::store/update` tidak lagi validasi `stok` (dihapus dari rule), `stok` tetap di fillable
- Dibuat opening stock (8 jurnal `adjustment/in`) senilai stok manual lama agar konsisten
- Test terverifikasi:
  - Matcher: exact/contains/levenshtein bekerja; "Kacamata Sporty" (belum ada) → unmatched (tidak disimpan); "Tote Bag Canvas" → cocok
  - Import FLIK: 2 cocok (stok Tote Bag 150→148), 2 unmatched; re-import idempotent (`unchanged=2`, stok tetap 148, tanpa duplikat jurnal)
  - Purchase store via controller: stok 148→153, HPP rata-rata tertimbang 29836.60 (cek manual cocok); destroy balik ke 148, jurnal 0
  - `recordOut` tolak overdraw (RuntimeException), stok tetap
  - `stockOf()` == kolom `stok` untuk 8 produk (SEMUA KONSISTEN)
- Data test import dibersihkan; DB kembali ke keadaan bersih + opening stock
- Pint fixed: `ShipmentImportService`, `ProductNameMatcher`, `PurchaseController`; `view:cache` OK; `route:list` OK (barang-masuk GET/POST/DELETE, jurnal-stok, pengiriman)
- AGENTS.md & MEMORY.md diperbarui (section C)

## Session: Fitur Gudang/Stok Lama Dihapus → Shipment Terpadu (FLIK/SiCepat/SPX) + Rename ke Bahasa Inggris

### Perubahan

**1. Fitur gudang/stok/kiriman lama DIHAPUS SELURUHNYA**
- Hapus models: `PembelianBarang`, `StockMovement`, `StockRecap`, `KirimanActual`, `KirimanActualProduct`, `PaketTracking`, `RtsRecap`, `Dashboard`
- Hapus services: `KirimanImportService`, `UndelImportService`, `OrderOnlineImportService`
- Hapus controller: `OrderOnlineController`; hapus command `BackfillPaketTrackingProductId`
- Hapus 15 migration lama gudang/stok/kiriman; hapus views `gudang/*` (kecuali `master.blade.php`)
- Hapus dashboard view `admin.blade.php` + `cs.blade.php`
- Yang dipertahankan: `Product`, `Supplier`, `Gudang` (master tempat gudang) — routes `gudang.master*`

**2. Fitur baru: Shipment Terpadu (semua nama kode BERBAHASA INGGRIS)**
- Tabel: `shipments` (unique `(source, tracking_number)`) + `shipment_status_histories` (FK cascade)
- Models: `Shipment` ($table = 'shipments'), `ShipmentStatusHistory`
- Service: `ShipmentImportService` — parse/detectSource/aliasMap/normalizeRow/import (transactional upsert + diff + status history)
- Controller: `ShipmentController` (`index`, `preview`, `store`)
- Command: `shipment:import {file?}` (dukung path absolut), schedule `dailyAt('02:00')` di `routes/console.php`
- Views: `shipment/index.blade.php` (upload + preview modal + filter + datatable; label user tetap Indonesia)
- Routes: `shipment.index/preview/import`, URL tetap `/pengiriman`
- Sidebar: section "Kiriman" → link `shipment.index`

**3. Rename Indonesia → Inggris (migration `2026_08_03_000000_rename_pengiriman_tables_to_english.php`)**
- `pengirimans`→`shipments`, `pengiriman_status_histories`→`shipment_status_histories`
- `sumber`→`source`, `no_resi`→`tracking_number`, `kurir`→`courier`, `nama_penerima`→`recipient_name`, `telepon`→`phone`, `alamat_lengkap`→`full_address`, `kecamatan`→`district`, `kota`→`city`, `provinsi`→`province`, `kode_pos`→`postal_code`, `nama_produk`→`product_name`, `jumlah`→`quantity`, `ongkir`→`shipping_fee`, `nilai_paket`→`parcel_value`, `nominal_cod`→`cod_amount`, `catatan_kurir`→`courier_note`, `tanggal_buat`→`created_date`, `tanggal_pickup`→`pickup_date`, `tanggal_terkirim`→`delivered_date`, `file_sumber`→`source_file`, `pengiriman_id`→`shipment_id`, `dilihat`→`viewed_at`
- Data dipertahankan (8596 rows), index & FK dibuat ulang dengan nama Inggris

**4. Perbaikan ikutan**
- `Product` model: hapus relasi `pembelianBarangs()` dan `stockMovements()`
- `RegionalImportService`: inline `normalizePhone()` (menggantikan `OrderOnlineImportService::normalizePhone`); view regional hapus cabang upload OO yang sudah mati
- `ImportShipments`: dukung path absolut (jangan di-prepend `base_path`)
- `Shipment` model: pakai `$table = 'shipments'` karena Laravel pluralize "Shipment" → "Shipments" (tidak masalah, tapi eksplisit lebih aman)

**5. Detail penting service import**
- Diff: bandingkan string (trim), float (cast), tanggal (format Y-m-d) secara terpisah — mencegah false-positive karena cast decimal `89000.00` vs `89000`
- `parse()` mengembalikan key `skips` (bukan `skip`)
- Kunci natural `(source, tracking_number)`, UPSERT harian, tidak ada delete

### Branch
- `kayu-dev`

### Status
- Migrasi sudah dijalankan di DB `awannacoba`; data produksi utuh: 5832 FLIK / 943 SICEPAT / 1821 SPX (total 8596) + 8597 status histories
- `php artisan view:cache` OK, `route:list` OK, pint sudah dijalankan
