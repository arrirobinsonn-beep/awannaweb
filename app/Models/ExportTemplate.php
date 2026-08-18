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
    public function mappings(): HasMany
    {
        return $this->hasMany(ExportTemplateMapping::class, 'template', 'key');
    }

    protected $table = 'export_templates';

    protected $fillable = [
        'key',
        'name',
        'couriers',
        'is_active',
    ];

    protected $casts = [
        'couriers' => 'array',
        'is_active' => 'boolean',
    ];
}
