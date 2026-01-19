<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'price_per_pack',
        'preview_image',
        'config_schema',
        'is_active',
    ];

    protected $casts = [
        'config_schema' => 'array',
        'is_active' => 'boolean',
    ];
}
