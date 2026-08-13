# MEMORY — 12 Agustus 2026 (sore)

## Session: Template Export BEBAS (custom) + Halaman Kelola Terpisah (fitur N1)

- **Latar**: user minta tampilan Aturan Export dipecah — template yang ada dapat opsi **Edit** dan **Hapus**; **create template baru** dipisah ke halaman sendiri. User klarifikasi: data mentah (order online) beda dari courier (ekspedisi) — template export adalah format ke aplikasi ekspedisi; **hapus permanen disetujui** (rule `spx` di courier_rules jadi safety net bila template hilang).
- **Tabel `export_templates`** (migration `2026_08_12_120000`): `key` unique, `name`, `couriers` (JSON), `is_active`; seed 3 bawaan (flik→4 flix-*, sicepat→[sicepat], spx→[spx]). `export_template_mappings.template` tetap string = `key` (relasi string via `hasMany(ExportTemplateMapping::class,'template','key')` — TANPA alter tabel mapping).
- **`ExportMappingService`**: + `templates()`, `template(key)` (cache), `couriersForTemplate(key)` DB-driven + fallback `LEGACY_COURIERS`, `createTemplate` (key auto-slug `Str::slug`, couriers kosong → `[key]`), `updateTemplate`, `deleteTemplate` (1 transaksi: hapus mappings + row). `OrderTemplateExportService::couriersForTemplate` delegasi ke service.
- **Controller 7 method**: `index` (list + `withCount`), `create`, `store`, `edit`, `update`, `destroy` (permanen), `upload` (template **opsional** — create tanpa key → semua empty; edit dgn key → `matchHeaders` bawa mapping lama). Validasi items: source_type enum, column/computed harus di registry, static tak boleh kosong, column_index unik (abort 422).
- **Views**: `index.blade.php` = daftar kartu (ikon, nama, key, kolom count, badge courier, status, Edit/Hapus + tombol Template Baru); `form.blade.php` = editor create/edit (Nama, Courier koma, Upload CSV fetch draft, tabel mapping, submit build `items[]`). Halaman tabs lama dihapus.
- **Integrasi Data Mentah**: `OrderOnlineController::index` pass `$exportTemplates`; tombol export di-loop — key `flik` → dropdown per courier (tetap, label `FLIK — {courier}` agar test render lama lolos), lainnya → tombol `📦 Export {name}`. `export()` cek template via `ExportTemplate::where('key')` (custom OK, 404 bila tak ada).
- **Trap**: `$this->couriers` di OrderOnlineController adalah CourierRuleService (bukan ExportMappingService) → cek template pakai query model langsung.
- **Test**: `ExportMappingTest` 14 test (index list + aksi, create/edit render, upload parse + carry-over, store custom slug+couriers default, tolak dobel index/kolom tak dikenal, update, destroy permanen, **export template custom courier lain tidak ikut**, regresi seeded layout, tanpa mapping → RuntimeException, SPX transform). `OrderOnlineTest` + `CourierRuleTest` + `AggregatorTrackingImportTest` tetap hijau. Total 4 file test **79/79 pass**; pipeline `verify_pipeline.php` **103/103 PASS**.
- AGENTS.md section N1 ditulis; migration + seeder sudah dijalankan di DB dev (3 template master + 65 mapping).

# MEMORY — 12 Agustus 2026

## Session: Aturan Export Dinamis — Upload Template CSV + Mapping Kolom (fitur N)

