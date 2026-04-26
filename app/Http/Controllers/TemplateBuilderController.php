<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TemplateBuilderController extends Controller
{
    public function index()
    {
        $this->syncTemplates();
        $templates = \App\Models\Template::where('is_active', true)
            ->where('config_schema->type', 'html')
            ->get();

        return view('builder.index', compact('templates'));
    }

    private function syncTemplates()
    {
        $path = resource_path('views/templates');
        if (!File::exists($path)) return;

        $files = File::files($path);
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (!str_ends_with($filename, '.html')) continue;

            $baseName = str_replace('.html', '', $filename);
            $jsonPath = $path . '/' . $baseName . '.json';
            
            $metadata = [
                'name' => ucwords(str_replace('_', ' ', $baseName)),
                'category' => 'Général',
                'price_per_pack' => 10.00,
                'sections' => []
            ];

            if (File::exists($jsonPath)) {
                $jsonContent = json_decode(File::get($jsonPath), true);
                $metadata = array_merge($metadata, $jsonContent);
            }

            \App\Models\Template::updateOrCreate(
                ['config_schema->file' => $filename],
                [
                    'name' => $metadata['name'],
                    'category' => $metadata['category'],
                    'price_per_pack' => $metadata['price_per_pack'],
                    'is_active' => true,
                    'config_schema' => [
                        'type' => 'html',
                        'file' => $filename,
                        'sections' => $metadata['sections']
                    ]
                ]
            );
        }
    }

    public function edit($id)
    {
        $template = \App\Models\Template::findOrFail($id);
        
        // Récupérer le nom du fichier depuis config_schema ou tenter une déduction
        $templateFile = $template->config_schema['file'] ?? null;
        
        if (!$templateFile) {
            // Tentative de déduction si le champ file est manquant
            $templateFile = "invitation{$id}.html";
        }

        $path = resource_path("views/templates/{$templateFile}");
        
        if (!File::exists($path)) {
            // Si toujours introuvable, on cherche par le nom du template (slugifié)
            $slugName = \Illuminate\Support\Str::slug($template->name, '_') . '.html';
            $path = resource_path("views/templates/{$slugName}");
            
            if (!File::exists($path)) {
                abort(404, "Le fichier de template [{$templateFile}] est introuvable dans " . resource_path('views/templates'));
            }
        }

        $html = File::get($path);
        
        return view('builder.edit', [
            'id' => $id,
            'html' => $html,
            'templateName' => $template->name
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

    /**
     * Vue publique pour l'invité (Lien WhatsApp)
     */
    public function guestInvitation($token)
    {
        $guest = \App\Models\Guest::where('invitation_token', $token)->firstOrFail();
        $event  = $guest->event;
        $template = $event->template;

        // Charger le fichier HTML du template
        $filename = $template->config_schema['file'] ?? "invitation{$template->id}.html";
        $path = resource_path("views/templates/{$filename}");

        if (!File::exists($path)) {
            abort(404, "Template file not found: {$filename}");
        }

        $html = File::get($path);
        $customData = $event->custom_data ?? [];
        $sections   = $template->config_schema['sections'] ?? [];

        // ============================================================
        // 1. Construire le tableau JS ordonné à partir des sections
        //    (merge des valeurs par défaut + données personnalisées)
        // ============================================================
        $dataArray = [];
        foreach ($sections as $section) {
            $sectionId = $section['id'];
            if (!isset($customData[$sectionId])) continue;

            $defaults = [];
            foreach ($section['fields'] ?? [] as $field) {
                $defaults[$field['id']] = $field['default'] ?? '';
            }
            $dataArray[] = array_merge($defaults, $customData[$sectionId]);
        }

        // ============================================================
        // 2. Remplacement DIRECT dans le source HTML :
        //    const chaptersData = [...] → données personnalisées
        //    const slidesData   = [...] → données personnalisées
        //    Cela fonctionne car les templates utilisent des `const`
        //    locales et non des variables window.
        // ============================================================
        if (!empty($dataArray)) {
            $jsonData = json_encode($dataArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            // invitation2.html → const chaptersData
            $html = preg_replace(
                '/const\s+chaptersData\s*=\s*\[.*?\];/s',
                'const chaptersData = ' . $jsonData . ';',
                $html
            );

            // invitation3.html → const slidesData
            $html = preg_replace(
                '/const\s+slidesData\s*=\s*\[.*?\];/s',
                'const slidesData = ' . $jsonData . ';',
                $html
            );
        }

        // ============================================================
        // 3. Pour invitation1 (HTML statique) : remplacer les éléments
        //    DOM directement via un script DOMContentLoaded
        // ============================================================
        $envData     = $customData['envelope'] ?? [];
        $evDetails   = $customData['event_details'] ?? [];

        $domReplacements = json_encode([
            'envelope'     => $envData,
            'event_details' => $evDetails,
        ], JSON_UNESCAPED_UNICODE);

        // ============================================================
        // 4. Injection du token RSVP + données globales dans <head>
        // ============================================================
        $headScript = "<script>
            window.guestToken = '{$token}';
            window.eventData  = {$domReplacements};
        </script>";
        $html = preg_replace('/<head([^>]*)>/i', '<head$1>' . $headScript, $html, 1);

        // ============================================================
        // 5. Script DOM pour invitation1 (remplacement des textes visibles)
        // ============================================================
        $domScript = "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var d = window.eventData || {};
                var env = d.envelope || {};
                var ev  = d.event_details || {};

                function setText(selectors, value) {
                    if (!value) return;
                    selectors.split(',').forEach(function(sel) {
                        var el = document.querySelector(sel.trim());
                        if (el) el.textContent = value;
                    });
                }
                function setHTML(selectors, value) {
                    if (!value) return;
                    selectors.split(',').forEach(function(sel) {
                        var el = document.querySelector(sel.trim());
                        if (el) el.innerHTML = value;
                    });
                }

                setHTML('.couple-names, .names, .hero-names', env.names || env.front_text);
                setText('.invitation-subtitle, .subtitle, .hero-subtitle', env.subtitle);
                setText('.event-date, .date-main, .save-date-date', ev.date);
                setText('.event-location, .lieu-nom, .location-name', ev.location);
                setText('.event-title, .hero-title, .section-title', ev.title);
            });
        </script>";
        $html = str_replace('</body>', $domScript . '</body>', $html);

        return response($html)->header('Content-Type', 'text/html');
    }
}
