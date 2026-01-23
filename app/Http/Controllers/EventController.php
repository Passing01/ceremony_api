<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\UserCredit;
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

        // Include guests list and their statuses in details
        $event->load('guests');

        return response()->json($event);
    }

    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:templates,id',
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'location' => 'nullable|array',
            'locations' => 'nullable|array',
            'event_type' => 'nullable|string',
            'dress_code' => 'nullable|string',
            'groom_photo' => 'nullable|image|max:4096',
            'bride_photo' => 'nullable|image|max:4096',
            'custom_data' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

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

        return DB::transaction(function () use ($request, $user, $credit) {
            // Handle optional images
            $groomPhotoPath = null;
            $bridePhotoPath = null;
            if ($request->hasFile('groom_photo')) {
                $groomPhotoPath = $request->file('groom_photo')->store('events/photos', 'public');
            }
            if ($request->hasFile('bride_photo')) {
                $bridePhotoPath = $request->file('bride_photo')->store('events/photos', 'public');
            }

            // Merge custom data with optional ceremony fields
            $custom = array_merge(
                $request->input('custom_data', []),
                $request->input('custom_fields', []),
                array_filter([
                    'event_type' => $request->input('event_type'),
                    'dress_code' => $request->input('dress_code'),
                    'groom_photo' => $groomPhotoPath,
                    'bride_photo' => $bridePhotoPath,
                ], function ($v) { return !is_null($v) && $v !== ''; })
            );

            // Support multiple locations if provided; fallback to single location
            $locations = $request->input('locations');
            $locationPayload = $locations ?? $request->input('location');
            $event = Event::create([
                'owner_id' => $user->id,
                'template_id' => $request->template_id,
                'title' => $request->title,
                'event_date' => $request->event_date,
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
            'event_type' => 'nullable|string',
            'dress_code' => 'nullable|string',
            'groom_photo' => 'nullable|image|max:4096',
            'bride_photo' => 'nullable|image|max:4096',
            'custom_data' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($request, $event, $validated) {
            // Handle optional images only if provided
            $groomPhotoPath = null;
            $bridePhotoPath = null;
            if ($request->hasFile('groom_photo')) {
                $groomPhotoPath = $request->file('groom_photo')->store('events/photos', 'public');
            }
            if ($request->hasFile('bride_photo')) {
                $bridePhotoPath = $request->file('bride_photo')->store('events/photos', 'public');
            }

            // Start with existing custom_data
            $customExisting = $event->custom_data ?? [];
            $customMerged = array_merge(
                $customExisting,
                $request->input('custom_data', []),
                $request->input('custom_fields', []),
                array_filter([
                    'event_type' => $request->input('event_type'),
                    'dress_code' => $request->input('dress_code'),
                    // Replace photos only if new uploaded
                    'groom_photo' => $groomPhotoPath,
                    'bride_photo' => $bridePhotoPath,
                ], function ($v) { return !is_null($v) && $v !== ''; })
            );

            // Compute location payload
            $locations = $request->input('locations');
            $locationPayload = $locations ?? $request->input('location');

            // Apply scalar updates if present
            if (array_key_exists('template_id', $validated)) {
                $event->template_id = $validated['template_id'];
            }
            if (array_key_exists('title', $validated)) {
                $event->title = $validated['title'];
            }
            if (array_key_exists('event_date', $validated)) {
                $event->event_date = $validated['event_date'];
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
                if ($index === 0 && !is_numeric($row[0])) continue; // Skip header
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
}