- **Latar**: mapping export `shipping_orders` → template courier (FLIK/SiCepat/SPX) masih hardcoded di `OrderTemplateExportService` (`flikRows/sicepatRows/spxRows`). User minta dinamis: upload template CSV → cocokkan tiap header dengan kolom `shipping_orders` → simpan di DB.
- **Keputusan (Q&A)**: (1) selain kolom DB, sediakan **nilai khusus/computed** (dimensi 10/8/6, berat 1, catatan kurir default, nama produk +power, Kode Warehouse, phone mulai 8, CAPSLOCK, dll); (2) **seed 3 template bawaan** persis layout lama → export identik sebelum diedit; (3) mapping **per template** (FLIK/SiCepat/SPX), bukan per courier.
- **Tabel `export_template_mappings`** (migration `2026_08_12_100000`): `template`, `column_index`, `header`, `source_type` (column/computed/static/empty), `source_value`, `is_active`; UNIQUE `(template, column_index)`.
- **`ExportMappingService`**: registry `COLUMNS` (25 kolom shipping_orders) & `COMPUTED` (15 key) = satu-satunya sumber kebenaran (dropdown UI = resolver service); `mappingFor()` cache per request (groupBy template); `parseTemplateFile()` (fgetcsv, BOM, buang trailing empty); `matchHeaders()` (bawa mapping lama by nama header saat upload ulang); `saveMapping()` replace per template 1 transaksi.
- **`OrderTemplateExportService`**: `writeRows()` → `buildRows(template, orders, sender)` (header+data dari mapping DB); `resolveCell()`/`columnValue()`/`computedValue()`; method `flikRows/sicepatRows/spxRows` DIHAPUS. `columnValue` whitelist registry + format Carbon → `Y-m-d H:i`; `computedValue` mencakup 15 key (warehouse, product_name_display, phone_spx, weight_1, pack_length/width/height, default_courier_note, cod_flag, cod_amount, payment_method_upper, province/city/district_upper, order_id_50).
- **Seeder `ExportTemplateMappingSeeder`** (DatabaseSeeder #9): 65 row (FLIK 16, SiCepat 27, SPX 22) meniru persis array lama — pipeline export diff **103/103 PASS** membuktikan identik.
- **Controller `ExportMappingController`**: `index` (3 tab), `upload` (parse header → JSON + matchHeaders), `save` (validasi: source_type in enum, column/computed harus di registry, static tidak boleh kosong, **column_index unik**).
- **View `export_mapping/index.blade.php`**: tabs per template, tombol upload CSV (fetch → draft rows, X-CSRF-TOKEN), tabel header + dropdown optgroup (kolom DB / nilai khusus / teks tetap + input), simpan build `items[<index>]`. Escaping: `escHtml`/`escAttr` untuk header.
- **Routes**: `GET/POST /export-mapping`, `POST /export-mapping/upload|save` (`export-mapping.*`). Sidebar Data Master → "Aturan Export".
- **Test**: `ExportMappingTest` 10 test (index, upload parse, carry-over mapping lama, save persist, tolak kolom tak dikenal/static kosong, export custom mapping 3 kolom, **regresi seeded vs layout lama** — amount di xlsx jadi number 10000 bukan "10000.00", export tanpa mapping → RuntimeException, SPX transform). `OrderOnlineTest` +`setUp()` seed mapping (export test butuh DB mapping).
- **verify_pipeline.php**: precheck `export_template_mappings` + panggil `buildRows()` via reflection (bukan `flikRows` dll) → **103/103 PASS**.
- **Verifikasi**: `migrate` + seed di DB dev OK (65 row); 4 file test **74/74 pass** (327 assertion); pipeline **103/103 PASS**; sisa referensi `flikRows` hanya variabel lokal di `generate_test_kit.php` + doc.

# MEMORY — 10 Agustus 2026

## Session: Rename kolom harga order online — `product_price`/`cod_amount` → `amount`/`shipping_cost` (13 Agustus)

- **Latar**: user menemukan kolom DB `cod_amount` & `product_price` ternyata diisi dari kolom `gross_revenue` CSV (nilai kotor = harga + ongkir; data nyata: `product_price=119000`, `gross_revenue=169000`, `shipping_cost=50000`, `net_revenue=119000`). Nilai `cod_amount` (119000) = harga produk bukan nominal COD → menyesatkan. User minta rename agar mudah dibaca & cukup deploy `php artisan migrate`.
- **Keputusan mapping (Q&A)**: `amount` = CSV `gross_revenue` (fallback `product_price` bila kosong/0); `shipping_cost` = CSV `shipping_cost`; **`cod_amount` dihapus** (redundan). Export FLIK/SiCepat/SPX (`OrderTemplateExportService`) nilai barang & nominal COD ikut `$o->amount` (incl ongkir, gross_revenue).
- **Migration `2026_08_13_000000_rename_shipping_order_price_columns.php`**: `renameColumn product_price→amount`, `cod_amount→shipping_cost` (decimal 14,2) + **backfill dari `raw_payload` JSON** per baris (`gross_revenue`→amount, `shipping_cost`→shipping_cost; tanpa gross_revenue → biarkan nilai lama; tanpa shipping_cost → 0). Aman tanpa re-import karena 13/13 baris punya `raw_payload` berisi kedua key.
- **Import service** (`OrderOnlineImportService::normalizeRow`): `$amount = decimal(value('gross_revenue')); if (<=0) $amount = decimal(value('product_price'))`; `$shippingCost = decimal(value('shipping_cost'))`; row keys `amount`/`is_cod`/`shipping_cost` (tanpa `product_price`/`cod_amount`). `buildRawPayload` tetap menyimpan semua kolom mentah termasuk `product_price`.
- **Model** `ShippingOrder`: fillable+casts `amount`/`shipping_cost` (decimal:2) ganti `product_price`/`cod_amount`.
- **Export service**: 5 titik `$o->product_price` → `$o->amount` (FLIK idx10, SiCepat Harga Paket+Total COD, SPX Parcel Value+COD Amount+Item Price) + docblock.
- **`shipments.cod_amount` TIDAK disentuh** (tabel aggregator terpisah, beda makna).
- **Test**: `createOrder`/seed 5 lokasi `'product_price'=>10000` → `'amount'=>10000`. Tidak ada assert nilai kolom harga di test.
- **filecoba kit**: `generate_test_kit.php` tambah helper `shippingCost()` (default 50000) & `grossRevenue()` (=product_price+ongkir → 119000→169000); `rowData` tulis `shipping_cost`/`gross_revenue`; referensi export pakai `grossRevenue()`. Regen 7 file + `verify_pipeline.php` → **102/102 PASS**. Harga CSV mentah lama 119000 → amount 169000 di DB/export.
- **Verifikasi**: `migrate` OK (kolom `amount`/`shipping_cost` ada, lama hilang), `OrderOnlineTest`+`AggregatorTrackingImportTest` **54/54** (250 assertion), `php artisan test` 66/67 (hanya `ExampleTest` 302-redirect yang pre-existing), `verify_pipeline.php` **102/102**.

## Session: Rule duplikat diperluas ke SEMUA status + pengecualian bank_transfer (13 Agustus)

- **Keputusan user**: deteksi duplikat kini berlaku untuk SEMUA status (termasuk `real`/`tembakan`), dengan **pembeda utama `order_id`**:
  - `order_id` BERBEDA + signature (`phone_normalized|product_code|alamat`) sama ≤14 hari → **`duplikat`** (courier null, tak ikut export). Kasus nyata batch #65 (`278247802`/`278247820`, 2 real cod SH qty9) kini tertangkap.
  - `order_id` SAMA (re-import) → BUKAN duplikat, masuk rule perbarui status / drop (`double_real`).
  - `payment_method=bank_transfer` **TIDAK pernah jadi duplikat** (uang sudah diterima = repeat order), TAPI tetap menambah signature ke set → baris cod/lainnya dgn signature sama & order_id beda tetap bisa ketandai duplikat (first-bank_transfer-lalu-cod → duplikat; first-cod-lalu-bank_transfer → repeat).
- **`loadDuplicateSignatures()`** → `[signature => [order_id => true]]` (bukan `true`), load phone dari SEMUA status (1 batch query `whereIn`, `created_at >= now()-14d`), select tambah `order_id`.
- **Loop import**: `$otherIds = array_keys(array_diff_key($matchedIds, [order_id => true]))`; jika `$otherIds !== [] && ! bank_transfer` → `duplikat` + `$duplicates++`; SEMUA baris (tidak cuma belum_diproses) menambah signature ke set. Guard `phone_normalized !== ''` (phone kosong tidak jadi penanda; kode produk `explode('+')[0]` tetap di `orderSignature`).
- **Test (OrderOnlineTest 35→45)**: `test_bank_transfer_repeat_order_not_duplicate` (cod dulu, bt kedua → tembakan bukan duplikat), `test_cod_after_bank_transfer_is_duplicate` (bt dulu, cod kedua ≤14d → duplikat), `test_real_cod_duplicate_within_same_file` (2 real cod 1 file → ke-2 duplikat), `test_bank_transfer_duplicate_within_same_file_is_repeat` (2 bt → dua-duanya real), `test_reimport_same_order_id_is_not_duplicate` (double_real, bukan duplikat). `test_duplicate_within_same_file` dipindah ke phone valid `0811111111` (dulu `081111` → normalize `''`).
- **Verifikasi**: `OrderOnlineTest` + `AggregatorTrackingImportTest` 54/54 pass (250 assertion); `verify_pipeline.php` **102/102 PASS** (sequential). Cross-check dulu kasus batch #65 sebelum deploy.
- Test `filecoba` pakai phone 10 digit (valid) → tidak terpengaruh guard phone kosong.

## Session: Test Kit Pipeline `filecoba/` (import → export → tracking → stok return)

- **Folder `filecoba/`** (baru): kit uji end-to-end pipeline order-online yang menjalankan service ASLI (bukan mock). `generate_test_kit.php` (mandiri, tanpa boot Laravel, pola `training/make_test_rules_csv.php`) menulis 7 file CSV; `verify_pipeline.php` (boot Laravel, DB aktif `awannacoba`) menjalankan import → export+diff → tracking → cek stok → idempotent, mencetak PASS/FAIL per langkah. **98/98 PASS**, re-runnable.
- **File**: `01_order_online_mentah.csv` (52 kolom, 10 order CBC-101..CBC-302), `02_export_{flik,sicepat,spx}.csv` (**referensi statis format RUNTIME** — FLIK 1-kolom HP "62", bukan template lama `FLIX template.csv` 2-kolom), `03_tracking_{flik,sicepat,spx}.csv` (header dashboard asli: FLIK 29 kolom, SICEPAT 43, SPX 47), `actual_export_*.csv` (artefak diff tiap run).
- **Skenario 10 order**: CBC-101..105 bank_transfer → `flix-tf` (FLIK, KSP+2→**GTM** jadi 2 gudang → ZIP), CBC-201..203 cod Jawa/Bali → `sicepat`, CBC-301/302 pending+paid → `tembakan`→`spx`. Tracking mencakup semua 6 nilai: `waiting_pickup` (CBC-104), `in_transit` (102/202), `delivered` (101/201/301), `returning` (203), `returned` (103 FLIK, 302 SPX → uji balik stok), `problem` (105, 3PL "Problem:...").
- **Keputusan teknis**:
  - Export diff baris-per-baris dengan referensi; `product_price` DB decimal → sel "119000.00" (referensi pakai `number_format(…,2)`); `delivered_at` di-compare sebagai string (kolom ber-cast datetime → Carbon, `(string)` agar `===` cocok).
  - `verify_pipeline.php` akses method protected `reserveStock`/`buildRows` (sejak 12 Agustus; dulu `flikRows`/`sicepatRows`/`spxRows` sebelum refactor mapping dinamis) via **ReflectionMethod** (tanpa mengubah kode produksi). `check()` param `$detail` bertipe `string` → jangan lempar `null` (`$mismatch ?? ''`).
  - **Re-runnable**: di awal verify, order CBC-* lama dihapus + `StockService::reverseReference('order_online', $order->id)` → stok kembali baseline sebelum re-import.
  - Skrip TIDAK menjalankan seeder (`CourierRuleSeeder` truncate) — precheck cuma pastikan `courier_rules` & `ProductVariant` terisi, kalau kosong minta seed manual.
  - Packaging/split terverifikasi dari delta stok relatif dalam run: BOX/LAP −4 (CBC-102,104,202,301), KDF+1.25 −1 (split KBJ CBC-104 qty2 → KBJ −1 + KDF −1).
- **Trap**: KBJ qty2 → KBJ −**1** (bukan −2) karena `ceil(2/2)`; awalnya assert −2 → salah, diperbaiki.
- **Verifikasi**: `generate_test_kit.php` + `verify_pipeline.php` 98/98 PASS (2 run berurutan, deterministik). AGENTS.md seksi H ditulis.

## Session: Upload Status Aggregator → awb/aggregator_status/delivered_at + stok return

- **Fitur baru G** (`AggregatorTrackingImportService`): admin upload file dashboard FLIK/SiCepat/SPX (`.csv`/`.xlsx`, PhpSpreadsheet `IOFactory`) di halaman Data Mentah → isi `shipping_orders.awb`, `aggregator_status`, `delivered_at`. Route `POST /orders/tracking-import` (`orders.tracking-import`), kartu "Upload Status Aggregator" di `order/index.blade.php`, `OrderOnlineController::trackingImport`.
- **Pencocokan WAJIB signature** (file TIDAK memuat order_id: SICEPAT "Nomor Referensi" 0/1902, SPX "Customer Reference" 0/673, FLIK Order ID = UUID): Tier 1 `phone_normalized + product_id + quantity + alamat`; Tier 2 (fallback, hanya bila unik) `phone_normalized + product_id + quantity`. 0 kandidat → `unmatched`, >1 → `ambiguous`. Batch `whereIn('phone_normalized')` + `ProductNameMatcher` (anti N+1).
- **`aggregator_status` 6 nilai INGGRIS** (`ShippingOrder::TRACKING_STATUSES`): `waiting_pickup` (FLIK Dikonfirmasi / SICEPAT Menunggu pickup / SPX Pending Pickup), `in_transit` (Sedang Diantar / Proses pengiriman / In Transit+Delivering), `delivered` (Dicairkan+Terkirim / Terkirim / Delivered), `returning` (Dalam Transit Pengembalian / Proses retur / Returning), `returned` (**Dikembalikan** / Retur / Returned), `problem` (FLIK: Dikonfirmasi/Sedang Diantar + "Status Terakhir dari 3PL" diawali "Problem" — cuma 3 baris; SICEPAT: Bermasalah; SPX: Pending Pickup/In Transit/Delivering + "Delivery OnHold Reason" berisi — 63 baris). Raw tak dikenal → null.
- **`delivered_at` hanya saat `delivered`**, dari kolom waktu file per sumber (parse per-source agar d/m SICEPAT tak tertukar m/d FLIK): FLIK `Terakhir Update` (`m/d/Y H:i`), SICEPAT `Tanggal Terkirim` (`d/m/Y H:i:s`), SPX `Delivered Time` (`d-m-Y H:i`). Status keluar dari delivered → nilai lama dibiarkan.
- **Stok return**: saat `aggregator_status` jadi `returned` → `StockService::reverseReference('order_online', $order->id)` (stok yang keluar saat export dikembalikan). Idempotent: hanya saat transisi (sebelumnya bukan returned), re-import tidak menggandakan (test `import returned is idempotent for stock`).
- **Detail teknis**: header xlsx bisa di baris 3 (SPX) → `detectSource` scan 8 baris pertama; sel kosong xlsx = `null` → `normalizeHeader(?string)`; `cellText()` tahan float/eksponensial (`2.63E17` → diperluas), `-` dianggap kosong. Kolom sudah ada di migration `2026_08_07_120000` → tanpa migrasi.
- **Test** (`AggregatorTrackingImportTest`, 9 test): mapStatus semua sumber + problem, import FLIK/SICEPAT/SPX (awb/status/delivered_at), returned balikin stok, idempotent, tier-2 fallback, ambiguous, unmatched. Pakai DB `awannacoba` tanpa refresh → jangan assert global count (AWB '999515101688' & status lintas-test terkontaminasi), scope ke order spesifik.
- **Verifikasi real file**: `training/flix.xlsx` (2404) → in_transit 1121, waiting_pickup 730, delivered 523, returning 24, returned 3, problem 3; `training/SICEPAT.xlsx` (1902) → delivered 1047, in_transit 329, waiting_pickup 221, returning 137, returned 32, problem 136; `training/SPX.xlsx` (673) → in_transit 321, waiting_pickup 171, delivered 109, returning 8, returned 1, problem 63. Semua cocok analisis.
- **Status**: full suite 48 pass + ExampleTest pre-existing fail (302 redir login); pint pass utk file baru.

## Session: FLIK 1 kolom HP (62), nama kacamata +power, rules sicepat

- **FLIK export — 1 kolom HP saja**: kolom kedua `No HP Pelanggan (mulai dengan "8")` DIHAPUS (header + value `phoneWithoutCountryCode()` dihapus). Kini hanya `No HP Pelanggan (mulai dengan "62")` = `phone_normalized`. Indeks kolom FLIK bergeser −1 (Catatan Kurir idx 9, Panjang/Lebar/Tinggi 11/12/13, Nama Produk 15).
- **Nama produk kacamata +power** (`OrderTemplateExportService::productDisplayName($o)`): untuk produk KMP/KSP/KBJ (cek `explode('+', product_code)[0]` di `StockService::KACAMATA_CODES`) ditulis `<nama> +<power> <qty> pcs` (contoh `Kacamata +1.50 2 pcs`). Power dari `product_variants.power` via eager-load `with('variant')` di `download()` (anti N+1), format `sprintf('+%.2f', ...)`. Suffix ` N pcs` lama di-strip dulu (`preg_replace('/\s+\d+\s*pcs$/i', '')`) agar tidak dobel. Produk non-kacamata → `product_name` apa adanya (cast `(string)` karena bisa null). Dipakai di kolom Nama Produk (FLIK idx 15), Isi Paket (SiCepat idx 10), Nama Barang (SPX idx 16).
- **Rules courier flix-sicepat → sicepat** (10 Agustus):
  - `CourierRuleSeeder` section 3 (COD BANTEN/DKI/JABAR/JATENG/JATIM/YOGYA/BALI) → `courier = 'sicepat'` (bukan `flix-sicepat`). Sudah di-re-seed di DB (verifikasi tinker: JAWA BARAT+cod → `sicepat`).
  - `CourierRuleService::COURIERS` + `'sicepat'` (untuk validasi `orders.update` + dropdown edit).
  - `flix-sicepat` tetap ada di `FLIK_COURIERS` + COURIERS sebagai override manual bila SiCepat bermasalah (TIDAK lagi dipilih otomatis).
  - Badge baru `.cou-sicepat` (hijau tua) di `order/index.blade.php`.
  - Generator training `make_test_rules_csv.php`: label `EXP` RULES-001 & RULES-008 → `sicepat`; CSV `TestRules_OrderOnline.csv` di-regenerasi.
- **Test (OrderOnlineTest 35→38)**: `test_courier_rule_resolution` `resolve('cod','BALI')` → `'sicepat'`; indeks FLIK di `test_export_dimensions_and_courier_note_per_template` disesuaikan ([9]/[11]/[12]/[13]); baru: `flik export single phone 62 only` (header 1 kolom HP, `6281234567890` di [1][2]), `export kacamata product name with power` (`Kacamata +1.50 2 pcs`, pakai variant `KMP+1.5` — seeder menghasilkan code tanpa nol di belakang, bukan `KMP+1.50`), `export non kacamata name unchanged`.
- **Penting**: variant code dari `ProductSeeder` = `rtrim(rtrim(number_format($power,2),'0'),'.')` → `KMP+1.5`, TAPI display power tetap `+1.50` via sprintf.
- **Verifikasi**: `OrderOnlineTest` 38/38 pass (174 assertion); full test hanya `ExampleTest` 302 pre-existing; pint passed (fix single_quote di `make_test_rules_csv.php`).

# MEMORY — 09 Agustus 2026

## Session: Re-import data sama → hard-delete `belum_diproses` lama + anti-double-export

- **Keputusan user**: saat data yang sama datang lagi berstatus `processing` (real) setelah sebelumnya `pending` (belum_diproses), baris lama **di-hapus permanen** (ShippingOrder tanpa SoftDeletes, `stock_movements.reference_id` bukan FK → tidak ada penghalang) + **anti-double-export**.
- **Penyesuaian 12 Agustus (opsi A)**: Rule re-import/update status cukup memakai `order_id` (order yang sama = order_id sama); baris berstatus `duplikat` TIDAK ikut dihapus (`stale` hanya `where('status','belum_diproses')`) karena duplikat adalah order yang BERBEDA (order_id sendiri, by signature phone+produk+alamat). Konsekuensi: order_id yang tadinya duplikat lalu naik real → 2 baris hidup berdampingan (duplikat + real), aman karena duplikat tidak exportable.
- **Perilaku baru `OrderOnlineImportService::import()`** (di dalam `DB::transaction`):
  1. Query batch global `$byOrderId = ShippingOrder::whereIn('order_id', ...)->get()->groupBy('order_id')` (1 query, anti N+1).
  2. Untuk baris berstatus `real`/`tembakan`: hapus baris lama dengan `order_id` sama berstatus `belum_diproses`/`duplikat` (`ShippingOrder::whereKey(...)->delete()`, counter `deleted`); `cancel` & `real` lama TIDAK dihapus/ditimpa.
  3. Anti-double-export: jika `real`/`tembakan` dengan `order_id` sama SUDAH ada di batch lain → jangan insert (counter `double_real`, `continue`). Catatan: koleksi `$same` tidak bermutasi oleh delete, jadi baris lama yang baru dihapus tidak memicu `continue`.
  4. Statistik batch: `success_rows = inserted + updated + duplicates`, `failed_rows = double_real`. Return bertambah `deleted` & `double_real`.
- **Controller** `store()`: pesan flash tambah `| Baris belum diproses lama dihapus: N` dan `| Real di-skip (sudah ada): N`.
- **Test baru (OrderOnlineTest 33→35)**: `reimport real deletes old belum diproses` (pending→belum_diproses, re-import processing → deleted=1, inserted=1, DB sisa 1 baris real), `reimport real does not create double real` (processing 2× → double_real=1, inserted=0, baris real tetap 1).
- **Verifikasi**: `OrderOnlineTest` 35/35 pass (169 assertion); pint passed; full test hanya `ExampleTest` 302 pre-existing.

## Session: Produk BOX/LAP/KDF + Pengurangan stok otomatis (kemasan & split KBJ)

- **Aturan (dikonfirmasi user)**:
  - BOX ('Box Kacamata') & LAP ('Lap Pembersih') = aksesoris kemasan, non-sized (1 varian default), stok awal 1000. KDF ('Kacamata Double Fokus') = pendamping KBJ, sized (9 power), stok 1000 dibagi rata.
  - Pengurangan otomatis saat stok KMP/KSP/KBJ berkurang (alur export order-online `reserveStock` DAN alur import shipment ber-resi `ShipmentImportService`): **BOX + LAP = floor(qty/2)** tiap qty≥2. **KBJ selalu split**: KBJ = ceil(qty/2), KDF = floor(qty/2) — KDF varian ber-power SAMA dengan varian KBJ (fallback power terkecil). KDF tidak memicu saat terjual sendiri.
  - Pembulatan BOX/LAP = floor (qty 1 → 0; qty 3 → 1+1; qty 4 → 2+2). KBJ/KDF = ceil/floor (qty 2 → 1+1; qty 3 → 2+1).
  - Split KBJ/KDF & BOX/LAP HANYA memengaruhi stok/jurnal — isi template export tetap 1 baris qty asli.
- **Migration `2026_08_09_100000_extend_stock_movements_unique_for_packaging`**: unique `stock_movements_ref_unique` dari `(reference, reference_id, type)` menjadi `(reference, reference_id, type, product_variant_id)` — memungkinkan 1 order/shipment punya beberapa baris `out` (kacamata + BOX + LAP [+KDF]).
- **`StockService`**:
  - `recordIn` & `recordOut`: key `updateOrCreate` kini menyertakan `product_variant_id` (WAJIB — kalau tidak, recordOut berikutnya untuk ref yang sama menimpa varian baris sebelumnya: BOX menimpa KMP, LAP menimpa BOX → transaksi rollback, tidak ada jurnal sama sekali).
  - Anti silent-reassign `recordOut` di-scope per produk: `whereHas('variant', product_id == target)` + `!= variantId` — membersihkan varian lama produk yang sama, TANPA menyentuh movement pendamping (BOX/LAP/KDF beda produk).
  - **`recordOutWithPackaging(variantId, date, qty, ref, refId, note, createdBy)`** — konstanta `KACAMATA_CODES=['KMP','KSP','KBJ']`, `PACK_*_CODE`, `PACK_QTY_PER=2`. Non-kacamata → delegasi `recordOut`. Kacamata → 1 transaksi: main (+kdf bila KBJ) + box + lap. Produk pendamping belum terdaftar / stok kurang → `RuntimeException` → pemanggil tangani (export → `stock_note` + order dilewati; import shipment → batch gagal, perilaku konsisten dgn stok produk utama kurang). Semua rollback atomik.
  - `packagingVariants()` di-cache per instance (1 query BOX+LAP default, 1 query seluruh varian KDF) — anti N+1 di loop export/import.
- **Pemanggil diubah**: `OrderTemplateExportService::reserveStock` & 2 call site `ShipmentImportService::import` → `recordOutWithPackaging`. `reverseReference` (undeliverable / ganti produk) otomatis membalik SEMUA movement pendamping (delete semua + recalc tiap varian).
- **`ProductSeeder`**: `SIZED_PRODUCTS` + `KDF`; produk BOX (id 80), LAP (81), KDF (79) ditambah (firstOrCreate, opening stock via `recordIn('adjustment')`). Catatan: `powerList()` menghasilkan **9** power (1.00..3.00 step 0.25), bukan 10 → sized product total stok 999 (9×111).
- **Test baru (OrderOnlineTest 27→33)**: `export kacamata reduces box lap` (idempotent double-export, count movement=3), `export kbj splits to kdf and packaging` (KBJ+1.25 → KDF+1.25), `export kbj qty one skips packaging` (qty1 → hanya KBJ −1), `undeliverable restores packaging stock`, `export skips when box unregistered` (BOX inactive → stock_note + KMP rollback), `shipment import reduces box lap`. Helper `ensureCatalog()` = `$this->seed(ProductSeeder::class)` (idempotent) karena DB shared & `products.code` unique; asersi pakai delta `stockOf` (bukan nilai absolut).
- **Verifikasi**: `OrderOnlineTest` 33/33 pass (158 assertion); full test hanya `ExampleTest` 302 pre-existing; pint passed (revert file debug root yang ter-format pint).

## Session: Fix halaman Products (kolom varian code/name) + Toggle status Produk & Varian

- **Bug tampilan varian**: kolom DB `product_variants` bernama `code`/`name`, tapi view & controller pakai `kode`/`nama` → kode varian tampil kosong (atribut tidak ada). `ProductVariant::$fillable` sudah `code`/`name`, jadi `variantStore/update` yang validasi `kode`/`nama` → `$data['kode']`/`['nama']` silent-drop saat `create()`/`update()` → tambah/edit varian via modal GAGAL (DB `code` NOT NULL).
- **Fix**: `ProductController::variantStore/variantUpdate` validasi `'code'`/`'name'` (`unique:product_variants,code`); `resources/views/product/index.blade.php` tampilan varian `$v->code`/`$v->name`, `data-kode`→`data-code`, `data-nama`→`data-name`, JS `btn.dataset.code/name`, body fetch `code`/`name`.
- **Toggle status langsung** (produk & varian):
  - Routes baru: `PATCH product/{product}/toggle-status` (`product.toggle-status`), `PATCH product/variant/{variant}/toggle-status` (`product.variant.toggle-status`).
  - Controller: `toggleStatus(Product)` & `toggleVariantStatus(ProductVariant)` — flip `active`↔`inactive`, balas JSON `{success, status}`.
  - View: kolom Status baru di tabel induk + toggle switch `.clay-toggle` (CSS inline di `@push('styles')`); di baris varian badge status diganti toggle kecil (`.clay-toggle-sm`). JS: onchange → `fetch(PATCH toggle-status)` → reload. Aksi edit/hapus di stopPropagation agar tidak ter-expand.
  - colspan tabel induk 7→8, align header Status varian right.
- **Verifikasi**: pint passed; `view:cache` OK; `route:list --name=product` 11 routes (2 toggle baru ada); tinker flip produk 1 & varian 19 (KBJ+1) berhasil lalu di-restore; `OrderOnlineTest` 27/27 pass (128 assertion); full test hanya `ExampleTest` 302 pre-existing.

## Session: Parser product + Dapat (angka) + product_code = kode varian + Dimensi/Nota Kurir Export

- **Parser kolom `product` (CSV)**: hanya diambil sampai spasi sebelum `-`; angka setelah `-` disimpan ke kolom baru `shipping_orders.meta_account` (migration `2026_08_09_000000`, string nullable, hanya DB — tidak tampil di UI). Tanpa `-` → product_name = teks penuh, meta_account = null.
- **`Dapat N` dari variasi** (regex `/Dapat\s*(\d+)/i`, contoh `"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000"`): override qty (menang atas kolom CSV `quantity`) + `product_name` jadi `"{nama} {N} pcs"` (contoh `A.3 Kacamata Multifokus Photocromic 2 pcs`). Tanpa Dapat → qty dari CSV (default 1), nama polos. Distribusi data: Dapat 2 (5419 baris), Dapat 4 (128), tanpa Dapat (322).
- **`product_code` kini berisi KODE VARIAN** (`product_variants.code`, mis. `KMP+1.50`), bukan kode produk master. Set di `OrderOnlineImportService::import()` setelah `resolveVariant()` via `$variantIndex[product_id]->firstWhere('id', variant_id)`.
- **Dampak kaskade**:
  - `orderSignature()` dinormalisasi: `explode('+', product_code)[0]` → deteksi duplikat tetap memakai kode master (data baru=varian, data lama=master, konsisten).
  - `OrderTemplateExportService::warehouseFor()`: `explode('+', $code)[0]` sebelum lookup `WAREHOUSE_BY_PRODUCT` (KSP+1.50→GTM).
  - `OrderOnlineController::update()` (ganti produk): resolve via `ProductVariant::where('code', ...)`, set `product_id/product_variant_id/product_code` dari varian; `product_name` = `product->name` + `" N pcs"` bila qty>1. Logika `reverseReference`/`productChanged` tetap.
  - View edit dropdown `product_code` iterate `$products` → nested `$p->variants` (`value=$v->code`).
- **Export dimensi & catatan kurir**: konstanta baru `PACK_DIMENSIONS=[10,8,6]` & `DEFAULT_COURIER_NOTE='HUBUNGI KONSUMEN SEBELUM DIKIRIM'`. Panjang/Lebar/Tinggi=10/8/6 di kolom masing-masing template (FLIK idx 12/13/14, SiCepat 12/13/14, SPX 13/14/15). Catatan kurir = `$o->courier_note ?: DEFAULT_COURIER_NOTE` di kolom FLIK `Alamat: Catatan Kurir` (idx 10), SiCepat `Catatan Pengiriman` (idx 20), SPX `Instruksi Pengiriman` (idx 21). Volume TIDAK dijumlahkan (tiap template punya kolom sendiri; `Berat` tetap `weight`).
- **Test baru (OrderOnlineTest, total 27)**: `product meta account split`, `dapat qty override and product name`, `product code stores variant code`, `warehouse for variant code`, `export dimensions and courier note per template`. Asersi sel PhpSpreadsheet pakai `(int)` cast (nilai balik string).
- **Verifikasi**: `OrderOnlineTest` 27/27 pass (128 assertion); full test hanya `ExampleTest` 302 pre-existing; pint passed pada file yang diubah.

## Session (sebelumnya): Fix stok tidak kembali saat undeliverable dengan varian non-default

- **Bug dilaporkan**: export `reserveStock()` kurangi stok; ubah courier → `undeliverable` ⇒ stok tidak kembali. Test lama `test_undeliverable_returns_reserved_stock` LULUS (varian tunggal default) tapi data asli (order id=4, KSP, variasi `+1.75`) gagal: varian 13 (KSP+1.75) stock=110 vs journal=111 — satu-satunya mismatch di DB; movement id 35 (out order 4) dihapus.
- **Akar masalah (3 cacat)**:
  1. `OrderOnlineController::update()` menimpa `product_variant_id` ke `defaultVariant()` SETIAP edit (form `product_code` selalu terkirim) → varian 13 (KSP+1.75) berubah jadi 10 (KSP+1) tanpa disadari.
  2. `StockService::recordOut()` pakai `updateOrCreate` key `(reference, reference_id, type)` → re-export setelah varian berubah memindahkan jurnal `out` lama diam-diam ke varian baru; stok varian lama (13) tetap terpotong tapi jurnal sudah tak merujuk.
  3. `reverseReference()` hanya mengembalikan varian yang tercatat di jurnal saat itu → yang dikembalikan varian 10 (→111), varian 13 mangkrak 110.
- **Fix**:
  - `update()`: hanya re-resolve `product_variant_id` jika `product_code` benar-benar berubah (`trim` + bandingkan); jika berubah dan sudah ada jurnal `out` `order_online` → `reverseReference()` DULU (stok varian lama kembali) sebelum ganti varian. Edit courier-only TIDAK menyentuh varian.
  - `recordOut()`: sebelum `updateOrCreate`, cek jurnal `out` existing dengan `product_variant_id` berbeda → hapus + `recalculateStock(varian lama)` (kembalikan stok) baru catat ke varian baru.
- **Test baru (OrderOnlineTest)**:
  - `undeliverable restores non default variant and keeps variant` — varian non-default +1.75, export (100→97), PUT `orders.update` dgn `courier=undeliverable` + `product_code` (meniru form asli) → varian tetap non-default, stok kembali 100, jurnal out 0.
  - `courier edit with same product code keeps variant` — ganti courier dgn product_code sama → varian TIDAK berubah.
  - `product code change with existing reservation restores old variant stock` — export (stok turun), ganti product_code ke produk lain → stok varian lama kembali 100, jurnal out 0, varian order = default produk baru.
  - **Penting di test**: `recordIn(..., 'adjustment')` harus diberi `reference_id` unik (mis. `$variant->id`), bukan `null` — `updateOrCreate` key `(reference, reference_id, type)` membuat semua varian saling menimpa bila `null`. Variant `code` juga harus unik (`$product->code.'+1.75'`).
- **Perbaikan data live**: `recalculateStock(13)` → varian 13 stock 111 = journal. Semua varian produksi (1–30) konsisten; mismatch tersisa (id ≥ 31) = artefak "Produk Test" dari test di DB shared.
- `OrderOnlineTest` kini **23/23 pass (104 assertion)**; full `php artisan test`: hanya `ExampleTest` 302 pre-existing. Pint passed.

# MEMORY — 08 Agustus 2026

## Session (lanjutan): Skema Varian Produk + Stok per Varian + Inventory (gudangs → inventories)

- **Skema inti**: `products` master saja (nama, kode, harga, inventory_id). `stok`, `supplier_id`, `ukuran` DIBUANG dari `products`. Stok & ukuran (`power`) pindah ke `product_variants`. `gudangs` → `inventories` (git-mv migration ke `2026_07_06_154252`, dibuat sebelum products). Jurnal `stock_movements` & `purchases` kini PER VARIAN (`product_variant_id`, FK cascade).
- **`product_variants`**: `product_id`, `power` decimal(5,2) (1.00–3.00 step 0.25), `name`, `jenis` nullable, `stock` (fisik, dijaga StockService), `hpp`, index `(product_id,power)`. `product_variant_items` = BOM optional.
- **Varian**: produk berukuran `SIZED_PRODUCTS=['KBJ','KMP','KSP']` → 10 varian `+1.00`…`+3.00`; tanpa ukuran (KCHP, SH, KNGH) → 1 varian default (`power` 0). `Product::defaultVariant()` = varian aktif power terkecil. Kode varian `{product.code}+{power}`.
- **CSV `variation` → varian**: `OrderOnlineImportService::extractPower()` regex `Plus\s*([+\-]?\d+(?:\.\d+)?)` dari string `"Ukuran: Usia 40-42 Tahun Plus +1.00, Warna: Grey Elegant"` → `resolveVariant()` (exact power, fallback default) → `shipping_orders.product_variant_id` (FK nullable + index). Warna diabaikan.
- **`StockService` per varian**: `recordIn/recordOut/reverseReference/recalculateStock/stockOf/hppRataRata/recalculateHpp/recalculateAll`; `recordOut` tolak overdraw (`\RuntimeException`). Pemanggil: `PurchaseController::store/destroy`, `OrderTemplateExportService::reserveStock` (`$order->product_variant_id`), `ShipmentImportService` (`defaultVariant()`), `ProductController::variantStore` (`stock_awal`).
- **`Inventory`** (dari `Gudang`): model+controller baru, routes `inventory.master*`, view `inventory/master.blade.php` (git-mv). `ProductController` create/edit pakai `inventories`; `variantStore` modal (`product.variant.store|update|destroy`).
- **Seeders**: `InventorySeeder` baru (`Gudang Pusat`, `GTM`, `Aurora`) dipanggil urutan #2 di `DatabaseSeeder`; `ProductSeeder::seedVariants()` + opening stock `recordIn('adjustment')` per variant.
- **Fixes penting**: FK `stock_movements→product_variants` memaksa urutan migration (inventories→products→variants→stock_movements) — git-mv `2026_08_05_10xxxx` → `2026_08_03_090000/090001`; `jenis` → nullable (semula NOT NULL → gagal migrate:fresh). `Product` model: buang `whitelists()/purchases()` (relasi rusak, tak terpakai); `Supplier`: buang `whitelists()/spendingHarians()`. Route product `except('show')`.
- **Verifikasi**: `migrate:fresh --seed` sukses; `OrderOnlineTest` 19/19 pass (86 assertion); full test 20 pass + 1 fail pre-existing (`ExampleTest` 302); pint passed; semua halaman render 200 (product/inventory/purchase/stock-movement/orders/supplier/spending).
- AGENTS.md diperbarui (section E: skema varian + inventory).

# MEMORY — 07 Agustus 2026

## Session (lanjutan): Kode Warehouse otomatis + Split ZIP per Gudang + Aturan SPX + Konsolidasi Migration

- **Migration dikonsolidasi**: 8 file `2026_08_07_100000…100007` dihapus → **1 file** `2026_08_07_120000_create_order_online_schema.php` (order_online_import_batches + sender, aggregator_sync_batches, courier_rules, shipping_orders final: status enum `real/tembakan/belum_diproses/cancel/duplikat`, product_code/product_id/stock_note, semua index + unique `(batch_id,order_id)`). Enum `stock_movements.reference` di-merge langsung ke base `2026_08_03_100000` (`['purchase','shipment','adjustment','order_online']`). `migrate:fresh --seed` sukses.
- **Kode Warehouse** (`OrderTemplateExportService::warehouseFor(product_code, sender)`): `KSP`→`GTM`, `SH`→`Aurora`, produk lain→`sender`. Kolom "Kode Warehouse" FLIK diisi per-baris.
- **Split per gudang**: export dikelompokkan per `warehouseFor`; 1 gudang → .xlsx langsung, ≥2 gudang → **1 ZIP** (`ZipArchive`) berisi file per gudang. Nama file `Ymd_<template>[_<courier>]_<warehouse>_<batch>.xlsx`; ZIP `Ymd_<template>[_<courier>]_<batch>.zip`.
- **SPX biasa**: `phoneSpx()` → mulai `8` (hapus `0/62/+62`); province/city/subdistrict **CAPSLOCK** (hanya SPX); nama file menyertakan sender (`Ymd_spx_<sender>_<batch>.xlsx`).
- **Tembakan → spx**: `OrderOnlineImportService::import()` — status `tembakan` SELALU courier `spx` (terlepas provinsi/payment); `real` → courier_rules.
- **Trap ZipArchive**: `addFile()` membaca file sumber saat `close()`, `@unlink` sebelum close → "Can't open file". Pakai `addFromString(file_get_contents())`.
- `OrderOnlineTest` kini **19/19 pass (83 assertion)**; full `php artisan test`: hanya `ExampleTest` 302 pre-existing. Pint passed; `view:cache` OK; `route:list --name=orders` 5 routes.
- AGENTS.md diperbarui (section D: warehouse mapping, split ZIP, phoneSpx/CAPSLOCK SPX, tembakan→spx, migration final).

## Session (lanjutan): Enum status baru + Deteksi Duplikat 14 hari + Fix Export Kosong

- Migrasi `2026_08_07_100007_change_status_enum_to_new_statuses_table.php` dijalankan di DB `awannacoba`: `status` → enum `real/tembakan/belum_diproses/cancel/duplikat`. Percobaan pertama gagal (`Data truncated` — enum lama tak terima `belum_diproses` saat UPDATE) → fix urutan up: string → `UPDATE tidak_diproses→belum_diproses` → enum baru.
- `OrderOnlineImportService`: mapping status baru (processing→real, pending+paid→tembakan, pending+unpaid→belum_diproses, cancelled→cancel, **completed→di-skip saat parse**, masuk `skips`/`skipped`, tidak disimpan); `courier` hanya diisi utk real/tembakan, selain itu `null`.
- Deteksi duplikat HANYA untuk `belum_diproses`: signature `phone_normalized|product_code|normalizeAddress` (`orderSignature()`); window `DUP_WINDOW_DAYS=14`; `loadDuplicateSignatures()` 1 query batch `whereIn` phone + `created_at>=now()-14d`; ≤14d → `duplikat` (courier null), >14d → repeat order; duplikat dalam 1 file juga tertangkap (set saat loop); real/tembakan TIDAK pernah duplikat (promosi) & TIDAK menambah signature (bug fixed: dulu phone kosong mencemari set).
- **Bug export kosong (hanya header) sudah diperbaiki**: `ProductSeeder` sekarang membuat opening stock via `StockService::recordIn(..., 'adjustment', $p->id, 'Stok awal (seeder)')`. Penting: `recordIn` pakai `updateOrCreate` dengan `reference_id` unik per produk — bila `null`, semua produk menimpa baris yang sama (`whereNull`). Hasil: 6 movements, `stockOf()`==`stok` semua produk → export berisi data (flix-tf 4 rows, flix-spx 2 rows).
- `ShippingOrder::STATUSES` ditambahkan; view: badge `st-belum_diproses`/`st-cancel`/`st-duplikat`, filter pakai STATUSES, label status `str_replace('_',' ', ...)`; controller store menampilkan jumlah duplikat.
- `OrderOnlineTest` **12/12 pass (47 assertion)**: mapping status (completed skip, courier null utk non-exportable), duplikat (≤14d / same file / repeat order >14d / promosi real-tembakan), resolve product_id, render, FLIK separated by courier+status, stok idempotent, skip stok kurang. `php artisan test` penuh: 13 pass + 1 fail pre-existing (`ExampleTest` 302).
- Pint passed; `view:cache` OK; `route:list --name=orders` 5 routes OK.
- **Login HTTP terverifikasi**: hash `owner@awanna.id` cocok dgn password `password` (Hash::check=true; `admin123`/`awanna123` salah) → curl login OK → `/orders` 200, badge status (real/tembakan/belum_diproses/cancel/duplikat) tampil, export `orders/74/export/flik/flix-tf` HTTP 200 .xlsx valid. Batch 74: order `real` qty5 stok2 → di-skip (`stock_note=Stok tidak mencukupi`) → file header-only, perilaku benar (order stok cukup sudah dibuktikan di verifikasi service: flix-tf 4 rows).

## Session (lanjutan): Fix stok kembali saat courier → undeliverable

- Bug: export `reserveStock()` membuat jurnal `out` (`reference=order_online`, `reference_id=order->id`) → saat courier diubah jadi `undeliverable`, stok TIDAK kembali (barang tidak jadi dikirim).
- Fix: `OrderOnlineController::update()` kini inject `StockService`; saat courier berubah menjadi `undeliverable` (dari courier lain) → `StockService::reverseReference('order_online', $order->id)` hapus jurnal `out` + `recalculateStock` → stok kembali. Ubah dari `undeliverable` ke courier normal TIDAK menambah stok (export yang reserve ulang via `recordOut` idempotent).
- `OrderOnlineTest` kini **14/14 pass (59 assertion)**: + `undeliverable returns reserved stock` (97→100, jurnal out 1→0), + `undeliverable to normal courier does not double stock`. Pint passed.
- AGENTS.md diperbarui (section D: stok balik saat undeliverable).

## Session (lanjutan): Enum status baru + Deteksi Duplikat 14 hari + Fix Export Kosong
- AGENTS.md diperbarui (section D: enum baru, completed skip, duplikat 14 hari, courier null, opening stock).

## Session: Data Mentah Order Online + Courier Rules + Export Template Excel

- 4 migrasi baru: `order_online_import_batches`, `aggregator_sync_batches`, `shipping_orders`, `courier_rules` (di DB `awannacoba`)
- Dibersihkan tabel & record migrasi stale dari percobaan sebelumnya (`import_order_online_baches` typo, `shipping_order_status_histories`, migrasi batch 2 yang filenya sudah dihapus) — tabel kosong, aman
- `CourierRuleSeeder`: 35 rules (flix-tf BT + flix-idx 7 + flix-sicepat 7 + flix-spx 20); `resolve()` fallback `spx`
- Test verifikasi:
  - Import file mentah `training/DataDariOrderOnline(mentah).csv`: 5869 orders, 0 skip, 0 CS tak dikenal, `order_online_contacts` terisi 5670 (dedupe phone)
  - Distribusi courier: flix-tf 672 / flix-idx 1154 / flix-sicepat 2279 / flix-spx 1764 (tidak ada `spx` murni karena semua provinsi ter-cover)
  - `handled_by_user_id` terisi 100% (5869/5869)
  - Export FLIK/SiCepat/SPX .xlsx valid (PhpSpreadsheet), kolom sesuai template
  - Feature test `OrderOnlineTest` 2/2 pass; `ExampleTest` gagal pre-existing (route `/` redirect)
- Pint hanya pada file baru; file tak terkait di-revert; `view:cache` OK; `route:list` OK (orders.*)
- AGENTS.md & MEMORY.md diperbarui (section D)

## Session (lanjutan): Status enum + Sender + Stok via product_code

- Data uji dibersihkan (5872 shipping_orders / 5670 contacts / 4 batch); training `RESI FLIK/SICEPAT/SPX JULI.csv` dipulihkan via `git restore`
- 3 migrasi baru dijalankan di DB `awannacoba`:
  - `2026_08_07_100004_change_status_enum_on_shipping_orders_table` — `status` → enum `real/tembakan/tidak_diproses`
  - `2026_08_07_100005_add_product_tracking_to_shipping_orders_table` — `product_code`, `product_id` FK, `stock_note`
  - `2026_08_07_100006_add_order_online_to_stock_movements_reference` — `reference` enum + `order_online`
- Mapping status: `processing`→`real`, `pending`+`paid`→`tembakan`, lainnya→`tidak_diproses`; export hanya `EXPORTABLE_STATUSES` (real+tembakan)
- Stok: import resolve `product_id` exact-match `product_code`; saat export `reserveStock()` → `StockService::recordOut('order_online', order->id)` idempotent; stok kurang/produk tak ada → order di-skip + `stock_note`
- `sender` wajib saat upload → kolom "Kode Warehouse" template FLIK; FLIK export dipisah per courier (`orders/{batch}/export/flik/{courier}`)
- `ProductSeeder` ditulis ulang sesuai kode CSV (KMP/KSP/KBJ/KCHP/SH/KNGH); di DB `KMPU→KMP`, `SNDR→SH` (7 produk total)
- `OrderOnlineTest` kini 7/7 pass (courier resolve, status mapping, resolve product_id, render, FLIK separated by courier+status, stok idempotent, skip stok kurang); `ExampleTest` 302 pre-existing
- Pint fixed (controller/service/test); `view:cache` OK; `route:list` OK (orders.* 5 routes)
- AGENTS.md diperbarui (section D: enum status + stok via product_code + sender)

## MEMORY — 03 Agustus 2026

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
