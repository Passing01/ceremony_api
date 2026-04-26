<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TemplateBuilderController extends Controller
{
    public function index()
    {
        $templates = [
            ['id' => 1, 'name' => 'Invitation Classique', 'file' => 'invitation1.html'],
            ['id' => 2, 'name' => 'Invitation Interactive', 'file' => 'invitation2.html'],
            ['id' => 3, 'name' => 'Story Moderne', 'file' => 'invitation3.html'],
        ];

        return view('builder.index', compact('templates'));
    }

    public function edit($id)
    {
        $templateFile = "invitation{$id}.html";
        $path = resource_path("views/templates/{$templateFile}");
        
        if (!File::exists($path)) {
            abort(404);
        }

        $html = File::get($path);
        
        return view('builder.edit', [
            'id' => $id,
            'html' => $html,
            'templateName' => "Template {$id}"
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'html' => 'required',
            'template_id' => 'required',
        ]);

        // Stocker en session pour l'instant (ou dans une table temporaire)
        session(['custom_template_html' => $request->html]);
        session(['selected_template_id' => $request->template_id]);

        return redirect()->route('builder.setup');
    }

    public function setup()
    {
        if (!session()->has('custom_template_html')) {
            return redirect()->route('builder.index');
        }

        return view('builder.setup');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'event_date' => 'required',
            'location' => 'nullable|string',
            'guests' => 'required|array',
            'invitation_message' => 'nullable|string',
        ]);

        $user = auth()->user() ?? \App\Models\User::first(); // Fallback pour démo

        $event = \App\Models\Event::create([
            'owner_id' => $user->id,
            'template_id' => session('selected_template_id'),
            'title' => $request->title,
            'event_date' => $request->event_date,
            'location' => ['name' => $request->location],
            'custom_data' => ['html_content' => session('custom_template_html')],
            'invitation_text' => $request->invitation_message,
            'slug' => \Illuminate\Support\Str::slug($request->title) . '-' . \Illuminate\Support\Str::random(6),
        ]);

        foreach ($request->guests as $guestData) {
            $event->guests()->create([
                'whatsapp_number' => $guestData['phone'],
                'invitation_token' => \Illuminate\Support\Str::uuid(),
                'status' => 'pending',
            ]);
        }

        // Nettoyer la session
        session()->forget(['custom_template_html', 'selected_template_id']);

        return response()->json(['message' => 'Événement créé avec succès', 'redirect' => '/']);
    }
}
