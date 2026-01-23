<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemplateController extends Controller
{
    public function index()
    {
        $baseUrl = rtrim(Config::get('services.templates.base_url', ''), '/');
        $endpoint = '/'.ltrim(Config::get('services.templates.endpoint', '/api/templates'), '/');
        $timeout = (int) Config::get('services.templates.timeout', 5);
        $cacheTtl = (int) Config::get('services.templates.cache_ttl', 300);

        $externalUrl = $baseUrl ? $baseUrl.$endpoint : null;
        $cacheKey = 'templates:external:'.md5((string) $externalUrl);

        // Try cache first when external URL configured
        if ($externalUrl) {
            try {
                $data = Cache::remember($cacheKey, $cacheTtl, function () use ($externalUrl, $timeout) {
                    $resp = Http::timeout($timeout)
                        ->acceptJson()
                        ->get($externalUrl);
                    if ($resp->successful()) {
                        return $resp->json();
                    }
                    throw new \RuntimeException('External templates fetch failed with status '.$resp->status());
                });

                if (is_array($data)) {
                    return response()->json($data);
                }
            } catch (\Throwable $e) {
                Log::warning('Templates external source unavailable, falling back to DB', [
                    'url' => $externalUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to local DB
        $templates = Template::where('is_active', true)->get();
        return response()->json($templates);
    }
}
