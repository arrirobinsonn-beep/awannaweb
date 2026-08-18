<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportTemplateMapping extends Model
{
    protected $table = 'export_template_mappings';

    protected $fillable = [
        'template',
        'column_index',
        'header',
        'source_type',
        'source_value',
        'is_active',
    ];

    protected $casts = [
        'column_index' => 'integer',
        'is_active' => 'boolean',
    ];
}
