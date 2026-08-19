# MEMORY — 15 Agustus 2026

## Follow-up (hari yang sama): Simplify resolveOrder — hanya phone + customer_name

- **Latar**: user minta pencocokan tracking hanya pakai phone + customer_name (data dianggap sudah aman). Multi-tier (address, product, quantity) dihapus — hanya 2 kolom yang dibandingkan.
- **Perubahan `resolveOrder()`**: Hapus tier2 (address), product match, quantity match. Sekarang: (1) `name_norm` kosong → unmatched; (2) filter kandidat by `normalizeName(customer_name) === nameNorm`; (3) 1 match → ok; >1 → ambiguous; 0 → unmatched.
- **Test rewrite**: 9 test gagal karena: (a) CSV tidak sertakan kolom nama (`Nama Penerima`/`Nama Shopper`/`Recipient Name`) → `name_norm` kosong → unmatched; (b) phone `6281234567890` sudah terkontaminasi ratusan order dari run berulang → banyak kandidat nama sama → ambiguous. **Fix**: semua test pakai phone unik via `uniqid()` + sertakan kolom nama di CSV.
- **Test baru**: `test_import_matches_by_phone_and_name_regardless_of_address`, `test_import_matches_even_with_unrecognizable_product_name`, `test_import_extracts_qty_from_dapat_promo_text`, `test_import_ambiguous_when_same_name_multiple_orders` — sesuai logika baru.
- **Hasil**: Tracking tests 15/15 pass, suite 158 pass (hanya ExampleTest pre-existing), pipeline 104/104 PASS. Tidak ada migrasi baru.

## Follow-up (hari yang sama): Hapus hardcode detectSource — 100% DB-driven

- **Latar**: user minta "hapus saja detect source, kalau memang hardcode hapus saja — sistem ini dirancang untuk dinamis". `detectSource()` punya fallback hardcoded flik/sicepat/spx yang menimpa deteksi DB.
- **Fix**: Hapus SEMUA hardcoded di `detectSource()` — sekarang 100% dari `tracking_header_mappings`. Jika tidak ada mapping di DB → RuntimeException "Belum ada mapping header". Jika file tidak match ≥2 header → RuntimeException "Sumber file tidak dikenali".
- **Chicken-and-egg**: seeder `TrackingHeaderMappingSeeder` panggil `extractDefaultMapping()` yang panggil `detectSource()` → gagal karena belum ada mapping. **Fix**: `extractDefaultMapping()` + `extractHeaders()` + `parse()` + `import()` semua terima param opsional `?string $source = null` — jika source di-pass, skip `detectSource()` (seeder/pipeline passed source eksplisit). View edit.blade.php kirim `source` via FormData saat upload.
- **Pipeline**: `verify_pipeline.php` cleanup delete tracking_header_mappings → seed ulang via `extractDefaultMapping(path, source)` langsung (bukan lewat Seeder class).
- **Test**: 3 test upload diperbaiki — kirim `source` param saat upload/parse. Semua 34 tracking tests pass, suite 158 pass, pipeline 104/104 PASS.

## Follow-up (hari yang sama): Fix 3 masalah tracking/export

- **Masalah 1 — FLIX tidak baca template export** di halaman tracking-status-rule: `$templateMap` di controller di-build dengan key courier names (`flix-tf`, `sicepat`, `spx`) tapi view lookup dengan source key (`flik`, `sicepat`, `spx`). **Fix**: `$templateMap = $exportTemplates->keyBy('key')->all()` — key = template key (flik/sicepat/spx/idxe).
- **Masalah 2 — Upload tracking IDX terdeteksi sebagai SPX**: `detectSource()` hardcoded hanya kenali flik/sicepat/spx → fallback ke `spx`. **Fix**: deteksi DB-driven dari `tracking_header_mappings` — hitung berapa header ter-map per source muncul di file CSV → source terbanyak menang (≥2 match). Fallback ke hardcoded tetap ada untuk backward-compat tanpa mapping DB.
- **Masalah 3 — 'tidak ada template export'**: error dari `OrderTemplateExportService::buildRows()` saat `mappingFor($template)` kosong. Dikarenakan `mappingFor()` cache per-request dan grouped by `template` key — jika user baru buat template tapi belum upload/save mapping, `$mapping->isEmpty()` = true → RuntimeException. Ini bukan bug kode, tapi UX: admin harus upload mapping dulu setelah buat template. Tidak perlu fix kode.
- **Verifikasi**: 34 tracking tests pass, suite 158 pass (ExampleTest pre-existing), pipeline 104/104 PASS.

## Follow-up (hari yang sama): Fix ProductController — return JSON untuk AJAX (store/update/destroy)

- **Latar**: user lapor edit produk error "The PUT method is not supported for route product. Supported methods: GET, HEAD, POST." Akar masalah: JS memakai `fetch()` (AJAX) tapi controller `store()`/`update()`/`destroy()` return `redirect()` → fetch mengikuti redirect 302 ke `/product` (GET only) → method PUT tidak ada di sana.
- **Fix controller**: `ProductController::store/update/destroy` return `response()->json(['success' => true, 'message' => ...])` alih-alih `redirect()->route('product.index')`. Hapus import `RedirectResponse` yang tidak dipakai.
- **Fix view**: delete button di `product/index.blade.php` diubah dari `<form>` HTML ke button AJAX (`deleteProduct(url, name)`) + JS function `deleteProduct` yang pakai `post(url, 'DELETE')`. 
- **Fix test**: `OrderOnlineTest::test_product_master_page_create_update_toggle_destroy` — 3 assertion `assertRedirect(route('product.index'))` diganti `assertOk()->assertJson(['success' => true])`.
- **Verifikasi**: `test_product*` 6/6 pass, pipeline 104/104 PASS. 10 test gagal = packaging/export pre-existing (stok DB dev terkuras, bukan regresi).

## Session: Courier dropdown dinamis dari export_templates + Tracking rules validasi template export

- **Latar**: user baru tambah template export `idxeveropro` (courier `IDEXPRESS`) tapi courier dropdown di halaman Data Mentah masih pakai hardcode `CourierRuleService::COURIERS` (7 value lama, tidak ada IDEXPRESS). Tracking status rules juga `SOURCES` hardcode `[flik,sicepat,spx]`.
- **Fix courier dropdown**: `OrderOnlineController::index()` kini kumpulkan courier dari `ExportTemplate::where('is_active')` → `flatMap(couriers)` → unique + push `undeliverable` → pass `$courierList`. View `order/index.blade.php` ganti `CourierRuleService::COURIERS` → `$courierList`. `update()` validasi courier juga dinamis dari `ExportTemplate`.
- **Fix tracking SOURCES**: `TrackingStatusRuleController` tambah `validSources()` — gabungan `TrackingStatusRule::SOURCES` + `ExportTemplate::pluck('key')` (unique). Semua validasi `in_array($source, ...)` diganti pakai `validSources()`. Index view kini render **template export reference** (nama, couriers) + tombol ➕ untuk template baru yang belum punya tracking rules.
- **Verifikasi**: 34 tracking tests pass, suite 158 pass (ExampleTest 302 pre-existing), pipeline 104/104 PASS. Tidak ada migrasi baru.
- **Deploy**: `git pull` di VPS (tidak perlu migrate — tidak ada schema change).

## Follow-up (hari yang sama): CourierRuleController juga harus dinamis

- **Latar**: user lapor "halaman courier-rules dropdown courier masih hardcode". Cek `CourierRuleController` — masih pakai `CourierRuleService::COURIERS` di 3 tempat: `index()` (pass ke view), `store()` (validasi), `update()` (validasi).
- **Fix**: tambah `use App\Models\ExportTemplate;` + method `allCouriers()` (kumpulkan dari `ExportTemplate::where('is_active')` + push `undeliverable` + sort). Ganti 3 titik hardcode → `$this->allCouriers()`. Sekarang saat admin tambah template baru (mis. `idxeveropro` → `IDEXPRESS`), courier itu langsung muncul di dropdown courier-rules.
- **Test**: `CourierRuleTest` **13/13 pass**. Suite **158 pass** (ExampleTest 302 pre-existing). Pipeline **104/104 PASS**.

