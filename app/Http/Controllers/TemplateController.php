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
        if (!\Illuminate\Support\Facades\File::exists($path)) return;

        $files = \Illuminate\Support\Facades\File::files($path);
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (!str_ends_with($filename, '.html')) continue;

            // Vérifier si le template existe déjà via son fichier
            $exists = Template::where('config_schema->file', $filename)->exists();

            if (!$exists) {
                // Créer un template par défaut à partir du nom du fichier
                $name = ucwords(str_replace(['_', '.html'], [' ', ''], $filename));
                Template::create([
                    'name' => $name,
                    'category' => 'Général',
                    'price_per_pack' => 10.00,
                    'is_active' => true,
                    'config_schema' => [
                        'type' => 'html',
                        'file' => $filename
                    ]
                ]);
            }
        }
    }
}
