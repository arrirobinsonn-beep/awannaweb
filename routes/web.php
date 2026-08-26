<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankStatementController;
use App\Http\Controllers\BankTransferController;
use App\Http\Controllers\CourierRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportMappingController;
use App\Http\Controllers\FinanceAccountController;
use App\Http\Controllers\FinanceCategoryController;
use App\Http\Controllers\FinanceTransferController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperationalReportController;
use App\Http\Controllers\OrderOnlineBatchController;
use App\Http\Controllers\OrderOnlineController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RegionalController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\SpendingHarianController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TopUpController;
use App\Http\Controllers\TrackingStatusRuleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MobileDeviceController;
use App\Http\Controllers\WarehouseRuleController;
use App\Http\Controllers\WhitelistController;
use Illuminate\Support\Facades\Route;

// ─── Guest ───────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Authenticated ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Complete-profile (boleh diakses sebelum profil lengkap)
    Route::get('/complete-profile', [ProfileController::class, 'showCompleteProfile'])->name('profile.complete');
    Route::post('/complete-profile', [ProfileController::class, 'storeCompleteProfile'])->name('profile.complete.store');

    // ── Semua route butuh profil lengkap ──────────────────────
    Route::middleware('profile.complete')->group(function () {

        Route::get('/', fn () => redirect()->route('dashboard'));

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Laporan Operasional (barang keluar/masuk, resi, metode bayar per pengirim)
        Route::get('/laporan-operasional', [OperationalReportController::class, 'index'])->name('operational-report.index');
        Route::get('/laporan-operasional/{batch}', [OperationalReportController::class, 'show'])->name('operational-report.batch');

        // Profil
        Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Supplier
        Route::resource('supplier', SupplierController::class)->names('supplier');

        // Produk & Varian — dikelola DI DALAM halaman Gudang (inventory otomatis = gudang yang dibuka)

        // Aturan Courier (auto-mapping kurir berdasarkan provinsi — dinamis dari DB)
        Route::get('/courier-rules', [CourierRuleController::class, 'index'])->name('courier-rule.index');
        Route::post('/courier-rules', [CourierRuleController::class, 'store'])->name('courier-rule.store');
        Route::put('/courier-rules/{courierRule}', [CourierRuleController::class, 'update'])->name('courier-rule.update');
        Route::patch('/courier-rules/{courierRule}/toggle', [CourierRuleController::class, 'toggle'])->name('courier-rule.toggle');
        Route::post('/courier-rules/{courierRule}/move/{direction}', [CourierRuleController::class, 'move'])->name('courier-rule.move');
        Route::delete('/courier-rules/{courierRule}', [CourierRuleController::class, 'destroy'])->name('courier-rule.destroy');

        // Aturan Gudang (kode produk → gudang/nama pengirim saat export — dinamis dari DB)
        Route::get('/warehouse-rules', [WarehouseRuleController::class, 'index'])->name('warehouse-rule.index');
        Route::post('/warehouse-rules', [WarehouseRuleController::class, 'store'])->name('warehouse-rule.store');
        Route::put('/warehouse-rules/{warehouseRule}', [WarehouseRuleController::class, 'update'])->name('warehouse-rule.update');
        Route::patch('/warehouse-rules/{warehouseRule}/toggle', [WarehouseRuleController::class, 'toggle'])->name('warehouse-rule.toggle');
        Route::delete('/warehouse-rules/{warehouseRule}', [WarehouseRuleController::class, 'destroy'])->name('warehouse-rule.destroy');

        // Aturan Status Aggregator — per dashboard: mapping header CSV → kolom DB + raw status → status sistem
        Route::get('/tracking-status-rules', [TrackingStatusRuleController::class, 'index'])->name('tracking-status-rule.index');
        Route::get('/tracking-status-rules/{source}/edit', [TrackingStatusRuleController::class, 'edit'])->name('tracking-status-rule.edit');
        Route::post('/tracking-status-rules/upload', [TrackingStatusRuleController::class, 'upload'])->name('tracking-status-rule.upload');
        Route::post('/tracking-status-rules/{source}/mapping', [TrackingStatusRuleController::class, 'saveMapping'])->name('tracking-status-rule.mapping');
        Route::post('/tracking-status-rules/{source}/config', [TrackingStatusRuleController::class, 'saveConfig'])->name('tracking-status-rule.config');
        Route::post('/tracking-status-rules', [TrackingStatusRuleController::class, 'store'])->name('tracking-status-rule.store');
        Route::put('/tracking-status-rules/{trackingStatusRule}', [TrackingStatusRuleController::class, 'update'])->name('tracking-status-rule.update');
        Route::patch('/tracking-status-rules/{trackingStatusRule}/toggle', [TrackingStatusRuleController::class, 'toggle'])->name('tracking-status-rule.toggle');
        Route::post('/tracking-status-rules/{trackingStatusRule}/move/{direction}', [TrackingStatusRuleController::class, 'move'])->name('tracking-status-rule.move');
        Route::delete('/tracking-status-rules/{trackingStatusRule}', [TrackingStatusRuleController::class, 'destroy'])->name('tracking-status-rule.destroy');

        // Aturan Export (template dinamis: index daftar, create/edit terpisah, hapus permanen)
        Route::get('/export-mapping', [ExportMappingController::class, 'index'])->name('export-mapping.index');
        Route::get('/export-mapping/create', [ExportMappingController::class, 'create'])->name('export-mapping.create');
        Route::post('/export-mapping', [ExportMappingController::class, 'store'])->name('export-mapping.store');
        Route::get('/export-mapping/{exportTemplate}/edit', [ExportMappingController::class, 'edit'])->name('export-mapping.edit');
        Route::put('/export-mapping/{exportTemplate}', [ExportMappingController::class, 'update'])->name('export-mapping.update');
        Route::delete('/export-mapping/{exportTemplate}', [ExportMappingController::class, 'destroy'])->name('export-mapping.destroy');
        Route::post('/export-mapping/upload', [ExportMappingController::class, 'upload'])->name('export-mapping.upload');

        // Whitelist
        Route::resource('whitelist', WhitelistController::class)->names('whitelist');

        // Spending Harian
        Route::resource('spending', SpendingHarianController::class)->names('spending');


        Route::patch('/spending/{spending}/approve', [SpendingHarianController::class, 'approve'])->name('spending.approve');
        Route::post('/spending/change-date', [SpendingHarianController::class, 'changeDate'])->name('spending.change-date');
        Route::post('/spending/bulk-delete', [SpendingHarianController::class, 'bulkDestroy'])->name('spending.bulk-destroy');
        Route::post('/spending/bulk-update', [SpendingHarianController::class, 'bulkUpdate'])->name('spending.bulk-update');
        Route::post('/spending/parse-upload', [SpendingHarianController::class, 'parseUpload'])->name('spending.parse-upload');


        // Top Up
        Route::get('/top-up', [TopUpController::class, 'index'])->name('topup.index');
        Route::get('/top-up/create', [TopUpController::class, 'create'])->name('topup.create');
        Route::post('/top-up', [TopUpController::class, 'store'])->name('topup.store');
        Route::get('/top-up/{proposal}', [TopUpController::class, 'show'])->name('topup.show');
        Route::patch('/top-up/{proposal}/approve', [TopUpController::class, 'approve'])->name('topup.approve');
        Route::patch('/top-up/{proposal}/decline', [TopUpController::class, 'decline'])->name('topup.decline');
        Route::get('/top-up/{proposal}/pay', [TopUpController::class, 'paymentForm'])->name('topup.payment');
        Route::post('/top-up/{proposal}/pay', [TopUpController::class, 'paymentStore'])->name('topup.payment.store');
        Route::patch('/top-up/{proposal}/va-paid', [TopUpController::class, 'markVaPaid'])->name('topup.va-paid');
        Route::get('/top-up/{proposal}/confirm', [TopUpController::class, 'confirmForm'])->name('topup.confirm');
        Route::post('/top-up/{proposal}/confirm', [TopUpController::class, 'confirmStore'])->name('topup.confirm.store');

        // Regional
        Route::get('/regional', [RegionalController::class, 'index'])->name('regional.index');
        Route::post('/regional/preview', [RegionalController::class, 'preview'])->name('regional.preview');
        Route::post('/regional/save', [RegionalController::class, 'savePreview'])->name('regional.save');
        Route::post('/regional/update-cell', [RegionalController::class, 'updateCell'])->name('regional.update-cell');
        Route::post('/regional/delete-cell', [RegionalController::class, 'deleteCell'])->name('regional.delete-cell');
        Route::post('/regional/check-existing', [RegionalController::class, 'checkExistingDates'])->name('regional.check-existing');
        Route::get('/regional/check', [RegionalController::class, 'checkDiscrepancy'])->name('regional.check');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');

        // User Management
        Route::resource('user', UserController::class)->names('user');
        Route::patch('/user/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('user.toggle-active');

        // Tim / Team
        Route::get('/tim', [TeamController::class, 'index'])->name('team.index');
        Route::get('/tim/performa', [TeamController::class, 'performance'])->name('team.performance');
        Route::get('/tim/admin', [TeamController::class, 'adminIndex'])->name('team.admin-index');
        Route::get('/tim/admin/penugasan', [TeamController::class, 'penugasan'])->name('team.penugasan');
        Route::post('/tim/admin/penugasan', [TeamController::class, 'penugasanStore'])->name('team.penugasan.store');
        Route::get('/tim/phone-list', [TeamController::class, 'phoneList'])->name('team.phone-list');

        // Master Inventory (gudang)
        Route::get('/inventory/master', [InventoryController::class, 'master'])->name('inventory.master');
        Route::post('/inventory/master', [InventoryController::class, 'masterStore'])->name('inventory.master.store');
        Route::delete('/inventory/master/{inventory}', [InventoryController::class, 'masterDestroy'])->name('inventory.master.destroy');

        // Master Produk — halaman produk sendiri (CRUD produk & varian).
        // Produk dibuat DI SINI; halaman Gudang hanya meng-attach produk yang sudah ada.
        Route::get('/product', [ProductController::class, 'index'])->name('product.index');
        Route::post('/product', [ProductController::class, 'store'])->name('product.store');
        Route::put('/product/{product}', [ProductController::class, 'update'])->name('product.update');
        Route::delete('/product/{product}', [ProductController::class, 'destroy'])->name('product.destroy');
        Route::patch('/product/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('product.toggle-status');
        Route::post('/product/{product}/variants', [ProductController::class, 'variantStore'])->name('product.variant.store');
        Route::put('/product/variants/{variant}', [ProductController::class, 'variantUpdate'])->name('product.variant.update');
        Route::delete('/product/variants/{variant}', [ProductController::class, 'variantDestroy'])->name('product.variant.destroy');
        Route::patch('/product/variants/{variant}/toggle-status', [ProductController::class, 'toggleVariantStatus'])->name('product.variant.toggle-status');

        // Gudang (stok per kategori + aturan kemasan dinamis + keanggotaan produk)
        Route::get('/gudang', [GudangController::class, 'index'])->name('gudang.index');
        Route::post('/gudang/adjust', [GudangController::class, 'adjust'])->name('gudang.adjust');
        Route::post('/gudang/packaging-rules', [GudangController::class, 'packagingStore'])->name('gudang.packaging-store');
        Route::put('/gudang/packaging-rules/{packagingRule}', [GudangController::class, 'packagingUpdate'])->name('gudang.packaging-update');
        Route::delete('/gudang/packaging-rules/{packagingRule}', [GudangController::class, 'packagingDestroy'])->name('gudang.packaging-destroy');

        // Produk di gudang — attach produk MASTER yang sudah ada (bukan buat baru),
        // kelola gudang (many-to-many + primary), dan lepas dari gudang.
        Route::post('/gudang/products', [GudangController::class, 'productAttach'])->name('gudang.product.attach');
        Route::put('/gudang/products/{product}/warehouses', [GudangController::class, 'productWarehousesUpdate'])->name('gudang.product.warehouses');
        Route::delete('/gudang/products/{product}', [GudangController::class, 'productDetach'])->name('gudang.product.detach');

        // Shipment (Import CSV FLIK/SiCepat/SPX)
        Route::get('/pengiriman', [ShipmentController::class, 'index'])->name('shipment.index');
        Route::post('/pengiriman/preview', [ShipmentController::class, 'preview'])->name('shipment.preview');
        Route::post('/pengiriman/import', [ShipmentController::class, 'store'])->name('shipment.import');

        // Order Online (Data Mentah + Export Template Excel)
        Route::get('/orders', [OrderOnlineController::class, 'index'])->name('orders.index');
        Route::post('/orders/preview', [OrderOnlineController::class, 'preview'])->name('orders.preview');
        Route::post('/orders/import', [OrderOnlineController::class, 'store'])->name('orders.import');
        Route::post('/orders/tracking-import', [OrderOnlineController::class, 'trackingImport'])->name('orders.tracking-import');
        Route::put('/orders/{shippingOrder}', [OrderOnlineController::class, 'update'])->name('orders.update');
        Route::get('/orders/{shippingOrder}', [OrderOnlineController::class, 'show'])->name('orders.show');
        Route::get('/orders/{batch}/export/{template}/{courier?}', [OrderOnlineController::class, 'export'])->name('orders.export');

        // Riwayat Batch Import Order Online
        Route::get('/order-batches', [OrderOnlineBatchController::class, 'index'])->name('order-batch.index');
        Route::delete('/order-batches/{batch}', [OrderOnlineBatchController::class, 'destroy'])->name('order-batch.destroy');

        // Purchase (Barang Masuk) & Stock Movement (Jurnal Stok)
        Route::get('/barang-masuk', [PurchaseController::class, 'index'])->name('purchase.index');
        Route::post('/barang-masuk', [PurchaseController::class, 'store'])->name('purchase.store');
        Route::delete('/barang-masuk/{purchase}', [PurchaseController::class, 'destroy'])->name('purchase.destroy');

        // Jurnal Stok
        Route::get('/jurnal-stok', [StockMovementController::class, 'index'])->name('stock-movement.index');

        // ── Mobile Devices (manajemen credential mobile API) ──
        Route::get('/mobile-devices', [MobileDeviceController::class, 'index'])->name('mobile-device.index');
        Route::post('/mobile-devices', [MobileDeviceController::class, 'store'])->name('mobile-device.store');
        Route::put('/mobile-devices/{mobileDevice}', [MobileDeviceController::class, 'update'])->name('mobile-device.update');
        Route::delete('/mobile-devices/{mobileDevice}', [MobileDeviceController::class, 'destroy'])->name('mobile-device.destroy');
        Route::patch('/mobile-devices/{mobileDevice}/toggle', [MobileDeviceController::class, 'toggle'])->name('mobile-device.toggle');
        Route::post('/mobile-devices/{mobileDevice}/regenerate', [MobileDeviceController::class, 'regenerate'])->name('mobile-device.regenerate');

        // ── Keuangan (akun, kategori, transfer antar akun, bukti transfer) ──
        Route::prefix('keuangan')->name('finance.')->group(function () {
            Route::resource('akun', FinanceAccountController::class)
                ->except(['show'])
                ->parameters(['akun' => 'account'])
                ->names('accounts');
            Route::patch('/akun/{account}/toggle', [FinanceAccountController::class, 'toggle'])->name('accounts.toggle');

            Route::resource('kategori', FinanceCategoryController::class)
                ->except(['show'])
                ->parameters(['kategori' => 'category'])
                ->names('categories');

            Route::get('transfer', [FinanceTransferController::class, 'index'])->name('transfers.index');
            Route::post('transfer', [FinanceTransferController::class, 'store'])->name('transfers.store');
            Route::delete('transfer/{transfer}', [FinanceTransferController::class, 'destroy'])->name('transfers.destroy');

            Route::get('bukti-transfer', [BankTransferController::class, 'index'])->name('bank-transfers.index');
            Route::get('rekening-koran', [BankStatementController::class, 'index'])->name('bank-statement.index');
            Route::get('rekening-koran/pdf', [BankStatementController::class, 'downloadPdf'])->name('bank-statement.pdf');
            Route::get('bukti-transfer/pending-count', [BankTransferController::class, 'pendingCount'])->name('bank-transfers.pending-count');
            Route::post('bukti-transfer', [BankTransferController::class, 'store'])->name('bank-transfers.store');
            Route::post('bukti-transfer/{bankTransfer}/confirm', [BankTransferController::class, 'confirm'])->name('bank-transfers.confirm');
            Route::post('bukti-transfer/{bankTransfer}/approve', [BankTransferController::class, 'approve'])->name('bank-transfers.approve');
            Route::post('bukti-transfer/{bankTransfer}/reject', [BankTransferController::class, 'reject'])->name('bank-transfers.reject');
            Route::delete('bukti-transfer/{bankTransfer}/image', [BankTransferController::class, 'deleteImage'])->name('bank-transfers.delete-image');
            Route::delete('bukti-transfer/{bankTransfer}', [BankTransferController::class, 'destroy'])->name('bank-transfers.destroy');
            Route::get('bukti-transfer/{bankTransfer}/download', [BankTransferController::class, 'download'])->name('bank-transfers.download');
        });
    });
});
