<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master template export (courier). `export_template_mappings.template`
 * menyimpan `key` dari tabel ini (relasi string, bukan FK).
 */
class ExportTemplate extends Model
{
    /** Mode file export: 1 file langsung. */
    public const SPLIT_SINGLE = 'single';

    /** Mode file export: split per gudang dari kolom `warehouse` data mentah (CSV). */
    public const SPLIT_CSV = 'split_csv';

    /** Mode file export: split per gudang dari aturan `warehouse_rules` (/warehouse-rules). */
    public const SPLIT_RULES = 'split_rules';

    /** Semua mode split yang valid (untuk validasi & dropdown UI). */
    public const SPLIT_MODES = [
        self::SPLIT_SINGLE => '1 File (semua gudang digabung)',
        self::SPLIT_CSV => 'Dipisah — nama warehouse dari data mentah (CSV)',
        self::SPLIT_RULES => 'Dipisah — aturan gudang (halaman Aturan Gudang)',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(ExportTemplateMapping::class, 'template', 'key');
    }

    protected $table = 'export_templates';

    protected $fillable = [
        'key',
        'name',
        'couriers',
        'split_mode',
        'is_active',
    ];

    protected $casts = [
        'couriers' => 'array',
        'is_active' => 'boolean',
    ];
}
