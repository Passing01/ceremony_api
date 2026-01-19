<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'template_id',
        'title',
        'event_date',
        'location',
        'custom_data',
        'short_link',
        'is_souvenir_enabled',
        'slug',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'location' => 'array',
        'custom_data' => 'array',
        'is_souvenir_enabled' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }
}