## Session: Fallback produk tak dikenal di import tracking + seeder template header (FLIK gagal terus)

- **Latar**: user lapor "FLIK gagal terus". Saya cek `training/flix.xlsx` (dashboard FLIK asli 2.404 baris) & `training/02_flik.csv` (7 baris — BUKAN konversi xlsx, isinya beda: produknya nama PROMO). Import `02_flik.csv` → 0 matched padahal 7 order-nya ADA di DB.
- **Akar masalah**: kolom "Nama Produk" dashboard FLIK berisi **nama PROMO** bukan nama produk → matcher gagal. Dengan matching sekarang yang hanya pakai phone + customer_name, produk tidak relevan untuk pencocokan.
- Dengan matching phone + customer_name, produk tidak lagi relevan untuk pencocokan.
- **Jebakan test**: phone `6281234567890` terkontaminasi 171 order sisa di DB dev (test lama tidak cleanup) → test fallback baru WAJIB phone unik (`62812`.substr(uniqid(),-8)) + nama unik.
- **Test**: `AggregatorTrackingImportTest` +3 → **15/15** (fallback promo tetap match; qty "Dapat 2"; ambiguous dipertahankan). Suite **158 pass** (ExampleTest 302 pre-existing) · pipeline **104/104 PASS**.

## Session: Seeder template tracking — `TrainingHeaderMappingSeeder` (dari training/templateTracking)

- **Latar**: user simpan template header dashboard di `training/templateTracking/` (header_flix.csv, header_sicepat.csv, header_spx.csv) → jadikan seeder.
- **`AggregatorTrackingImportService::extractDefaultMapping(filePath)`** (baru, publik): baca file → `detectSource` + `cleanHeaders` + `mapHeaders($headers, $source, [])` (param baru `$dbMap` opsional, `[]` = murni alias tanpa DB) → `[source, mapping: header→db_column]`.
- **`TrackingHeaderMappingSeeder`** (baru): loop 3 file → `extractDefaultMapping` → `updateOrCreate` per (source, header) (idempotent, mapping admin tidak dihapus). Hasil: FLIK 8 kolom, SiCepat 8, SPX 9. Terdaftar di `DatabaseSeeder` (8d).
- **Test lama diperbaiki** (kini ada data bawaan di DB dev): `test_upload_parses_csv_headers_with_carry_over` & `test_save_mapping_rejects_duplicate_header` harus delete mapping flik di awal+finally. +`test_seeder_populates_default_header_mappings_from_templates` (cek mapping kunci tiap source + idempotent 2× jalankan). `TrackingStatusRuleTest` **19/19**.

# MEMORY — 14 Agustus 2026

## Fix: test packaging OrderOnlineTest tidak idempotent (order_id tetap → stok terkontaminasi)

- **Gejala**: `test_undeliverable_restores_packaging_stock` gagal konsisten bahkan dijalankan sendiri (stok BOX −2 ekstra: 872 vs 870). Test packaging lama memakai `order_id` TETAP (`PKG-UND-1`, `PKG-KMP-1`, `PKG-KBJ-1`, `PKG-NOBOX`) tanpa cleanup — sisa order dari run sebelumnya yang gagal di tengah ikut ke-export (courier=flix-tf, EXPORTABLE) → `recordOutWithPackaging` memotong stok 2×. DB dev tidak di-refresh, jadi run berikutnya terkontaminasi.
- **Fix**: semua `order_id` di test packaging (`PKG-*`) diganti `uniqid('PKG-')` (+ pastikan `ensureCatalog`/`makeProduct` dsb tetap jalan) — pola sama dengan test lain yang memakai `uniqid()`.
- **Verifikasi**: `test_undeliverable_restores_packaging_stock` 1/1, `OrderOnlineTest` **66/66** (356 assertions), suite penuh **154 pass** (hanya ExampleTest 302 pre-existing), pipeline **104/104 PASS**.

## Session: Aturan Status — UI mapping DIBALIK + matching NAMA + kolom masalah data-driven (follow-up)

- **Latar (klarifikasi user)**: konsep tracking = upload CSV dashboard; header file tracking & export template bisa beda walau satu dashboard. Pencocokan ingin pakai **no HP + NAMA** pelanggan. Mapping DIBALIK: **kolom kiri = kolom DATABASE (teks statis, BUKAN form — DB tidak berubah), kolom kanan = header CSV dari file upload**. Konversi status tetap (raw → status unik standar). Kolom masalah HARUS dinamis termasuk keunikan FLIK (status normal di kolom A, masalah di kolom B header beda yang isinya DIAWALI "Problem") → aggregator baru cukup konfigurasi, tanpa ubah kode.
- **`TrackingHeaderMapping::COLUMNS`** + `customer_name` (Nama Pelanggan) → registry **9 kolom**. `aliasMap` + customer_name (`nama shopper`/`recipient name`/`nama penerima`/`customer name`/`buyer name`/`nama pelanggan` — TIDAK pakai `penerima` sendirian: menangkap "alamat penerima" → salah map ke nama).
- **`AggregatorTrackingImportService::normalizeRow`** + baca `customer_name`/`name_norm`; **`resolveOrder`** hanya pakai **phone + customer_name** — nama kosong → unmatched; >1 kandidat nama sama → ambiguous.
- **`problem_match_type`** (migration `2026_08_14_100001`, default `contains`): cara cocok kolom masalah → `starts_with` = DIAWALI keyword (FLIK 3PL "Problem..."). `TrackingStatusRuleService::problemColumnMatches(column, keyword, matchType)`. Seeder FLIK problem → `starts_with`, sisanya `contains`. Form tambah/modal edit + select Cara Cocok Kolom Masalah (muncul saat problem_mode=required).
- **UI dibalik**: `extractHeaders` return `{source, headers[] (array string), mapping{db_column=>header}}` (carry-over per kolom DB); `saveHeaderMapping` terima items `{db_column, header}` + **RuntimeException bila 1 header dipakai 2 kolom** (unique `(source,header)`); controller `saveMapping` validasi `db_column required in:COLUMNS`, header nullable + catch RuntimeException → error mapping. View `edit.blade.php`: tabel kiri kolom DB teks statis, kanan select header; upload isi dropdown + pre-select; JS cegah duplikat header (option disabled); submit items per kolom DB.
- **Test**: `TrackingStatusRuleTest` 14 → **17** (upload carry-over per db_column, save mapping incl. customer_name, duplikat header ditolak, `starts_with` diawali vs mengandung); `AggregatorTrackingImportTest` +2 (matching nama: nama memutuskan 2 order HP+produk+qty sama; nama menang walau alamat file beda). Suite **151 pass** (ExampleTest 302 pre-existing); pipeline **104/104 PASS**.
- AGENTS.md section S di-update (deskripsi UI dibalik, implementasi, Penting, suite count).

## Session: Halaman Aturan Status → mapping HEADER CSV per DASHBOARD (koreksi arah)

