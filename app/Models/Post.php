<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'caption', 'visibility'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(PostBookmark::class);
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class);
    }
}
