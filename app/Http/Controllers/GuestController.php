<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function rsvp(Request $request, $token)
    {
        $request->validate([
            'rsvp' => 'required|in:confirmed,declined,waiting',
            'companion_count' => 'nullable|integer|min:0',
            'message' => 'nullable|string|max:1000',
        ]);

        $guest = Guest::where('invitation_token', $token)->firstOrFail();

        $guest->update([
            'rsvp' => $request->rsvp,
            'companion_count' => $request->companion_count ?? 0,
            'message' => $request->message,
        ]);

        return response()->json(['message' => 'RSVP mis à jour', 'guest' => $guest]);
    }
}
