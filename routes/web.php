<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RegionalController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\SpendingHarianController;
use App\Http\Controllers\StockMovementController;
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
        Route::get('/tim/admin/penugasan', [TeamController::class, 'penugasan'])->name('team.penugasan');
        Route::post('/tim/admin/penugasan', [TeamController::class, 'penugasanStore'])->name('team.penugasan.store');
        Route::get('/tim/phone-list', [TeamController::class, 'phoneList'])->name('team.phone-list');

        // Master Gudang (tempat gudang)
        Route::get('/gudang/master', [GudangController::class, 'gudangMaster'])->name('gudang.master');
        Route::post('/gudang/master', [GudangController::class, 'gudangMasterStore'])->name('gudang.master.store');
        Route::delete('/gudang/master/{gudang}', [GudangController::class, 'gudangMasterDestroy'])->name('gudang.master.destroy');

        // Shipment (Import CSV FLIK/SiCepat/SPX)
        Route::get('/pengiriman', [ShipmentController::class, 'index'])->name('shipment.index');
        Route::post('/pengiriman/preview', [ShipmentController::class, 'preview'])->name('shipment.preview');
        Route::post('/pengiriman/import', [ShipmentController::class, 'store'])->name('shipment.import');

        // Purchase (Barang Masuk) & Stock Movement (Jurnal Stok)
        Route::get('/barang-masuk', [PurchaseController::class, 'index'])->name('purchase.index');
        Route::post('/barang-masuk', [PurchaseController::class, 'store'])->name('purchase.store');
        Route::delete('/barang-masuk/{purchase}', [PurchaseController::class, 'destroy'])->name('purchase.destroy');

        // Jurnal Stok
        Route::get('/jurnal-stok', [StockMovementController::class, 'index'])->name('stock-movement.index');
    });
});
