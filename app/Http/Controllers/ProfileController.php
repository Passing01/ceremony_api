<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostBookmark;
use App\Models\PostLike;
use App\Models\UserCredit;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        // Counts
        $postsCount = Post::where('user_id', $user->id)->count();
        $likesCount = PostLike::where('user_id', $user->id)->count();
        $bookmarksCount = PostBookmark::where('user_id', $user->id)->count();
        $purchasedTemplatesCount = UserCredit::where('user_id', $user->id)->count();

        // Use toArray() to include appends (profile_picture_url)
        $profile = $user->toArray();

        // Add stats
        $profile['stats'] = [
            'posts_count' => $postsCount,
            'likes_count' => $likesCount,
            'bookmarks_count' => $bookmarksCount,
            'purchased_templates_count' => $purchasedTemplatesCount,
        ];

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'profile_picture' => 'nullable|image|max:4096', // 4MB Max
            'agency_details' => 'sometimes|array',
            'settings' => 'sometimes|array',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('users/avatars', 'public');
            $user->profile_picture = $path;
        }

        if ($request->has('full_name')) {
            $user->full_name = $request->full_name;
        }

        if ($request->has('agency_details')) {
            // Merge or replace? Usually merge is safer for partial updates, but replace is simpler.
            // Let's merge if existing is array
            $current = $user->agency_details ?? [];
            $user->agency_details = array_merge($current, $request->agency_details);
        }

        if ($request->has('settings')) {
            $currentSettings = $user->settings ?? [];
            $user->settings = array_merge($currentSettings, $request->settings);
        }

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user->only(['id', 'full_name', 'email', 'role', 'profile_picture', 'agency_details', 'settings'])
        ]);
    }

    public function myTemplates(Request $request)
    {
        $user = $request->user();
        $credits = UserCredit::with(['template:id,name,category,price_per_pack,preview_image'])
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($credit) {
                return [
                    'template' => $credit->template,
                    'remaining_uses' => $credit->remaining_uses,
                ];
            });

        return response()->json(['data' => $credits]);
    }
}