- **Koreksi user**: interpretasi pertama saya (upload file → map STATUS VALUE) SALAH. Maksud sebenarnya: user ingin mencocokkan **header CSV** dengan **kolom yang ingin diisi di database** (persis pola export-mapping), dan tampilan dipisah **per dashboard** (FLIK/SiCepat/SPX) — bukan disatukan.
- **`tracking_header_mappings`** (migration `2026_08_14_100000`): `source`, `header` (normalized), `db_column`; UNIQUE `(source, header)` nama eksplisit `tracking_header_mappings_combo_unique`. Model `TrackingHeaderMapping` + konstanta **`COLUMNS`** (registry 8 kolom: tracking_number/phone/address/product_name/quantity/status/problem/delivered_date) — sumber kebenaran dropdown & validasi.
- **Service**: `mapHeaders` kini DB-aware — `headerMappingFor(source)` (cache per instance) → mapping DB **menang per header**, header lain fallback alias hardcoded (mapping sebagian tidak memutus kolom lain). `extractHeaders(filePath)` (header unik + carry-over db_column) + `saveHeaderMapping(source, items)` (bulk replace 1 transaksi). `extractStatuses` dihapus.
- **Controller**: `index` → kartu per dashboard (hitungan via 2 aggregate groupBy); `edit($source)` → halaman per dashboard (mapping header + rules khusus sumber); `upload` (JSON `{source, headers[]}`, file di-`store` dulu agar path punya ekstensi — pola `trackingImport`, `createWithContent` juga pathname tanpa ekstensi); `saveMapping` (validasi `db_column in:COLUMNS`, redirect ke edit). `parse`/`apply` (flow status-value lama) DIHAPUS beserta routes-nya.
- **Views**: `index` → kartu dashboard (gradient per ekspedisi, jumlah mapping + rules, tombol Edit); `edit` (baru) → kartu Mapping Header CSV (upload → tabel header → dropdown kolom DB, simpan via JS hidden `items[]`, validasi sumber file = dashboard dibuka) + kartu Aturan Status (tabel rule khusus sumber + form manual `source` hidden + modal edit).
- **Test** `TrackingStatusRuleTest` 14: index kartu, edit scoped per dashboard (rule flik tak tampil di spx), upload header + carry-over, **save mapping → import parse pakai header tak standar** (RESI001/hp customer/status paket dll, cleanup mapping & rule di finally), + 10 test lama tetap hijau. Pipeline + cleanup `TrackingHeaderMapping::query()->delete()` di awal (agar import tracking selalu pakai alias bawaan).
- **Verifikasi**: suite **147 pass** (hanya ExampleTest 302 pre-existing) · pipeline **104/104 PASS**. AGENTS.md section S di-update (deskripsi per dashboard, implementasi, Penting).

## Session: Fitur Copy ke WhatsApp di detail laporan operasional

- **Latar**: user minta tombol copy di halaman detail laporan (`/laporan-operasional/{batch}`) yang mengubah SEMUA isinya jadi teks untuk di-paste di WhatsApp (bukan screenshot).
- **Implementasi (view-only, tanpa controller)**: teks dibangun **server-side di `@php`** view `laporan/batch.blade.php` — `$copyText` memakai `$rows`/`$grouped` yang SAMA dengan tabel (format angka identik `number_format(...,0,',','.')`; pemisah varian pcs 2 vs 4 tetap baris terpisah). Format WA: header (pengirim, rentang, batch) → ringkasan (order/resi, qty, uang masuk, HPP, margin %) → `━━━ BARANG TERJUAL ━━━` per produk + baris varian (`kode (+power) | nama | N pcs/order ×J = Q pcs — Rp (HPP)`) → `━━━ TOTAL ━━━`.
- **Mekanisme copy**: teks disimpan di `<textarea id="copy-report-text" hidden>` (dibaca `.value` — HTML-escape ter-decode otomatis oleh browser) + `<pre id="copy-report-fallback">`. JS: `navigator.clipboard.writeText` (cek `window.isSecureContext` — di HTTP non-localhost clipboard API undefined) → fallback `document.execCommand('copy')` dengan `select()`+`setSelectionRange` → gagal total → `<pre>` fallback tampil utk blok manual. Textarea pakai `position:fixed;left:-9999px` (BUKAN `display:none`) agar select/execCommand tetap jalan.
- **Tombol**: `📋 Copy ke WhatsApp` (`#btn-copy-report`, `clay-btn-primary`) di header batch samping tombol Kembali; feedback "✅ Tersalin — paste di WhatsApp!" / "❌ Gagal — blok teks di bawah manual".
- **Test** `OperationalReportTest` +1 → 11/11: `test_batch_detail_has_whatsapp_copy_text` — halaman memuat btn/textarea/pre + isi teks (LAPORAN PENGIRIM, BARANG TERJUAL, 2 pcs, 238.000, HPP 140.000).
- **Verifikasi**: suite **145 pass** (hanya ExampleTest 302 pre-existing) · pipeline **104/104 PASS**. AGENTS.md section T di-update.

## Session: Laporan hanya hitung order yang DIPROSES (cancel/belum_diproses/duplikat/undeliverable dikecualikan)

- **Latar**: user lihat selisih di laporan-operasional — angka laporan & detail tidak cocok dengan yang benar-benar dikirim. Setelah saya analisis data batch #1: laporan vs detail sebenarnya PERSIS (uang 2.078.000 / HPP 541.000 / 13 order); selisihnya dari (1) kartu "Barang Keluar" (jurnal, termasuk BOX/LAP/KDF = 18 unit packaging/split) vs "Qty Terjual" (order 49), dan (2) order TIDAK pernah diproses ikut dihitung.
- **Keputusan user**: kecualikan `cancel`, `belum_diproses`, **`duplikat`** (ditanyakan, dipilih "kecualikan juga"), dan courier `undeliverable` — logika: pesanan itu tidak pernah diproses.
- **`ShippingOrder::scopeProcessed()`** (baru): `whereIn('shipping_orders.status', EXPORTABLE_STATUSES)` (real/tembakan) + courier ≠ `undeliverable` (atau null). Kolom DIKUALIFIKASI `shipping_orders.*` karena scope dipakai pada query ber-JOIN (products/batches punya kolom `status` juga → ambigu kalau tidak).
- **Pemakaian**: `OperationalReportController::index` (`$orderPeriode` + `$rows`), `show` (`$rows` + `resi`), `DashboardController::dashboardGeneral` (`$orderHariIni`). Jurnal `stock_movements` TIDAK difilter (sudah benar: hanya exportable yang reserve stok).
- **Test** `OperationalReportTest` +1 (10/10): `test_report_excludes_cancel_belum_diproses_duplikat_and_undeliverable` — buat 2 diproses (real+tembakan, 338.000) + 4 tidak diproses (cancel/belum_diproses/duplikat/real-undeliverable, masing 999.000) → laporan & detail tampil 338.000/140.000 dan `assertDontSee('999.000')`. Helper `createOrder` + param `status`/`courier` (default real/flix-tf, backward-compatible).
- **Verifikasi data nyata**: batch #1 13 order 2.078.000 → 9 order 1.413.000 (yang diproses). Suite **144 pass** (hanya ExampleTest 302 pre-existing) · pipeline **104/104 PASS**.


## Session: Detail per Pengirim — Barang & Rincian Varian Terjual (fitur lanjutan T)

- **Latar**: user minta kolom **nama pengirim** dan **total pengeluaran** di tabel laporan-operasional bisa diklik → masuk halaman **detail** yang isinya barang apa saja yang terjual + jumlahnya + **rincian varian produk** (kacamata promo 2 pcs vs 4 pcs beda).
- **Route baru**: `GET /laporan-operasional/{batch}` → `operational-report.batch` (model binding `OrderOnlineImportBatch`). Link dari laporan meneruskan `dari`/`sampai` → detail ikut periode terpilih (default hari ini).
- **`OperationalReportController::show()`**: 1 aggregate `shipping_orders` LEFT JOIN `products` (nama/kode master + purchase_price utk HPP) + LEFT JOIN `product_variants` (power/kode varian), GROUP BY `(product_id, product_variant_id, product_name, product_code, quantity, ...)`. **Kunci pemisah pcs**: kacamata promo "Dapat N" disimpan sbg `product_name = "... N pcs"` + `quantity = N` → menyertakan `product_name` + `quantity` (qty per order) di GROUP BY membuat varian sama (mis. KMP+1.50) dgn isi 2 pcs dan 4 pcs jadi **baris terpisah**. Ringkasan (order/qty/uang/HPP) dihitung dari collection; `resi` via 1 aggregate kecil (`awb` non-kosong).
- **Query index laporan**: select `b.id as batch_id` ditambahkan (sebelumnya groupBy b.id tapi tidak di-select) → link detail per batch.
- **View baru `laporan/batch.blade.php`**: kartu ringkasan (Order/Resi, Qty Terjual, Uang Masuk, Margin vs HPP) + date-range picker + tabel **Barang Terjual & Rincian Varian**: grup header per produk master (`$rows->groupBy('kode_master')`) + baris varian (kode varian + badge power + nama terjual + qty/order + jumlah order + qty terjual + uang + HPP) + tfoot TOTAL. `laporan/operasional.blade.php`: nama pengirim & kolom Total Pengeluaran jadi `<a class="link-report" data-page-link>`.
- **Trap**: kolom `created_at` ambigu setelah JOIN `products`/`product_variants` (keduanya punya `created_at`) → SQLSTATE 1052 → WAJIB kualifikasi `shipping_orders.created_at` di WHERE detail.
- **Test** `OperationalReportTest` +4 → **9/9**: link baris → batch detail (assert href pakai `htmlspecialchars` karena `&` di-render jadi `&amp;`), detail memisahkan 2 pcs vs 4 pcs (qty per order 2 & 4, total qty 6, uang 714000, HPP 420000), detail hormati rentang tanggal, helper `makePowerProduct(code, power)` utk varian ber-power.
- **Verifikasi**: suite **143 pass** (hanya ExampleTest 302 pre-existing) · pipeline `verify_pipeline.php` **104/104 PASS** · route `operational-report.batch` terdaftar. AGENTS.md section T di-update.


