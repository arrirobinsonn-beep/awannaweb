<?php

use App\Http\Controllers\Mobile\MobileTransactionController;
use Illuminate\Support\Facades\Route;

// ─── Mobile API ─────────────────────────────────────────────────────────────
Route::prefix('mobile')->name('mobile.')->middleware('auth.mobile')->group(function () {
    Route::get('transactions', [MobileTransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{id}', [MobileTransactionController::class, 'show'])->name('transactions.show');
    Route::get('transactions/{id}/image', [MobileTransactionController::class, 'image'])->name('transactions.image');
    Route::post('transactions/{id}/confirm', [MobileTransactionController::class, 'confirm'])->name('transactions.confirm');
    Route::post('transactions/{id}/reject', [MobileTransactionController::class, 'reject'])->name('transactions.reject');
});
