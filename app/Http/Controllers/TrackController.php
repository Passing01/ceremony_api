<?php

namespace App\Http\Controllers;

use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class TrackController extends Controller
{
    public function publicIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $tracks = Track::query()
            ->where('is_public', true)
            ->where('status', 'active')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', '%' . $q . '%')
                        ->orWhere('artist', 'like', '%' . $q . '%');
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json($tracks);
    }

    public function upload(Request $request)
    {
        $user = $request->user();

        if (!$request->hasFile('file')) {
            return response()->json([
                'message' => 'The file failed to upload.',
                'errors' => [
                    'file' => ['No file was received by the server.'],
                ],
            ], 422);
        }

        /** @var UploadedFile|null $file */
        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            $code = $file?->getError();
            $maxUpload = ini_get('upload_max_filesize');
            $maxPost = ini_get('post_max_size');

            return response()->json([
                'message' => 'The file failed to upload.',
                'errors' => [
                    'file' => [
                        'Upload failed at PHP level. Code: ' . ($code ?? 'unknown') . '. Limits: upload_max_filesize=' . $maxUpload . ', post_max_size=' . $maxPost,
                    ],
                ],
            ], 422);
        }

        $validated = $request->validate([
            'file' => 'required|file|max:20480|mimetypes:audio/mpeg,audio/mp4,audio/aac,audio/wav,audio/ogg,application/ogg',
            'title' => 'nullable|string|max:255',
            'artist' => 'nullable|string|max:255',
            'duration_ms' => 'nullable|integer|min:1',
        ]);

        $path = $file->store('tracks/audio', 'public');

        $track = Track::create([
            'user_id' => $user->id,
            'provider' => 'upload',
            'title' => $validated['title'] ?? null,
            'artist' => $validated['artist'] ?? null,
            'duration_ms' => $validated['duration_ms'] ?? null,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'is_public' => true,
            'status' => 'active',
        ]);

        return response()->json($track, 201);
    }
}
