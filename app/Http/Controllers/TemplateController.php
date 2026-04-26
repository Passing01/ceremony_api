<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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
            if ($file->getExtension() === 'json') {
                $config = json_decode(File::get($file), true);
                $htmlFile = str_replace('.json', '.html', $file->getFilename());
                
                $id = $mapping[$file->getFilename()] ?? null;
                
                $templateData = [
                    'name' => $config['name'] ?? 'Template Sans Nom',
                    'category' => $config['category'] ?? 'Général',
                    'price_per_pack' => $config['price_per_pack'] ?? 0,
                    'config_schema' => array_merge($config, ['file' => $htmlFile]),
                    'is_active' => true,
                ];

                if ($id) {
                    \App\Models\Template::updateOrCreate(['id' => $id], $templateData);
                } else {
                    \App\Models\Template::updateOrCreate(['name' => $templateData['name']], $templateData);
                }
            }
        }
    }
}
