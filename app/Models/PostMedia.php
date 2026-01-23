<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'post_id', 'type', 'path', 'thumbnail', 'meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
