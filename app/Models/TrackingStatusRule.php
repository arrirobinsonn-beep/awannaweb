<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Aturan dinamis mapping raw status dashboard aggregator → aggregator_status.
 *
 * Lihat migration 2026_08_13_150000 untuk penjelasan kolom. Dikelola admin
 * lewat halaman "Aturan Status" — pengganti map hardcoded di service import.
 */
class TrackingStatusRule extends Model
{
    protected $table = 'tracking_status_rules';

    public const SOURCES = ['flik', 'sicepat', 'spx'];

    public const MATCH_TYPES = ['exact', 'contains'];

    public const PROBLEM_MODES = ['none', 'required'];

    /** Cara mencocokkan KOLOM MASALAH terhadap keyword (problem_match_type). */
    public const PROBLEM_MATCH_TYPES = ['contains', 'starts_with'];

    protected $fillable = [
        'source',
        'raw_status',
        'match_type',
        'status',
        'problem_mode',
        'problem_keyword',
        'problem_match_type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
