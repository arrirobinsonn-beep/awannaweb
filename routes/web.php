<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderOnlineController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegionalController;
use App\Http\Controllers\SpendingHarianController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TopUpController;
use App\Http\Controllers\UserController;
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
        Route::get('/dashboard/paket-detail', [DashboardController::class, 'paketDetail'])->name('dashboard.paket-detail');
        Route::get('/dashboard/cs', [DashboardController::class, 'dashboardCs'])->name('dashboard.cs');
        Route::get('/dashboard/cs/search-awb', [DashboardController::class, 'csSearchAwb'])->name('dashboard.cs.search-awb');

        // Profil
        Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Supplier
        Route::resource('supplier', SupplierController::class)->names('supplier');

        // Produk
        Route::resource('product', ProductController::class)->names('product');

        // Whitelist
        Route::resource('whitelist', WhitelistController::class)->names('whitelist');

        // Spending Harian
        Route::resource('spending', SpendingHarianController::class)->names('spending');
        Route::patch('/spending/{spending}/approve', [SpendingHarianController::class, 'approve'])->name('spending.approve');

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
        Route::get('/tim/phone-list', [TeamController::class, 'phoneList'])->name('team.phone-list');

        // Gudang (admin)
        Route::get('/gudang/stok', [GudangController::class, 'stok'])->name('gudang.stok');

        // Master Gudang
        Route::get('/gudang/master', [GudangController::class, 'gudangMaster'])->name('gudang.master');
        Route::post('/gudang/master', [GudangController::class, 'gudangMasterStore'])->name('gudang.master.store');
        Route::delete('/gudang/master/{gudang}', [GudangController::class, 'gudangMasterDestroy'])->name('gudang.master.destroy');

        // Master Pembelian Barang
        Route::get('/gudang/pembelian', [GudangController::class, 'pembelian'])->name('gudang.pembelian');
        Route::post('/gudang/pembelian', [GudangController::class, 'pembelianStore'])->name('gudang.pembelian.store');
        Route::get('/gudang/pembelian/{pembelian}/edit', [GudangController::class, 'pembelianEdit'])->name('gudang.pembelian.edit');
        Route::put('/gudang/pembelian/{pembelian}', [GudangController::class, 'pembelianUpdate'])->name('gudang.pembelian.update');
        Route::delete('/gudang/pembelian/{pembelian}', [GudangController::class, 'pembelianDestroy'])->name('gudang.pembelian.destroy');

        // RTS per Hari — Read-only Pivot Report
        Route::get('/gudang/rts-per-hari', [GudangController::class, 'rtsPerHari'])->name('gudang.rts-per-hari');

        // Kiriman Actual
        Route::get('/gudang/kiriman', [GudangController::class, 'kiriman'])->name('gudang.kiriman');
        Route::post('/gudang/kiriman', [GudangController::class, 'kirimanStore'])->name('gudang.kiriman.store');
        Route::post('/gudang/kiriman/excel-preview', [GudangController::class, 'kirimanExcelPreview'])->name('gudang.kiriman.excel-preview');
        Route::post('/gudang/kiriman/excel-import', [GudangController::class, 'kirimanExcelImport'])->name('gudang.kiriman.excel-import');
        Route::get('/gudang/kiriman/{kiriman}/edit', [GudangController::class, 'kirimanEdit'])->name('gudang.kiriman.edit');
        Route::put('/gudang/kiriman/{kiriman}', [GudangController::class, 'kirimanUpdate'])->name('gudang.kiriman.update');
        Route::delete('/gudang/kiriman/{kiriman}', [GudangController::class, 'kirimanDestroy'])->name('gudang.kiriman.destroy');
        Route::post('/gudang/kiriman/dashboard', [GudangController::class, 'kirimanDashboardStore'])->name('gudang.kiriman.dashboard-store');
        Route::delete('/gudang/kiriman/dashboard/{dashboard}', [GudangController::class, 'kirimanDashboardDestroy'])->name('gudang.kiriman.dashboard-destroy');
        Route::post('/gudang/kiriman/excel-undel-preview', [GudangController::class, 'excelUndelPreview'])->name('gudang.kiriman.excel-undel-preview');
        Route::post('/gudang/kiriman/excel-undel-import', [GudangController::class, 'excelUndelImport'])->name('gudang.kiriman.excel-undel-import');

        // Rincian Stok
        Route::get('/gudang/rincian-stok', [GudangController::class, 'stokRincian'])->name('gudang.stok-rincian');
        Route::post('/gudang/rincian-stok', [GudangController::class, 'stokRincianStore'])->name('gudang.stok-rincian.store');
        Route::get('/gudang/rincian-stok/{stockMovement}/edit', [GudangController::class, 'stokRincianEdit'])->name('gudang.stok-rincian.edit');
        Route::put('/gudang/rincian-stok/{stockMovement}', [GudangController::class, 'stokRincianUpdate'])->name('gudang.stok-rincian.update');
        Route::delete('/gudang/rincian-stok/{stockMovement}', [GudangController::class, 'stokRincianDestroy'])->name('gudang.stok-rincian.destroy');
        Route::post('/gudang/rincian-stok/bulk-delete', [GudangController::class, 'stokRincianBulkDelete'])->name('gudang.stok-rincian.bulk-delete');
        Route::post('/gudang/rincian-stok/delete-date', [GudangController::class, 'stokRincianDeleteDate'])->name('gudang.stok-rincian.delete-date');

        // Order Online
        Route::post('/order-online/preview', [OrderOnlineController::class, 'preview'])->name('order-online.preview');
        Route::post('/order-online/import', [OrderOnlineController::class, 'import'])->name('order-online.import');

        // Backfill handle_by for existing PaketTracking
        Route::post('/gudang/backfill-handle-by', [GudangController::class, 'backfillHandleBy'])->name('gudang.backfill-handle-by');

        // Rekap Stok Barang (GUDANG KUNINGAN)
        Route::get('/gudang/rekap-stok', [GudangController::class, 'rekapStok'])->name('gudang.rekap-stok');
        Route::post('/gudang/rekap-stok', [GudangController::class, 'rekapStokBulk'])->name('gudang.rekap-stok.bulk');
    });
});
