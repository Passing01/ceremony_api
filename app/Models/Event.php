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
        'track_id',
        'event_type',
        'title',
        'event_date',
        'location',
        'custom_data',
        'invitation_text',
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

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = parent::toArray();

        // Add dynamic image URL fields based on event type
        $eventTypes = config('event_types');
        if ($this->event_type && isset($eventTypes[$this->event_type])) {
            foreach ($eventTypes[$this->event_type]['fields'] as $field) {
                if ($field['type'] === 'image') {
                    $key = $field['name'];
                    $urlKey = $key . '_url';

                    if (isset($this->custom_data[$key]) && $this->custom_data[$key]) {
                        $attributes[$urlKey] = url('storage/' . $this->custom_data[$key]);
                    } else {
                        $attributes[$urlKey] = null;
                    }
                }
            }
        }

        // Add template config if template is loaded
        if ($this->relationLoaded('template')) {
            $attributes['template_config'] = $this->template ? $this->template->config_schema : null;
        }

        return $attributes;
    }

    /**
     * Dynamically get image URLs (for code access)
     */
    public function __get($key)
    {
        if (str_ends_with($key, '_url')) {
            $fieldName = substr($key, 0, -4);
            $eventTypes = config('event_types');

            // Basic check if field exists in custom_data
            if (isset($this->custom_data[$fieldName]) && $this->custom_data[$fieldName]) {
                return url('storage/' . $this->custom_data[$fieldName]);
            }
            return null;
        }

        return parent::__get($key);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }
}
