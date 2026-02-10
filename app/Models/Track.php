<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'provider',
        'title',
        'artist',
        'duration_ms',
        'path',
        'mime_type',
        'size_bytes',
        'is_public',
        'status',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'duration_ms' => 'integer',
        'size_bytes' => 'integer',
    ];

    protected $appends = [
        'audio_url',
    ];

    public function getAudioUrlAttribute()
    {
        if (!$this->path) {
            return null;
        }

        return url('storage/' . $this->path);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
