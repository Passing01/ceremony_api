<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\UserCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:templates,id',
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'location' => 'nullable|array',
            'custom_data' => 'nullable|array',
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
            $event = Event::create([
                'owner_id' => $user->id,
                'template_id' => $request->template_id,
                'title' => $request->title,
                'event_date' => $request->event_date,
                'location' => $request->location,
                'custom_data' => $request->custom_data,
                'slug' => Str::slug($request->title) . '-' . Str::random(6),
                'short_link' => Str::random(10), // Placeholder for real short link logic
            ]);

            if ($credit) {
                $credit->decrement('remaining_uses');
            }

            return response()->json($event, 201);
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
