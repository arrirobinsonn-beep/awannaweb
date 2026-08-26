<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transaksi keuangan masuk/keluar per akun.
 *
 * Alur: CS upload → pending → pemilik bank confirm → confirmed → guru approve → approved.
 * Gambar tidak pernah dihapus otomatis — hanya manual oleh guru.
 * Hanya status approved yang mengubah current_balance (via FinanceService).
 */
class BankTransfer extends Model
{
    public const TYPES = ['in', 'out'];

    public const STATUSES = ['pending', 'confirmed', 'approved', 'rejected'];

    public const STATUS_LABELS = [
        'pending' => 'Menunggu Konfirmasi',
        'confirmed' => 'Sudah Dikonfirmasi',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    protected $table = 'bank_transfers';

    protected $fillable = [
        'account_id', 'category_id', 'product_id', 'order_online_id', 'type', 'amount', 'description',
        'transaction_date', 'created_by', 'image_url', 'status', 'rejection_note',
        'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
