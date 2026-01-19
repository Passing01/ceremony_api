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
        ]);

        $guest = Guest::where('invitation_token', $token)->firstOrFail();

        $guest->update([
            'rsvp' => $request->rsvp,
        ]);

        return response()->json(['message' => 'RSVP mis à jour', 'guest' => $guest]);
    }
}
