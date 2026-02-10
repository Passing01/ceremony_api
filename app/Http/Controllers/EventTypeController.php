<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventTypeController extends Controller
{
    /**
     * Get all available event types
     */
    public function index()
    {
        $eventTypes = config('event_types');

        $formatted = [];
        foreach ($eventTypes as $key => $config) {
            $formatted[] = [
                'type' => $key,
                'name' => $config['name'],
            ];
        }

        return response()->json($formatted);
    }

    /**
     * Get configuration for a specific event type
     */
    public function show($type)
    {
        $eventTypes = config('event_types');

        if (!isset($eventTypes[$type])) {
            return response()->json([
                'message' => 'Type d\'événement non trouvé'
            ], 404);
        }

        return response()->json([
            'type' => $type,
            'name' => $eventTypes[$type]['name'],
            'fields' => $eventTypes[$type]['fields'],
        ]);
    }
}

