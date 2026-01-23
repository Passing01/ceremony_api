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

        $profile = $user->only(['id','full_name','email','role','profile_picture','agency_details','settings','created_at']);
        $profile['stats'] = [
            'posts_count' => $postsCount,
            'likes_count' => $likesCount,
            'bookmarks_count' => $bookmarksCount,
            'purchased_templates_count' => $purchasedTemplatesCount,
        ];

        return response()->json($profile);
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
