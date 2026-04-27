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
        \Illuminate\Support\Facades\Log::info('EventController@store START', [
            'user_id' => $request->user()?->id,
            'template_id' => $request->template_id,
            'data' => $request->all()
        ]);

        $validTemplates = [1, 2, 3, 4, 5];

        $validated = $request->validate([
            'template_id' => [
                'required',
                function ($attribute, $value, $fail) use ($validTemplates) {
                    // On accepte les IDs numériques (anciens) ou les nouveaux IDs de la DB
                    if (!is_numeric($value) && !\App\Models\Template::where('id', $value)->exists()) {
                        $fail('The selected template id is invalid.');
                    }
                },
            ],
            'title' => 'required|string|max:255',
            'event_date' => 'nullable|date',
            'location' => 'nullable|array',
            'locations' => 'nullable|array',
            'event_type' => 'nullable|string',
            'custom_data' => 'nullable|array',
            'data' => 'nullable|array', // Support pour le format Flutter
            'custom_fields' => 'nullable|array',
            'invitation_text' => 'nullable|string|max:1000',
            'cover_image' => 'nullable|image|max:5120', // 5MB max
        ]);

        $user = $request->user();
        \Illuminate\Support\Facades\Log::info('Creating event for user', ['user_id' => $user->id, 'template_id' => $request->template_id]);

        try {
            return DB::transaction(function () use ($request, $user) {
                // Check credits
                $credit = UserCredit::where('user_id', $user->id)
                    ->where('template_id', $request->template_id)
                    ->where('remaining_uses', '>', 0)
                    ->first();

                $eventType = $request->input('event_type', 'custom');
                \Illuminate\Support\Facades\Log::info('Event type selected', ['type' => $eventType]);

                // Handle dynamic image fields
                $imageFields = [];
                $eventTypes = config('event_types');
                $eventTypeConfig = $eventTypes[$eventType] ?? ['fields' => []];

                foreach ($eventTypeConfig['fields'] as $field) {
                    if ($field['type'] === 'image' && $request->hasFile($field['name'])) {
                        $imagePath = $request->file($field['name'])->store('events/photos', 'public');
                        $imageFields[$field['name']] = $imagePath;
                    }
                }

                // Merge data
                $customData = $request->input('custom_data', []);
                if (is_string($customData)) $customData = json_decode($customData, true) ?? [];
                // Log de debug pour voir TOUT ce qui arrive
                \Illuminate\Support\Facades\Log::info('RAW INPUT RECEIVED', $request->all());

                // 1. Fonction récursive pour traiter les fichiers (DOT notation)
                $processFiles = function ($array, $prefix = '') use (&$processFiles, $request) {
                    $result = [];
                    foreach ($array as $key => $value) {
                        $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
                        $file = $request->file($fullKey);
                        
                        if ($file) {
                            $actualFile = is_array($file) ? reset($file) : $file;
                            $result[$key] = $actualFile->store('events/media', 'public');
                        } elseif (is_array($value)) {
                            $result[$key] = $processFiles($value, $fullKey);
                        } else {
                            $result[$key] = $value;
                        }
                    }
                    return $result;
                };

                // 2. Récupérer les données brutes
                $allInput = $request->all();
                
                // 3. Cas critique : Si 'data' est vide mais qu'on a des clés 'data[...]'
                if (empty($allInput['data'])) {
                    foreach ($allInput as $key => $val) {
                        if (str_starts_with($key, 'data[')) {
                            // Transformation précise : data[ch1][title] -> ch1.title
                            $cleanKey = str_replace('data[', '', $key); // ch1][title]
                            $cleanKey = str_replace(']', '', $cleanKey); // ch1[title
                            $cleanKey = str_replace('[', '.', $cleanKey); // ch1.title
                            
                            \Illuminate\Support\Arr::set($allInput['data'], $cleanKey, $val);
                        }
                    }
                }

                $processedInput = $processFiles($allInput);
                $customData = $processedInput['data'] ?? [];

                // Si double nesting data.data
                if (isset($customData['data']) && is_array($customData['data'])) {
                    $customData = array_merge($customData, $customData['data']);
                    unset($customData['data']);
                }

                $title = $processedInput['title'] ?? $customData['title'] ?? 'Sans titre';
                $invitationText = $processedInput['invitation_text'] ?? $customData['invitation_text'] ?? '';
                $eventDate = $processedInput['event_date'] ?? $customData['event_date'] ?? now();

                \Illuminate\Support\Facades\Log::info('FINAL CUSTOM DATA TO SAVE', [
                    'has_ch1' => isset($customData['ch1']),
                    'keys' => array_keys($customData)
                ]);

                $event = Event::create([
                    'owner_id' => $user->id,
                    'template_id' => $request->template_id,
                    'title' => $title,
                    'event_date' => $eventDate,
                    'invitation_text' => $invitationText,
                    'custom_data' => $customData,
                    'status' => 'active',
                    'slug' => \Illuminate\Support\Str::slug($title) . '-' . \Illuminate\Support\Str::random(6),
                    'short_link' => \Illuminate\Support\Str::random(10),
                ]);
                \Illuminate\Support\Facades\Log::info('Event created', ['id' => $event->id]);

                // Guests
                $guests = $request->input('guests');
                if (is_string($guests)) $guests = json_decode($guests, true);

                if (is_array($guests)) {
                    \Illuminate\Support\Facades\Log::info('Processing guests', ['count' => count($guests)]);
                    foreach ($guests as $guestData) {
                        $guest = $event->guests()->create([
                            'whatsapp_number' => $guestData['whatsapp_number'] ?? $guestData['phone'] ?? null,
                            'invitation_token' => Str::uuid(),
                            'status' => 'pending',
                        ]);
                        \App\Jobs\SendWhatsAppInvite::dispatchSync($guest);
                    }
                }

                if ($credit) {
                    $credit->decrement('remaining_uses');
                }

                return response()->json([
                    'message' => 'Événement créé et invitations envoyées !',
                    'event' => $event
                ], 201);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur interne lors de la création.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        if ($request->user()->id !== $event->owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'template_id'     => 'sometimes|exists:templates,id',
            'title'           => 'sometimes|string|max:255',
            'event_date'      => 'sometimes|date',
            'location'        => 'nullable|array',
            'locations'       => 'nullable|array',
            'event_type'      => 'sometimes|string',
            'custom_data'     => 'nullable|array',
            'data'            => 'nullable|array', // format Flutter
            'custom_fields'   => 'nullable|array',
            'invitation_text' => 'nullable|string|max:1000',
            'cover_image'     => 'nullable|image|max:5120',
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
            // Recursive function to handle nested file uploads
            $processNestedFiles = function ($array, $prefix = '') use (&$processNestedFiles, $request) {
                $result = [];
                foreach ($array as $key => $value) {
                    $fullKey = $prefix ? "{$prefix}[{$key}]" : $key;
                    if ($request->hasFile($fullKey)) {
                        $path = $request->file($fullKey)->store('events/media', 'public');
                        $result[$key] = $path;
                    } elseif (is_array($value)) {
                        $result[$key] = $processNestedFiles($value, $fullKey);
                    } else {
                        $result[$key] = $value;
                    }
                }
                return $result;
            };

            // Get all data from request and process files
            $allInput = $request->all();
            $processedInput = $processNestedFiles($allInput);

            // Get event type configuration
            $eventType = $request->input('event_type', $event->event_type);
            $eventTypes = config('event_types');
            $eventTypeConfig = $eventTypes[$eventType] ?? null;

            // Handle root image fields from config
            $imageFields = [];
            if ($eventTypeConfig) {
                foreach ($eventTypeConfig['fields'] as $field) {
                    if ($field['type'] === 'image' && $request->hasFile($field['name'])) {
                        $imagePath = $request->file($field['name'])->store('events/photos', 'public');
                        $imageFields[$field['name']] = $imagePath;
                    }
                }
            }

            // Extract processed data
            $dataFlutter = $processedInput['data'] ?? [];
            $customDataInput = $processedInput['custom_data'] ?? [];
            $customFieldsInput = $processedInput['custom_fields'] ?? [];

            $customMerged = array_merge(
                $event->custom_data ?? [],
                $customDataInput,
                $customFieldsInput,
                $dataFlutter,
                $imageFields
            );

            // Apply scalar updates
            if ($request->has('template_id')) $event->template_id = (int) $request->template_id;
            if ($request->has('event_type')) $event->event_type = $request->event_type;
            if ($request->has('title')) $event->title = $processedInput['title'] ?? $request->title;
            if ($request->has('event_date')) $event->event_date = $processedInput['event_date'] ?? $request->event_date;
            if ($request->has('invitation_text')) $event->invitation_text = $processedInput['invitation_text'] ?? $request->invitation_text;
            if ($request->has('location')) $event->location = $processedInput['location'] ?? $request->location;

            // cover_image update
            $coverImagePath = $processedInput['cover_image'] ?? null;
            if ($coverImagePath) {
                $event->cover_image = $coverImagePath;
            } elseif ($request->hasFile('cover_image')) {
                $event->cover_image = $request->file('cover_image')->store('events/covers', 'public');
            }

            $event->custom_data = $customMerged;
            $event->save();

            return response()->json($event);
        });
    }

    public function stats($slug)
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        $stats = [
            'total' => $event->guests()->count(),
            'confirmed' => $event->guests()->where('rsvp', 'confirmed')->count(),
            'declined' => $event->guests()->where('rsvp', 'declined')->count(),
            'waiting' => $event->guests()->where('rsvp', 'waiting')->count(),
            'total_companions' => $event->guests()->where('rsvp', 'confirmed')->sum('companion_count'),
            'feedbacks' => $event->guests()
                ->whereNotNull('message')
                ->where('message', '!=', '')
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'whatsapp_number', 'rsvp', 'companion_count', 'message', 'updated_at'])
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
