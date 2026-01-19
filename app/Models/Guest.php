<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'event_id',
        'whatsapp_number',
        'invitation_token',
        'status',
        'rsvp',
        'check_in_at',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
