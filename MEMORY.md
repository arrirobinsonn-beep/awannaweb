# MEMORY — 28 Juli 2026

## Session: Stok Rincian — Akumulasi 1 Row per Tanggal

### Perubahan

**1. StockMovement import — akumulasi per (product_id, gudang, tanggal)**
- File: `app/Http/Controllers/GudangController.php:658`
- `StockMovement::create()` → `firstOrNew()` + increment `barang_keluar`, concat catatan
- KirimanActual tetap dibuat per group (tanggal|dashboard|kurir|jenis), tapi StockMovement digabung

**2. View stok-rincian — 1 baris per tanggal**
- File: `resources/views/gudang/stok-rincian.blade.php`
- Klik tanggal → tampil 1 baris data akumulasi (sum belanja, rts, dll)
- 1 tombol Edit langsung ke halaman edit movement
- Hapus kolom Pilih/checkbox, bulk-delete, selectAllInGroup JS

**3. Edit stok-rincian — gudang & produk readonly**
- File: `resources/views/gudang/stok-rincian-edit.blade.php`
- Gudang & produk jadi `disabled` + hidden input kirim value

**4. Reset data lama**
- Hapus semua StockMovement, KirimanActual, PaketTracking (FK di-null-kan dulu)
- Reset `stok` semua Product ke 0
- Re-import diperlukan

### Branch
- `develop/arif`

### Commit
- `56f61c2` — fix: akumulasi stock movement 1 row per tanggal+gudang+produk, gudang+produk readonly di edit