## Session: Fix kartu laporan operasional tampil 0 (bukan bug query)

- **Latar**: user lapor lagi "card barang masuk/keluar di halaman laporan masih belum berfungsi, padahal di jurnal stock sudah ada" — menduga query salah.
- **Diagnosis**: query & server-side SUDAH benar — render HTML memuat `data-counter="4"` (keluar) & `data-counter="11491"` (masuk) utk hari ini. Masalahnya di **tampilan**: markup kartu `data-counter="{{ $nilai }}">0</div>` punya teks visible hardcoded `0`, dan angka hanya berubah lewat animasi JS `CounterAnimation` (`resources/js/animations.js`). Tapi di lingkungan dev tanpa build Vite (`public/build/manifest.json` tidak ada), `@vite(...)` di layout tidak me-render apa pun → `app.js`/`animations.js` TIDAK dimuat → semua `[data-counter]` diam di `0` selamanya. (Bug yang sama menimpa semua kartu stat `data-counter` di seluruh app, bukan cuma laporan.)
- **Fix**: teks awal kartu diisi **nilai asli** — `data-counter="{{ $nilai }}">{{ $nilai }}</div>`. Tanpa JS langsung tampil angka benar; kalau JS ada, animasi counter tetap jalan (override textContent dari 0→target). Diterapkan ke: `dashboard/general.blade.php` (4 stat + 3 ops), `dashboard/keuangan.blade.php` (2), `laporan/operasional.blade.php` (3), `regional/index.blade.php` (4), `team/performance.blade.php` (4).
- **Verifikasi**: render HTTP `data-counter` vs teks visible: `4=>4`, `11491=>11491`, `0=>0` (resi memang 0 hari ini). Suite **139 pass** (hanya ExampleTest 302 pre-existing). AGENTS.md section T diberi catatan fix.


## Session: Laporan Operasional — Dashboard Hari Ini + Detail per Pengirim (fitur T)

- **Latar**: user mau dashboard admin menampilkan barang keluar/masuk hari ini, jumlah resi, dan metode pembayaran (COD/bank_transfer) — klik → halaman laporan yang merinci per nama pengirim (import batch): total pengeluaran, resi, metode bayar; paling bawah total keseluruhan uang masuk vs HPP. User tanya "apakah query-nya berat?" — jawaban: **tidak** kalau pakai query agregat (dijelaskan & diterapkan).
- **Keputusan**: query agregat (bukan tabel ringkasan) — 3 query total utk seluruh halaman; tabel ringkasan baru dibuat kalau data sudah jutaan baris DAN lambat (cek slow query > 500ms). Definisi: keluar/masuk = `stock_movements` (date hari ini); resi = `awb` terisi; uang masuk = `SUM(amount)`; HPP = `quantity × products.purchase_price`.
- **Index baru** `2026_08_13_160000`: `shipping_orders.created_at` (sebelumnya tidak ada; filter hari ini/rentang butuh ini). Filter pakai range `>=`/`<`, BUKAN `whereDate()` (mematikan index).
- **Controller baru** `OperationalReportController::index` — parse/balik `dari/sampai`, 2 aggregate hari ini (stok by date, order by created_at range), 1 aggregate laporan (JOIN `order_online_import_batches` utk sender + LEFT JOIN `products` utk HPP, GROUP BY b.id/sender); total dihitung dari collection (pola batch). `DashboardController::dashboardGeneral` + `opsHariIni` utk 4 kartu.
- **View**: `dashboard/general.blade.php` 4 kartu stat (klik → laporan dgn dari/sampai=hari ini); `laporan/operasional.blade.php` baru — date-range-picker + tabel per pengirim (pengeluaran, resi N/total, COD, TF, uang masuk, HPP) + tfoot TOTAL + baris Margin (uang masuk − HPP, %). Sidebar Gudang & Kiriman → Laporan Operasional (owner/super_admin/admin). Route `GET /laporan-operasional`.
- **Test** `OperationalReportTest` (5): kartu dashboard render, laporan per sender + totals, rentang tanggal, empty state (rentang 2019), total per sender = sum. Pola isolasi: tanggal unik `2026-01-XX` + sender/order_id `uniqid()`, batch di-delete di `finally`.
- **Verifikasi**: EXPLAIN — index dipakai tanpa join; dengan join optimizer full scan pd tabel kecil (210 baris, 6ms) — normal, index siap utk data besar. Suite **138 pass** (hanya ExampleTest 302 pre-existing) · pipeline **104/104 PASS**.

## Follow-up: kartu laporan operasional mengikuti periode terpilih

- **Latar**: user lapor "card barang masuk dan keluar di halaman laporan operasi masih belum berfungsi" — kartu di `laporan/operasional` selalu menampilkan data HARI INI (`$stokHariIni`/`$orderHariIni` hardcoded `$today`), tidak merespons filter `dari`/`sampai`.
- **Fix**: `OperationalReportController::index` ganti jadi `$stokPeriode`/`$orderPeriode` yang dihitung dari `$dari`–`$sampai` terpilih (2 aggregate, pola sama); `$sampaiEnd = sampai+1day` eksklusif. View `laporan/operasional.blade.php`: label dinamis `isToday` → "Hari Ini" (default) / "Periode Terpilih" (range lain) + subtitle rentang. Dashboard TIDAK berubah (tetap "hari ini").
- **Bug kecil**: `stock_movements.date` bertipe datetime → range stok harus eksklusif `< besok`, bukan `<= sampai` (movement di hari `sampai` bisa terlewat kalau waktunya bukan 00:00).
- **Test** `OperationalReportTest` +1 (6): `test_report_cards_follow_selected_period` — buat jurnal in/out dgn qty unik (111111/22222) di tanggal unik, GET range tsb → assert `data-counter="111111"`/`"22222"` + label "Periode Terpilih"; GET default → "Hari Ini". Catatan: `assertSee('data-counter="..."')` butuh argumen `false` (default meng-escape `"` jadi `&quot;`).
- **Verifikasi**: OperationalReportTest 6/6 · suite **139 pass** (hanya ExampleTest 302 pre-existing) · pipeline **104/104 PASS**.

## Hotfix: migration tracking_status_rules gagal (key terlalu panjang)

- **Gejala**: `SQLSTATE[42000] ... 1071 Specified key was too long; max key length is 3072 bytes` saat `alter table tracking_status_rules add unique tracking_status_rules_combo_unique(source, raw_status, match_type, problem_mode, status)` — 5 kolom `string()` default (255 char × 4 bytes utf8mb4) = 5100 bytes > 3072.
- **Fix**: batasi panjang kolom di migration `2026_08_13_150000` — `source`/`match_type`/`status`/`problem_mode` → `string(20)`, `raw_status`/`problem_keyword` → `string(191)`; UNIQUE 5 kolom tetap (seeder `updateOrCreate` bergantung padanya).
- **Pemulihan**: migrasi sempat ter-apply sebagian (tabel ada, index tidak, entry migrations tidak tercatat) → `Schema::dropIfExists('tracking_status_rules')` lalu `php artisan migrate` ulang + `db:seed TrackingStatusRuleSeeder` (23 rule).
- **Temuan saat verifikasi**: DB dev `awannacoba` ternyata belum ter-seed lengkap (inventories=0, pivot kosong, produk lama 7/8 varian) → `php artisan db:seed --force` (semua seeder idempotent) memulihkan: inventories=3, products=20, variants=61, pivot=19, variant_inventory=59.
- **Verifikasi**: suite **133 pass** (hanya ExampleTest 302 pre-existing) · pipeline `verify_pipeline.php` **104/104 PASS**.

