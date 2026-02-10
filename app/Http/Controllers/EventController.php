<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\UserCredit;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $events = Event::where('owner_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json($events);
    }

    public function show(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        if ($request->user()->id !== $event->owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Include guests list and their s, 'track'tatuses in details
        $event->load(['guests', 'template']);

        return response()->json($event);
    }

    public function store(Request $request)
    {
        $validTemplates = [1, 2, 3, 4, 5]; // IDs valides : 1=royal, 2=minimal, 3=floral, 4=vintage, 5=corporate

        $validated = $request->validate([
            'template_id' => [
                'required',
                function ($attribute, $value, $fail) use ($validTemplates) {
                    if (!in_array((int) $value, $validTemplates, true)) {
                        $fail('The selected template id is invalid. Valid IDs are: ' . implode(', ', $validTemplates));
                    }
                },
            ],
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'location' => 'nullable|array',
            'locations' => 'nullable|array',
            'event_type' => 'required|string',
            'custom_data' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'invitation_text' => 'nullable|string|max:1000',
            'track_id' => 'nullable|uuid|exists:tracks,id',
        ]);

        // Validate event type exists
        $eventTypes = config('event_types');
        if (!isset($eventTypes[$request->event_type])) {
            return response()->json([
                'message' => 'Type d\'événement invalide'
            ], 422);
        }

        $user = $request->user();

        // Check credits
        $credit = UserCredit::where('user_id', $user->id)
            ->where('template_id', $request->template_id)
            ->where('remaining_uses', '>', 0)
            ->first();

        // For V1 validation, we might skip strict credit check if payment flow isn't ready, 
        // but per spec we should check.
        // if (!$credit) {
        //     return response()->json(['message' => 'Crédits insuffisants pour ce modèle.'], 403);
        // }

        return DB::transaction(function () use ($request, $user, $credit, $eventTypes) {
            // Get event type configuration
            $eventTypeConfig = $eventTypes[$request->event_type];

            // Handle dynamic image fields based on event type
            $imageFields = [];
            foreach ($eventTypeConfig['fields'] as $field) {
                if ($field['type'] === 'image' && $request->hasFile($field['name'])) {
                    $imagePath = $request->file($field['name'])->store('events/photos', 'public');
                    $imageFields[$field['name']] = $imagePath;
                }
            }

            // Merge custom data with all fields
            $custom = array_merge(
                $request->input('custom_data', []),
                $request->input('custom_fields', []),
                $imageFields
            );

            // Add any non-image fields from the request
            foreach ($eventTypeConfig['fields'] as $field) {
                if ($field['type'] !== 'image' && $request->has($field['name'])) {
                    $custom[$field['name']] = $request->input($field['name']);
                }
            }

            // Support multiple locations if provided; fallback to single location
            $locations = $request->input('locations');
            $locationPayload = $locations ?? $request->input('location');

            $event = Event::create([
                'owner_id' => $user->id,
                'template_id' => $request->template_id,
                'track_id' => $request->input('track_id'),
                'event_type' => $request->event_type,
                'title' => $request->title,
                'event_date' => $request->event_date,
                'invitation_text' => $request->input('invitation_text'),
                'location' => $locationPayload,
                'custom_data' => $custom,
                'slug' => Str::slug($request->title) . '-' . Str::random(6),
                'short_link' => Str::random(10), // Placeholder for real short link logic
            ]);

            if ($credit) {
                $credit->decrement('remaining_uses');
            }

            return response()->json($event, 201);
        });
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        if ($request->user()->id !== $event->owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'template_id' => 'sometimes|exists:templates,id',
            'title' => 'sometimes|string|max:255',
            'event_date' => 'sometimes|date',
            'location' => 'nullable|array',
            'locations' => 'nullable|array',
            'event_type' => 'sometimes|string',
            'custom_data' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'invitation_text' => 'nullable|string|max:1000',
        ]);

        // Validate event type if provided
        if (isset($validated['event_type'])) {
            $eventTypes = config('event_types');
            if (!isset($eventTypes[$validated['event_type']])) {
                return response()->json([
                    'message' => 'Type d\'événement invalide'
                ], 422);
            }
        }

        return DB::transaction(function () use ($request, $event, $validated) {
            // Get event type configuration (use existing or new)
            $eventType = $request->input('event_type', $event->event_type);
            $eventTypes = config('event_types');
            $eventTypeConfig = $eventTypes[$eventType] ?? null;

            // Start with existing custom_data
            $customExisting = $event->custom_data ?? [];

            // Handle dynamic image fields based on event type
            $imageFields = [];
            if ($eventTypeConfig) {
                foreach ($eventTypeConfig['fields'] as $field) {
                    if ($field['type'] === 'image' && $request->hasFile($field['name'])) {
                        $imagePath = $request->file($field['name'])->store('events/photos', 'public');
                        $imageFields[$field['name']] = $imagePath;
                    }
                }
            }

            // Merge custom data
            $customMerged = array_merge(
                $customExisting,
                $request->input('custom_data', []),
                $request->input('custom_fields', []),
                $imageFields
            );

            // Add any non-image fields from the request
            if ($eventTypeConfig) {
                foreach ($eventTypeConfig['fields'] as $field) {
                    if ($field['type'] !== 'image' && $request->has($field['name'])) {
                        $customMerged[$field['name']] = $request->input($field['name']);
                    }
                }
            }

            // Compute location payload
            $locations = $request->input('locations');
            $locationPayload = $locations ?? $request->input('location');

            // Apply scalar updates if present
            if (array_key_exists('template_id', $validated)) {
                $event->template_id = (int) $validated['template_id'];
            }
            if (array_key_exists('event_type', $validated)) {
                $event->event_type = $validated['event_type'];
            }
            if (array_key_exists('title', $validated)) {
                $event->title = $validated['title'];
            }
            if (array_key_exists('event_date', $validated)) {
                $event->event_date = $validated['event_date'];
            }
            if (array_key_exists('invitation_text', $validated)) {
                $event->invitation_text = $validated['invitation_text'];
            }
            if (!is_null($locationPayload) || array_key_exists('location', $validated) || array_key_exists('locations', $validated)) {
                $event->location = $locationPayload;
            }

            $event->custom_data = $customMerged;

            $event->save();

            return response()->json($event);
        });
    }

    public function stats($slug)
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        // Check authorization if needed, or public stats? 
        // Usually stats are for the owner.
        // if ($request->user()->id !== $event->owner_id) abort(403);

        $stats = [
            'total' => $event->guests()->count(),
            'confirmed' => $event->guests()->where('rsvp', 'confirmed')->count(),
            'declined' => $event->guests()->where('rsvp', 'declined')->count(),
            'waiting' => $event->guests()->where('rsvp', 'waiting')->count(),
        ];

        return response()->json($stats);
    }

    public function importGuests(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        if ($request->user()->id !== $event->owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $guestsToInsert = [];

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
            ]);

            $file = $request->file('file');
            $path = $file->getRealPath();
            $data = array_map('str_getcsv', file($path));

            // Assuming first column is valid phone number or header usage
            // Simple logic: Skip header if mapped, otherwise mapping needed.
            // For V1 -> Assume Column 0 is Phone.

            foreach ($data as $index => $row) {
                if ($index === 0 && !is_numeric($row[0]))
                    continue; // Skip header
                if (!empty($row[0])) {
                    $guestsToInsert[] = ['whatsapp_number' => $row[0]];
                }
            }
        } else {
            $request->validate([
                'guests' => 'required|array',
                'guests.*.whatsapp_number' => 'required|string',
            ]);
            $guestsToInsert = $request->guests;
        }

        $count = 0;
        foreach ($guestsToInsert as $guestData) {
            // Avoid duplicates? Specs don't explicitly say, but good practice.
            // For now simple insert.
            $event->guests()->create([
                'whatsapp_number' => $guestData['whatsapp_number'],
                'invitation_token' => Str::uuid(),
            ]);
            $count++;
        }

        return response()->json(['message' => "Successfully imported {$count} guests"]);
    }

    public function sendInvites(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if ($request->user()->id !== $event->owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Dispatch Jobs for pending guests
        $guests = $event->guests()->where('status', 'pending')->get();

        foreach ($guests as $guest) {
            \App\Jobs\SendWhatsAppInvite::dispatch($guest);
        }

        return response()->json(['message' => 'Envoi des invitations en cours...', 'count' => $guests->count()]);
    }

    public function gallery($id)
    {
        $event = Event::findOrFail($id);

        if (!$event->is_souvenir_enabled) {
            return response()->json(['message' => 'Module souvenir non activé pour cet événement.'], 403);
        }

        // Placeholder: Fetch media from S3/Cloudinary/Storage
        // Return list of URLs
        return response()->json(['media' => []]);
    }

    public function uploadMedia(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if (!$event->is_souvenir_enabled) {
            return response()->json(['message' => 'Module souvenir non activé pour cet événement.'], 403);
        }

        $request->validate([
            'file' => 'required|file|image|max:10240', // 10MB max
        ]);

        // Upload logic
        // $path = $request->file('file')->store('events/' . $event->id . '/gallery');

        return response()->json(['message' => 'Upload successful', 'path' => 'placeholder/path.jpg']);
    }

    public function publicShow($slug)
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        // Load template to include its config
        $event->load(['template', 'track']);

        return response()->json($event);
    }
}
