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
        // 2. Remplacement DIRECT dans le source HTML (Templates JS)
        // ============================================================
        if (!empty($dataArray)) {
            $jsonData = json_encode($dataArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $html = preg_replace('/const\s+chaptersData\s*=\s*\[.*?\];/s', 'const chaptersData = ' . $jsonData . ';', $html);
            $html = preg_replace('/const\s+slidesData\s*=\s*\[.*?\];/s', 'const slidesData = ' . $jsonData . ';', $html);
        }

        // Synchroniser la date du compte à rebours
        $weddingDate = $event->event_date ? $event->event_date->format('Y-m-dT19:00:00') : '2026-09-12T19:00:00';
        $html = preg_replace('/new Date\([\'"].*?[\'"]\)/', "new Date('{$weddingDate}')", $html);

        // ============================================================
        // 3. Préparation des données globales
        // ============================================================
        $domReplacements = json_encode([
            'hero'          => $customData['hero'] ?? $customData['intro'] ?? [],
            'intro'         => $customData['intro'] ?? $customData['envelope'] ?? [],
            'envelope'      => $customData['envelope'] ?? [],
            'event_details' => $customData['event_details'] ?? [],
            'location_civile' => $customData['location_civile'] ?? [],
            'location_reception' => $customData['location_reception'] ?? [],
            'dresscode'     => $customData['dresscode'] ?? [],
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
        // 5. Script DOM Intelligent pour templates statiques (1, 4, 5)
        // ============================================================
        $domScript = "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var d = window.eventData || {};
                
                function setText(selectors, value) {
                    if (!value) return;
                    selectors.split(',').forEach(function(sel) {
                        var el = document.querySelector(sel.trim());
                        if (el) el.textContent = value;
                    });
                }

                function setMedia(containerSelector, mediaUrl) {
                    if (!mediaUrl) return;
                    var container = document.querySelector(containerSelector);
                    if (!container) return;
                    
                    var isVideo = /\.(mp4|webm|mov|ogg)/i.test(mediaUrl);
                    if (isVideo) {
                        container.innerHTML = '<video src=\"' + mediaUrl + '\" autoplay muted loop playsinline style=\"width:100%; height:100%; object-fit:cover;\"></video>';
                    } else {
                        container.innerHTML = '<img src=\"' + mediaUrl + '\" style=\"width:100%; height:100%; object-fit:cover;\">';
                    }
                }

                // Noms avec préservation du style '&'
                var names = d.intro.names || d.envelope.names || d.envelope.front_text;
                if (names) {
                    var parts = names.split('&');
                    var selectors = '.names, .couple-names, .hero-names';
                    selectors.split(',').forEach(function(sel) {
                        var el = document.querySelector(sel.trim());
                        if (!el) return;
                        if (parts.length === 2) {
                            var etClass = el.querySelector('.et, .ampersand') ? (el.querySelector('.et') ? 'et' : 'ampersand') : 'et';
                            el.innerHTML = parts[0].trim() + ' <span class=\"' + etClass + '\">&</span> ' + parts[1].trim();
                        } else {
                            el.textContent = names;
                        }
                    });
                }

                // Médias
                setMedia('.hero-section, .hero-media', d.hero.media || d.hero.mediaSrc);
                setMedia('.card-lieu:nth-of-type(1) .lieu-media', d.location_civile.media);
                setMedia('.card-lieu:nth-of-type(2) .lieu-media', d.location_reception.media);

                // Textes
                setText('.invitation-text, .card-invite p:first-child', d.event_details.text);
                setText('.quote, .quote-text, .card-invite .quote', d.event_details.quote || d.event_details.story);
                setText('.save-date-date, .date-time', d.event_details.date);
                setText('.lieu-nom', d.event_details.location);
                
                // Détails spécifiques (Template 5)
                var civ = d.location_civile || {};
                var rec = d.location_reception || {};
                var elCiv = document.querySelectorAll('.card-lieu')[0];
                if(elCiv && civ.name) {
                    elCiv.querySelector('.lieu-nom').textContent = civ.name;
                    elCiv.querySelector('.lieu-adresse').textContent = civ.address;
                    elCiv.querySelector('.lieu-type').textContent = civ.title;
                }
                var elRec = document.querySelectorAll('.card-lieu')[1];
                if(elRec && rec.name) {
                    elRec.querySelector('.lieu-nom').textContent = rec.name;
                    elRec.querySelector('.lieu-adresse').textContent = rec.address;
                    elRec.querySelector('.lieu-type').textContent = rec.title;
                }
            });
        </script>";
        $html = str_replace('</body>', $domScript . '</body>', $html);

        return response($html)->header('Content-Type', 'text/html');
    }
}