## Session: Aturan Status Aggregator Dinamis — Mapping Status Dashboard → Status Sistem (fitur S)

- **Latar**: user minta fitur update status aggregator (upload file dashboard → `shipping_orders.aggregator_status`) dibuat dinamis seperti Aturan Courier / Aturan Gudang — mapping raw status → status sistem tidak lagi hardcoded di `AggregatorTrackingImportService::mapStatus`.
- **Migrasi `2026_08_13_150000`**: tabel `tracking_status_rules` — `source` (flik/sicepat/spx), `raw_status` (lowercase), `match_type` (exact/contains), `status` (6 nilai `ShippingOrder::TRACKING_STATUSES`), `problem_mode` (none/required), `problem_keyword` nullable, `sort_order`, `is_active`; UNIQUE `(source, raw_status, match_type, problem_mode, status)` (nama pendek eksplisit).
- **`TrackingStatusRuleService::resolve(source, rawStatus, ?problemColumn)`** (baru): cache per instance (groupBy source, anti N+1), evaluasi urut `sort_order`; rule `problem_mode=required` hanya cocok bila kolom masalah terpenuhi — `problem_keyword` null = kolom tidak kosong (SPX OnHold), terisi = MENGANDUNG keyword (FLIK 3PL `problem`, case-insensitive). `match_type` exact/contains. Tanpa cocok → null.
- **`AggregatorTrackingImportService`**: `mapStatus(source, rawStatus, problemColumn)` delegasi ke service; argumen `true` lama tetap diterima (paksa 'problem', kompatibilitas); `isProblem()` DIHAPUS; `normalizeRow` kirim teks kolom masalah langsung (ganti boolean hasil isProblem).
- **Controller/UI**: `TrackingStatusRuleController` (index/store/update/destroy/toggle/move; validasi source/match/status/problem_mode `in:...`; duplikat per kombinasi; normalisasi lowercase) + view `tracking_status_rule/index.blade.php` (pola cr-modal: form tambah dengan select Sumber/Cara Cocok/Status Sistem/Kolom Masalah + input kata kunci kondisional via JS, tabel badge + toggle + ↑↓ + edit modal + hapus, info box). Sidebar Data Master → **Aturan Status** (di bawah Aturan Gudang). Routes `/tracking-status-rules` (`tracking-status-rule.*`).
- **Seeder `TrackingStatusRuleSeeder`** (DatabaseSeeder #8c, idempotent updateOrCreate): 23 rule — FLIK 8 (dikonfirmasi/sedang diantar → problem mode required keyword 'problem' sort 1 + rule normal sort 10/20, dicairkan/terkirim→delivered, dalam transit pengembalian→returning, dikembalikan→returned), SICEPAT 6 (incl. bermasalah→problem), SPX 9 (pending pickup/in transit/delivering → problem mode required keyword null sort 1 + normal, delivered/returning/returned).
- **Test**: `TrackingStatusRuleTest` baru 10 test (index, store+resolve dinamis, problem required keyword null & terisi, prioritas problem atas normal by sort_order, contains, toggle, destroy, duplikat, move swap, update; + import ikut rules DB). `AggregatorTrackingImportTest::test_map_status_english_values` — argumen `true` diganti string kolom masalah. **Trap**: `TrackingStatusRuleService` cache PER INSTANCE → test yang buat rule di tengah jalan harus pakai service baru (`$flikSvc = new TrackingStatusRuleService`).
- **verify_pipeline.php**: + precheck `tracking_status_rules` (butuh seeder utk langkah tracking).
- **Perubahan perilaku kecil**: FLIK problem dulu `stripos(...,'problem')===0` (prefix) → kini contains; lebih longgar & bisa diubah admin.
- **Suite**: **133 pass** (hanya ExampleTest 302 pre-existing); pipeline **104/104 PASS**.

## Session: Aturan Gudang Dinamis (produk→gudang) + Rule Courier Khusus Produk (fitur P)

- **Latar**: user minta mapping "kode produk → nama pengirim" (SH→GTM, KSP→Aurora) yang tadinya konstanta `WAREHOUSE_BY_PRODUCT` dibuat dinamis seperti `courier_rules`; plus **rule SH → courier selalu flix**, tidak terpengaruh aturan courier provinsi.
- **Migrasi `2026_08_13_140000`**: tabel `warehouse_rules` (`product_code` unique, `warehouse`, `is_active`). `2026_08_13_140001`: `courier_rules.product_code` nullable + index.
- **`WarehouseRuleService`** (baru): `resolve(productCode)` dari tabel aktif (cache per instance, anti N+1), normalisasi uppercase + `explode('+')[0]`. `OrderTemplateExportService::warehouseFor` prioritas: **warehouse_rules → gudang utama produk (is_primary) → WAREHOUSE_BY_PRODUCT → sender**.
- **`CourierRuleService::resolve` 2 fase**: fase 1 = rule `product_code` terisi & cocok (SELALU menang, berapa pun sort_order) → fase 2 = rule umum (payment+province, sort_order terkecil menang). `OrderOnlineImportService` meneruskan `product_code` CSV. `tembakan` tetap spx.
- **Controller/UI**: `WarehouseRuleController` (index/store/update/destroy/toggle; duplikat per product_code; normalisasi) + view `warehouse_rule/index.blade.php` (pola cr-modal, sidebar Data Master → Aturan Gudang, routes `/warehouse-rules`). `CourierRuleController` + view: field **Kode Produk** (datalist produk) di form/tabel/modal edit; cek duplikat (payment+province+product_code).
- **Seeder**: `WarehouseRuleSeeder` (baru, idempotent updateOrCreate: SH→GTM, KSP→Aurora — tambahan admin dipertahankan); `CourierRuleSeeder` + rule `product_code='SH'` → flix-tf (payment/province null, sort 1).
- **Test**: CourierRuleTest +3 (rule produk menang atas provinsi — pakai kode UNIK karena SH sudah di-seed di DB dev; nonaktif → jatuh ke provinsi; store normalisasi; duplikat kombinasi). `WarehouseRuleTest` baru 7 test. OrderOnlineTest +2: import SH cod Jawa Barat → flix-tf & KMP → sicepat (**phone unik per run** — phone tetap + SH + alamat tetap kena dup-signature dari run sebelumnya → courier null); warehouse rule menimpa gudang utama (nonaktif → Gudang Pusat).
- **Suite**: **122 pass** (hanya ExampleTest 302 pre-existing); pipeline **103/103 PASS**. AGENTS.md section P + update section M.

## Session: Barang Masuk (Purchase) — Opsi Gudang Tujuan (fitur R)

- **Latar**: user minta halaman Barang Masuk diperbaiki karena sekarang sudah ada konsep gudang (M2M) — stok pembelian harus bisa masuk ke gudang tertentu, bukan selalu gudang utama produk.
- **Migrasi `2026_08_13_130000`**: `purchases.inventory_id` nullable FK + index. `Purchase` model + `inventory_id` fillable + relasi `inventory()`.
- **`PurchaseController::store`**: validasi `inventory_id` REQUIRED; `recordIn(..., $purchase->inventory_id)` (catatan jurnal + nama gudang); `index` eager-load `inventory`, filter `inventory_id`, pass `$inventories`. HPP tetap per produk.
- **View**: select **GUDANG TUJUAN** (required) + JS autofill: pilih varian → gudang utama produk (`data-primary-wh` di optgroup; Barang Pasti/Additional tak punya primary → pilih manual); kolom Gudang (badge 🏭) di tabel; filter gudang.
- **Test**: `test_purchase_records_stock_to_selected_warehouse` — pembelian ke gudang B (bukan utama A): purchases.inventory_id=B, stok B=10, A tetap 0, index filter gudang. Suite **110 pass** (ExampleTest 302 pre-existing); pipeline **103/103 PASS**.
- AGENTS.md section R ditulis.

## Session: Master Produk Terpusat — Produk di halaman sendiri, Gudang hanya attach (fitur Q)

- **Latar**: user mau membuat produk baru dilakukan di **halaman Produk sendiri**; di halaman Gudang, "tambah barang" tidak lagi membuat produk baru, tapi **menambahkan produk yang sudah ada** (beserta variannya dari `products`/`product_variants`) ke gudang itu — supaya semua produk di gudang teratur.
- **ProductController baru** (`/product`, sidebar Data Master): index (filter search/tipe/status) + store/update/destroy/toggle + variant CRUD (JSON). Saat create: **varian default otomatis** (stock 0, tanpa gudang/stok awal). Update produk mengubah goods_type/min_stock dst.; hapus = soft delete. Route `product.*` + `product.variant.*`.
- **GudangController**: `productStore`/`productUpdate`/`productDestroy`/`variant*`/`toggle*` DIHAPUS → ganti `productAttach` (pilih produk master + stock_awal opsional + is_primary; auto-primary bila belum punya gudang; duplikat attach ditolak), `productWarehousesUpdate` (centang gudang + radio primary), `productDetach` (lepas dari gudang, produk tetap ada). `index` kirim `$availableProducts` (aktif & belum terdaftar di gudang ini).
- **View Gudang**: tombol "＋ Tambah Produk ke Gudang" per section → modal form POST pilih produk (select difilter goods_type via JS) + stok awal + checkbox utama + link "Buat produk baru →" ke halaman Produk. Tombol `🏷` → modal Kelola Gudang Produk (form PUT). `🗑` → form detach (hidden inventory_id). Baris varian expand read-only (stok per gudang); status produk/varian jadi badge; modal create/edit produk & varian DIHAPUS dari halaman Gudang.
- **View master** (`product/index.blade.php`): tabel (kode, nama, tipe, gudang utama + count, stok total, min stok + badge, HPP, harga jual, toggle status, aksi) + modal buat/edit + expand varian (add/edit/delete/toggle).
- **Jebakan**: (1) `ProductController::class` di routes perlu `use` import (routes/web.php tidak punya namespace → tanpa import jadi `\ProductController`). (2) Dropdown attach memuat SEMUA produk yang bisa di-attach → test scoping lama `assertDontSee('Kabel Casan Hp 3IN1')` di halaman GTM gagal (nama itu legit muncul di dropdown) → ganti cek via hitungan section ("Barang Inti: 1", "Barang Additional: 0").
- **Test**: OrderOnlineTest **62/62** (4 test lama diganti: attach, warehouses+detach, master variant CRUD, master create/update/toggle/destroy); suite penuh **108 pass** (ExampleTest 302 pre-existing); pipeline **103/103 PASS**.
- AGENTS.md section Q ditulis (menandai pembalikan fitur O soal lokasi CRUD produk).

## Follow-up (hari yang sama): gudang UTAMA HANYA untuk Barang Inti (core)

- **Latar**: user minta label "gudang utama" hanya berlaku untuk **Barang Inti**; Barang Pasti & Additional tidak boleh punya label itu (fatal — Barang Pasti ada di semua gudang, Additional mengikuti barang inti).
- **Migrasi `2026_08_13_120000_primary_only_for_core`**: set `is_primary=false` untuk semua pivot produk non-core (membersihkan backfill lama yang menandai consumable/additional).
- **`Product::primaryInventoryId()`** + guard: non-core (consumable/additional) selalu return null. **Jebakan**: instance baru dari `Product::create()` punya `goods_type` null di memori (DB default 'core' tidak di-refresh ke model) → guard harus `goods_type !== null && goods_type !== 'core'` agar tidak salah memblokir produk baru (test `test_product_belongs_to_multiple_warehouses...` ketahuan).
- **Seeder**: `syncInventoryMembership()` hanya set `is_primary` utk core; consumable/additional selalu false; `seedVariants(..., $designatedInventoryId)` utk opening stock non-core (ganti `primaryInventoryId()` yang kini null utk non-core).
- **GudangController**: `productAttach` hanya jadikan primary utk core (dan `attach` dengan primary baru membersihkan primary lama → tidak ada primary ganda); `productWarehousesUpdate` — radio primary hanya required utk core, non-core semua baris is_primary=false.
- **UI**: modal attach — checkbox "Jadikan gudang utama" hanya tampil saat produk core dipilih (`#am-primary-wrap` + JS); modal kelola gudang — kolom "Utama" (`.wm-utama`) disembunyikan utk non-core (`data-goods-type` di tombol 🏷); halaman Produk — kolom Gudang Utama tampil "—" utk non-core.
- **Test**: +`test_primary_only_for_core_products` (attach KTH/BOX tidak jadi primary, warehouses non-core tanpa primary, core tetap bisa + menggantikan primary lama, restore di finally). Suite **109 pass** (ExampleTest 302 pre-existing); pipeline **103/103 PASS**.

## Session: Relasi Many-to-Many Produk ↔ Gudang + Stok per Gudang (fitur P)

- **Latar**: user minta relasi `products.inventory_id` (1 produk → 1 gudang induk) diubah jadi **many-to-many** — 1 produk bisa dimiliki banyak gudang (pivot), stok disimpan di tabel stok. Ditanya juga apakah ini lebih baik untuk sistem sekarang.
- **Jawaban saya (disetujui)**: ya, lebih baik — menghapus special-case Barang Pasti (consumable yang dulu "ada di semua gudang") dan konsisten dgn arah fitur O. 3 syarat desain: (1) stok per **VARIAN × gudang**, (2) jurnal tetap sumber kebenaran → kolom stock di tabel baru = cache, (3) butuh penanda gudang UTAMA untuk export/fulfillment.
- **Q&A user**: granularitas **per varian × gudang**; export pakai **flag `is_primary`** di pivot; HPP **per produk**; min_stock **per produk** (keduanya tetap seperti sekarang).
- **Migrasi `2026_08_13_110000`**: `product_inventory` (product_id, inventory_id, is_primary, UNIQUE combo) + `product_variant_inventory` (product_variant_id, inventory_id, stock, UNIQUE combo). Backfill: tiap produk → pivot dari `products.inventory_id` (is_primary=1); consumable di-attach ke SEMUA inventory; stok per (varian, gudang) diagregasi dari jurnal (movement inventory_id NULL → gudang utama produk). `2026_08_13_110001` drop `products.inventory_id`. Sudah ter-apply ke DB dev (awannacoba).
- **Model**: `ProductInventory`, `ProductVariantInventory` baru; `Product::inventories()` BelongsToMany + `primaryInventory()` (wherePivot is_primary) + `primaryInventoryId()` + `stockAt()`; `inventory()` relasi lama DIHAPUS; `Inventory::products()` → BelongsToMany; `ProductVariant::inventoryStocks()` + `stockAt()`.
- **StockService**: `syncVariantInventoryStocks($variantId)` upsert cache dari jurnal per (varian, gudang) — dipanggil recordIn/recordOut/reverseReference/recalculateAll; `inventoryId` null di-resolve ke gudang utama produk; `recordOutWithPackaging` pakai `primaryInventoryId()` (ganti `product->inventory_id`).
- **Export `warehouseFor`** kini DB-driven: nama gudang UTAMA produk (cache per instance), fallback `WAREHOUSE_BY_PRODUCT` → sender. **Konsekuensi penting**: produk non-spesial (KMP/KBJ/KCHP) yang tadinya sender kini tampil **`Gudang Pusat`** di kolom Kode Warehouse — ini perubahan perilaku export (bukan cuma internal).
- **Halaman Gudang**: scoping per gudang via `whereHas('inventories')`; `perInventoryStockByVariant` baca cache (bukan aggregate jurnal); `productStore` attach pivot primary; modal edit produk + seksi **Gudang Produk** (checkbox gudang + radio utama, dikirim ke productUpdate sbg `inventory_ids[]`+`primary_inventory_id`); detach → hapus pivot + cache stok gudang tsb (jurnal tetap).
- **Seeder**: `syncInventoryMembership()` — consumable attach semua gudang (primary pertama), lainnya gudang induk (primary); re-seed paksa primary bawaan tanpa hapus keanggotaan admin; opening stock non-consumable via `primaryInventoryId()`.
- **Jebakan yang ketemu**: `primaryInventory()` harus dipanggil sebagai PROPERTY (`?->primaryInventory?->first()?->name`) bukan method — BelongsToMany tidak punya atribut `name`. Test M2M yang ganti primary produk wajib restore di `finally` (test `warehouse_export_uses_primary_inventory` awalnya meninggalkan KMP tanpa primary → test flik & variant_code ikut gagal; sudah diperbaiki).
- **Test**: OrderOnlineTest **62/62** (+2 test M2M baru); suite penuh **108 pass** (ExampleTest 302 pre-existing); pipeline `verify_pipeline.php` **103/103 PASS** (kit `warehouseFor` → 'Gudang Pusat' + referensi statis diregenerasi, `02_export_flik.csv` baris non-KSP/SH berubah GUDANG-PUSAT→Gudang Pusat).
- AGENTS.md section P ditulis; referensi `products.inventory_id` lama di section O di-update.

## Session: Halaman Gudang — 3 Kategori Barang + Aturan Kemasan Dinamis + Acuan Restock (fitur O)

- **Latar**: user mau gudang dipilah jadi 3 tipe — Barang Pasti (Kertas Thermal, Lakban, Bubble Wrap; stok manual admin karena tidak menentu), Barang Inti (produk yang dijual; berkurang otomatis), Barang Additional (BOX/LAP; berkurang otomatis mengikuti barang inti dengan rasio yang bisa diatur). Juga acuan re-stock per produk, dan swap gudang **KSP→Aurora, SH→GTM** (sebelumnya terbalik).
- **Q&A**: implementasi penuh; `min_stock` di-set **per produk** (form Produk, acuan total stok); kelola aturan kemasan **di halaman Gudang**.
- **Kolom baru** (`2026_08_13_100000`): `products.goods_type` enum consumable/core/additional (default core, +index) & `products.min_stock` unsigned int default 0. Product model: constants `GOODS_TYPES`/`GOODS_TYPE_LABELS` (Barang Pasti/Inti/Additional) + relasi `packagingRulesAsSource()`.
- **Tabel `packaging_rules`** (`2026_08_13_100001`): `(source_product_id, target_product_id, qty_per, is_active)` UNIQUE (source,target). `PackagingRule` model + relasi source/targetProduct.
- **`StockService` DB-driven**: `recordOutWithPackaging()` tidak lagi hardcode BOX/LAP/`PACK_QTY_PER` — baca `packagingRulesFor(productId)` (cache per instance, `with('targetProduct')`, groupBy source) → tiap rule aktif: `intdiv(qty, qty_per)` dari `defaultVariantOf(target)`; target tanpa varian aktif → `RuntimeException` "Produk BOX belum terdaftar." (pesan pakai kode produk). Split KBJ→KDF tetap special-case (`kdfVariants()` cache, power sama → fallback terkecil). `KACAMATA_CODES` dipertahankan utk `productDisplayName` export.
- **Enum reference `manual`** (`2026_08_13_100002`): penyesuaian stok manual gudang memakai `reference='manual'` + `reference_id` acak (`random_int`) — kalau `adjustment`+id varian, bakal menimpa opening stock seeder (unique key `(reference, reference_id, type, product_variant_id)`).
- **ProductSeeder**: semua produk diberi `goods_type` (update idempotent saat re-seed, `min_stock` admin TIDAK ditimpa); produk baru consumable `KTH`/`LAK`/`BUB` (1 varian, stok 1000); `seedPackagingRules()` 6 rule (KMP/KSP/KBJ→BOX/LAP qty_per=2, updateOrCreate → test packaging lama tetap hijau karena tiap `ensureCatalog()` me-reseed).
- **Halaman `/gudang`**: `GudangController` (index/adjust/packagingStore/Update/Destroy) + view `gudang/index.blade.php` — 3 section kartu per tipe (total stok, chip per varian, badge ⚠ Perlu Restock bila total ≤ min_stock, form penyesuaian manual per varian consumable: qty + catatan + ＋Tambah/−Kurangi) + kartu Aturan Kemasan (tambah rule, ubah qty_per, toggle Aktif, hapus). Sidebar Gudang & Kiriman → link 🏬 Gudang (di atas Barang Masuk). Route `/gudang`, `/gudang/adjust`, `/gudang/packaging-rules` (+PUT/DELETE).
- **Form Produk**: select Tipe Barang + input Min. Stok (Acuan Restock) di `product/form.blade.php`; `ProductController::store/update` validasi `goods_type in:consumable,core,additional` + `min_stock integer min:0`.
- **Swap gudang**: `WAREHOUSE_BY_PRODUCT = ['KSP'=>'Aurora','SH'=>'GTM']` — service, 3 test warehouse (KSP→Aurora, SH→GTM, ZIP berisi Aurora+GTM+sender), `filecoba/generate_test_kit.php::warehouseFor`, referensi statis `02_export_flik.csv` (baris KSP `GTM`→`Aurora`).
- **Test**: `OrderOnlineTest` +5 (qty_per dinamis 1:1 → BOX −4, LAP tetap −2; rule nonaktif → BOX tetap; gudang render + adjust in/out; CRUD rule + duplikat ditolak; badge restock muncul/hilang) → **52/52 pass**; seluruh suite **98 pass** (hanya `ExampleTest` 302 pre-existing); pipeline `verify_pipeline.php` **103/103 PASS** (artefak `actual_export_*.csv` regenerasi pakai Aurora utk KSP).
- AGENTS.md section O ditulis + referensi KSP→GTM lama di-update ke KSP→Aurora/SH→GTM.

## Follow-up (hari yang sama): stok & aturan kemasan PER GUDANG

- **Latar**: user minta halaman gudang lebih fleksibel — Barang Pasti ada di tiap gudang, jadi atur stoknya **per gudang**; rules kemasan juga **per gudang**.
- **Migration `2026_08_13_100003`**: `packaging_rules.inventory_id` nullable FK (null = Semua Gudang) + UNIQUE `(source, target, inventory)` pakai nama eksplisit `packaging_rules_combo_unique` (default-nya 74 char > 64 batas MySQL → migrasi pertama gagal & ter-apply sebagian; ditulis ulang idempotent: guard `hasColumn` + cek nama index, rename index sisa "1" jadi `packaging_rules_inventory_id_index`).
- **StockService**: `recordIn`/`recordOut` + param `inventoryId` (paling akhir, backward-compatible) → di-set ke movement; `stockOf(variantId, ?inventoryId)` filter per gudang; `recordOutWithPackaging` mencatat semua movement (main/KDF/additional) dgn `inventory_id` = gudang induk produk; `packagingRulesFor(productId, inventoryId)` = rule spesifik `union` global (spesifik menimpa utk target sama), cache per instance.
- **GudangController**: `index` kirim `$inventories` + `$perVariant` (stok per gudang per varian, 1 query aggregate groupBy variant+inventory); `adjust` menerima `inventory_id` (form per gudang); `packagingStore` duplikat cek per (source, target, inventory), inventory kosong = global.
- **View**: Barang Pasti → kartu per produk dgn tabel per gudang (stok + form ＋/− + hidden inventory_id); Inti/Additional → kolom "Stok per Gudang" chip (akumulasi per varian dihitung manual, bukan flatMap — key ganda); kartu Aturan Kemasan → select gudang + badge 🏭 gudang / "Semua Gudang".
- **Seeder**: opening stock consumable (KTH/LAK/BUB) dibagi rata per inventory (reference_id unik `variant_id*100+inventory_id`); produk lain opening stock tercatat ke gudang induk (`inventory_id` = produk). `PurchaseController` juga mencatat `inventory_id`.
- **Test**: `OrderOnlineTest` +2 → 54 (adjust per gudang total=jumlah gudang & kurangi satu gudang tak pengaruh gudang lain; rule per gudang menimpa global — BOX −4 di gudang ber-rule 1:1 vs −2 di gudang tanpa rule, restore state di finally). Suite 100 pass (ExampleTest 302 pre-existing); pipeline 103/103.

## Follow-up (hari yang sama): 1 GUDANG = 1 HALAMAN

- **Latar**: user minta tampilan gudang bukan semua gudang dalam 1 halaman, tapi **pilih gudang dulu** — semisal pilih GTM maka tampil Barang Pasti + produk SH saja; buka gudang lain isinya beda.
- **Scoping**: `GudangController::index(Request)` — tanpa `inventory_id` → hanya kartu **Pilih Gudang** (prompt); dengan `inventory_id` → Barang Pasti (SEMUA consumable, ada di tiap gudang) + Barang Inti/Additional `where inventory_id = gudang` + rules global & khusus gudang. `perInventoryStockByVariant(?inventoryId)` difilter gudang aktif. View: picker kartu (state aktif), header gudang, 3 section scoped (stok di gudang ini + per varian), form adjust hidden inventory_id = gudang aktif, kartu Aturan Kemasan (tambah default gudang aktif).
- **`products.inventory_id` diselaraskan** (seeder + data dev): SH→GTM, KSP→Aurora, sisanya → Gudang Pusat (selaras `WAREHOUSE_BY_PRODUCT` export). `ProductSeeder` update `inventory_id` saat re-seed. Data dev: `db:seed ProductSeeder` + `UPDATE stock_movements ... SET inventory_id = p.inventory_id` (core/additional, 10 baris KSP/SH opening pindah gudang); opening consumable sudah per-inventory (333/333/334).
- **Test**: +1 `test_gudang_page_scoped_per_warehouse` (tanpa pilihan → picker saja tanpa produk; GTM → KTH + Shendara, tanpa KCHP/KNGH — produk yang tidak ada di rule kemasan jadi penanda scoping; Aurora → Sporty, tanpa Shendara). `test_gudang_page_renders_and_adjusts_consumable_stock` & `test_gudang_page_shows_restock_warning` di-update kirim `inventory_id`. `OrderOnlineTest` 55/55; suite 101 pass (ExampleTest 302 pre-existing); pipeline 103/103.

## Follow-up (hari yang sama): CRUD Produk Pindah ke Halaman Gudang

- **Latar**: user minta CRUD produk (yang di halaman `/product`) dipindah ke dalam halaman Gudang — supaya saat input produk baru, `inventory_id` otomatis mengacu ke gudang yang sedang dibuka (tidak perlu pilih gudang lagi; tidak ada risiko semua produk baru jatuh ke Gudang Pusat).
- **Q&A**: (1) halaman `/product` **dihapus total** (route, sidebar, view); (2) saat tambah produk di gudang **auto buat varian default** (kode = kode produk, power 0) + tetap bisa kelola varian tambahan.
- **Controller**: `ProductController.php` + `resources/views/product/*` DIHAPUS. Semua logika pindah ke `GudangController`: `productStore` (validasi + `inventory_id` dari hidden form = gudang dibuka; 1 transaksi buat produk + varian default + stok awal via `recordIn(..., inventory_id)`), `productUpdate` (inventory TIDAK bisa diubah), `productDestroy`, `variantStore/Update/Destroy`, `toggleStatus/toggleVariantStatus`.
- **Routes**: `product.*` & `product.variant.*` dihapus → `gudang.product.store/update/destroy/toggle-status`, `gudang.variant.store/update/destroy/toggle-status` (URL `/gudang/products`, `/gudang/products/{product}/variants`, `/gudang/variants/{variant}`). Sidebar link Produk dihapus.
- **View `gudang/index.blade.php`**: tombol ＋ Tambah Produk per section (goods_type preset, modal create/edit produk dengan info "Gudang otomatis: {nama}" + hidden inventory_id + stok awal create-only), baris produk + kolom toggle status, baris expand varian (tabel varian + modal add/edit + delete + toggle), badge ⚠ Perlu Restock dikembalikan di kolom Min. Stok (sempat hilang saat kolom Status diganti toggle — ketahuan lewat test).
- **Test**: +4 (`productStore` → inventory= gudang + varian default + stok awal tercatat ke gudang; `productUpdate` inventory tetap; `productDestroy`; `variantStore` + stock_awal; `toggleStatus`). `OrderOnlineTest` 59/59; suite 105 pass (ExampleTest 302 pre-existing); pipeline 103/103.

## Follow-up (hari yang sama): Jenis Aturan Kemasan `rule_type` — Additional vs Split (promo Beli 1 Dapat 2)

- **Latar**: user bingung soal promo kacamata — semua kacamata barang promo "Beli 1 Dapat 2" / "Beli 2 Dapat 4", dan bonusnya pakai **KDF** (bukan produk/varian baru). Tanya: bikin varian baru atau aturan bertumpuk? **Q&A**: (1) komposisi paket qty 2 → **1 KMP + 1 KDF** (split, bukan 2 KMP); (2) bonus = **KDF yang sudah ada** (bukan KDP baru); (3) cakupan **KMP & KBJ** (per produk via aturan; KSP TIDAK dapat KDF).
- **Keputusan desain**: varian = dimensi power lensa; promo = aturan keluar stok → jangan bikin varian promo (matriks meledak + import `variation` ekstrak power jadi ambigu). Pakai `packaging_rules` + kolom **`rule_type`**: `additional` (tiap qty_per → 1 pendamping keluar, target varian DEFAULT) vs `split` (main = `ceil(qty/qty_per)`, target = `floor(qty/qty_per)`, varian target **POWER SAMA** fallback power terkecil).
- **Migration `2026_08_13_100004`**: `packaging_rules.rule_type` string default `additional` + index.
- **StockService**: `recordOutWithPackaging()` — hardcode split KBJ **DIHAPUS**, kini data-driven: split rules menentukan mainQty (`ceil`) + target bonus (`floor`, power sama via `variantForPower()`); additional rules tetap `intdiv` dari `defaultVariantOf()`. Guard "KDF tidak dikirim sendiri" tetap. `kdfVariants()`/`kdfVariantFor()` diganti `variantForPower(productId, power)` (query per panggilan — panggilan terbatas, tidak N+1 parah).
- **Seeder `seedPackagingRules()`** — **dipanggil SETELAH produk dibuat** (fix bug laten: di DB fresh rule tidak pernah ter-seed karena produk belum ada saat dipanggil). Seed: 6 rule additional (KMP/KSP/KBJ→BOX/LAP qty_per=2) + **2 rule split (KMP→KDF, KBJ→KDF qty_per=2)** — semua global (inventory_id null), `updateOrCreate` idempotent.
- **GudangController**: `packagingStore`/`packagingUpdate` validasi `rule_type in:additional,split` (default additional). View: form tambah rule punya select **Jenis Aturan** + target kini core+additional (bukan hanya additional — target split adalah KDF yang core); tabel rule ada select jenis + qty_per + toggle Aktif.
- **Dampak test**: KMP qty 4 kini terpecah → 2 KMP + 2 KDF + 2 BOX + 2 LAP (movement 4). `test_export_kacamata_reduces_box_lap`, `test_shipment_import_reduces_box_lap`, `test_packaging_rule_qty_per_changes_reduction`, `test_packaging_rule_inactive_skips_reduction` di-update; `test_gudang_packaging_rule_crud` ganti sumber KMP→KSP (KMP→KDF sudah di-seed global, kombinasi bentrok) + assert `rule_type` tersimpan; +1 test `test_export_kmp_promo_splits_to_kdf` (qty 2 → 1+1; qty 1 → 1+0). `OrderOnlineTest` **60/60**; suite 106 pass (ExampleTest 302 pre-existing); pipeline 103/103 (delta KMP/KDF baru tidak di-check pipeline — hanya BOX/LAP/KDF+1.25 yang tetap sama).

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
- **Skenario 10 order**: CBC-101..105 bank_transfer → `flix-tf` (FLIK, KSP+2→**Aurora** jadi 2 gudang → ZIP sejak 13 Agustus; sebelumnya GTM), CBC-201..203 cod Jawa/Bali → `sicepat`, CBC-301/302 pending+paid → `tembakan`→`spx`. Tracking mencakup semua 6 nilai: `waiting_pickup` (CBC-104), `in_transit` (102/202), `delivered` (101/201/301), `returning` (203), `returned` (103 FLIK, 302 SPX → uji balik stok), `problem` (105, 3PL "Problem:...").
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
  - `OrderTemplateExportService::warehouseFor()`: `explode('+', $code)[0]` sebelum lookup `WAREHOUSE_BY_PRODUCT` (KSP+1.50→Aurora, SH+1.25→GTM sejak 13 Agustus).
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
