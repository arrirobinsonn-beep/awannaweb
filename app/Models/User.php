<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'nama',
        'panggilan',
        'role',
        'nohp',
        'alamat',
        'bank',
        'norek',
        'avatar',
        'advertiser_id',
        'is_active',
        'is_profile_complete',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_profile_complete' => 'boolean',
        ];
    }

    // ─── Relasi ────────────────────────────────────────────────

    public function spendingHarians(): HasMany
    {
        return $this->hasMany(SpendingHarian::class);
    }

    public function topUpProposals(): HasMany
    {
        return $this->hasMany(TopUpProposal::class);
    }

    public function approvedProposals(): HasMany
    {
        return $this->hasMany(TopUpProposal::class, 'approver_id');
    }

    // ─── Relasi Tim ────────────────────────────────────────────

    /** Advertiser yang menjadi 'atasan' dari user CS ini */
    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    /** Daftar CS yang menjadi tim dari advertiser ini */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(User::class, 'advertiser_id');
    }

    // ─── Helper ────────────────────────────────────────────────

    /** Apakah user bisa membuat akun baru (hanya owner & super_admin) */
    public function canCreateUser(): bool
    {
        return $this->hasRole(['owner', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    /** Display name: pakai panggilan jika ada, fallback ke bagian pertama email */
    public function getDisplayNameAttribute(): string
    {
        if ($this->panggilan) {
            return $this->panggilan;
        }
        if ($this->nama) {
            return $this->nama;
        }

        return explode('@', $this->email)[0];
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }
        $name = urlencode($this->display_name);

        return "https://ui-avatars.com/api/?name={$name}&background=FF6B6B&color=fff&bold=true";
    }
}