<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transaksi keuangan masuk/keluar per akun. CS meng-upload bukti (image_url)
 * untuk type=in → status pending → di-approve/reject role keuangan/owner.
 *
 * Hanya status approved yang mengubah current_balance (via FinanceService).
 * Saat approved, file bukti gambar DIHAPUS dari disk (image_url di-null).
 * Saat rejected, rejection_note berisi feedback untuk CS.
 */
class BankTransfer extends Model
{
    public const TYPES = ['in', 'out'];

    public const STATUSES = ['pending', 'approved', 'rejected'];

    public const STATUS_LABELS = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    protected $table = 'bank_transfers';

    protected $fillable = [
        'account_id', 'category_id', 'product_id', 'order_online_id', 'type', 'amount', 'description',
        'transaction_date', 'created_by', 'image_url', 'status', 'rejection_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
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
