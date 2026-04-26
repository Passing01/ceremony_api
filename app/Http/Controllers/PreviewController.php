<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PreviewController extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:templates,id',
            'data' => 'required|array'
        ]);

        $template = Template::findOrFail($request->template_id);
        $filename = $template->config_schema['file'];
        $path = resource_path("views/templates/{$filename}");

        if (!File::exists($path)) {
            return response()->json(['message' => 'Template file not found'], 404);
        }

        $html = File::get($path);
        $data = $request->data;
        $sections = $template->config_schema['sections'] ?? [];

        // 1. Préparation des données pour les templates JS (tableau ordonné)
        $dataArray = [];
        foreach ($sections as $section) {
            $sectionId = $section['id'];
            if (isset($data[$sectionId])) {
                $dataArray[] = $data[$sectionId];
            }
        }

        $jsonDetails = json_encode($dataArray);
        $injection = "<script>window.chaptersData = {$jsonDetails}; window.slidesData = {$jsonDetails}; if(window.showChapter) window.showChapter(0); if(window.showSlide) window.showSlide(0);</script>";
        $html = str_replace('</body>', $injection . '</body>', $html);

        // 2. Remplacements directs pour le template 1
        foreach ($data as $sectionId => $fields) {
            if (is_array($fields)) {
                foreach ($fields as $key => $value) {
                    if (is_string($value)) {
                        $html = str_replace("{{{$sectionId}.{$key}}}", $value, $html);
                    }
                }
            }
        }

        return response($html)->header('Content-Type', 'text/html');
    }
}
