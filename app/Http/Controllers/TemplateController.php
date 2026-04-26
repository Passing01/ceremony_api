<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        $this->syncTemplates();
        $templates = Template::where('is_active', true)->get();
        return response()->json($templates);
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
            
            // Paramètres par défaut
            $metadata = [
                'name' => ucwords(str_replace('_', ' ', $baseName)),
                'category' => 'Général',
                'price_per_pack' => 10.00,
                'sections' => []
            ];

            // Charger les métadonnées si le fichier JSON existe
            if (File::exists($jsonPath)) {
                $jsonContent = json_decode(File::get($jsonPath), true);
                $metadata = array_merge($metadata, $jsonContent);
            }

            // Mettre à jour ou créer en base de données
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
}
