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

        // Injection des données
        // 1. Pour les templates JS-driven (2 et 3)
        $jsonDetails = json_encode($data);
        $injection = "<script>window.chaptersData = {$jsonDetails}; if(window.showChapter) window.showChapter(0);</script>";
        $html = str_replace('</body>', $injection . '</body>', $html);

        // 2. Pour le template 1 (DOM simple)
        // On pourrait faire des remplacements de placeholders {{title}} etc.
        foreach ($data as $sectionId => $fields) {
            foreach ($fields as $key => $value) {
                $html = str_replace("{{{$sectionId}.{$key}}}", $value, $html);
            }
        }

        return response($html)->header('Content-Type', 'text/html');
    }
}
