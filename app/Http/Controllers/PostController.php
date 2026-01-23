<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\PostLike;
use App\Models\PostBookmark;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function feed(Request $request)
    {
        $query = Post::with(['user:id,full_name,profile_picture,role', 'media'])
            ->withCount(['likes', 'comments'])
            ->orderByDesc('created_at');

        if ($agencyId = $request->query('agency_id')) {
            $query->where('user_id', $agencyId);
        }

        $posts = $query->paginate(15);

        return response()->json($posts);
    }

    public function show(Request $request, $id)
    {
        $post = Post::with(['user:id,full_name,profile_picture,role', 'media'])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        $user = $request->user();
        if ($user) {
            $post->setAttribute('liked', PostLike::where('post_id', $post->id)->where('user_id', $user->id)->exists());
            $post->setAttribute('bookmarked', PostBookmark::where('post_id', $post->id)->where('user_id', $user->id)->exists());
        }

        return response()->json($post);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'agence') {
            return response()->json(['message' => 'Only agencies can create posts.'], 403);
        }

        $request->validate([
            'caption' => 'nullable|string|max:2000',
            'visibility' => 'nullable|in:public,private',
            'media' => 'required|array|min:1',
            'media.*' => 'file|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/x-msvideo|max:20480'
        ]);

        return DB::transaction(function () use ($request, $user) {
            $post = Post::create([
                'user_id' => $user->id,
                'caption' => $request->input('caption'),
                'visibility' => $request->input('visibility', 'public'),
            ]);

            foreach ($request->file('media') as $file) {
                $mime = $file->getMimeType();
                $type = str_starts_with($mime, 'image') ? 'image' : 'video';
                $path = $file->store('posts/media', 'public');
                PostMedia::create([
                    'post_id' => $post->id,
                    'type' => $type,
                    'path' => $path,
                    'thumbnail' => null,
                    'meta' => [
                        'mime' => $mime,
                        'size' => $file->getSize(),
                        'original_name' => $file->getClientOriginalName(),
                    ],
                ]);
            }

            return response()->json($post->load(['media']), 201);
        });
    }

    public function like(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);
        PostLike::firstOrCreate(['post_id' => $post->id, 'user_id' => $user->id]);
        return response()->json(['liked' => true]);
    }

    public function unlike(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);
        PostLike::where('post_id', $post->id)->where('user_id', $user->id)->delete();
        return response()->json(['liked' => false]);
    }

    public function bookmark(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);
        PostBookmark::firstOrCreate(['post_id' => $post->id, 'user_id' => $user->id]);
        return response()->json(['bookmarked' => true]);
    }

    public function unbookmark(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);
        PostBookmark::where('post_id', $post->id)->where('user_id', $user->id)->delete();
        return response()->json(['bookmarked' => false]);
    }

    public function comments(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $comments = PostComment::with(['user:id,full_name,profile_picture'])
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with(['replies.user:id,full_name,profile_picture'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);
        return response()->json($comments);
    }

    public function addComment(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);
        $data = $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:post_comments,id'
        ]);
        $comment = PostComment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => $data['body'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);
        return response()->json($comment->load('user:id,full_name,profile_picture'), 201);
    }

    public function deleteComment(Request $request, $commentId)
    {
        $user = $request->user();
        $comment = PostComment::findOrFail($commentId);
        if ($comment->user_id !== $user->id && $comment->post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $comment->delete();
        return response()->json(['deleted' => true]);
    }

    public function myBookmarks(Request $request)
    {
        $user = $request->user();
        $posts = Post::with(['user:id,full_name,profile_picture,role', 'media'])
            ->whereIn('id', PostBookmark::where('user_id', $user->id)->pluck('post_id'))
            ->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($posts);
    }

    public function myLikes(Request $request)
    {
        $user = $request->user();
        $postIds = PostLike::where('user_id', $user->id)->pluck('post_id');
        $posts = Post::with(['user:id,full_name,profile_picture,role', 'media'])
            ->whereIn('id', $postIds)
            ->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($posts);
    }

    public function agencyPosts(Request $request, $agencyId)
    {
        $posts = Post::with(['user:id,full_name,profile_picture,role', 'media'])
            ->where('user_id', $agencyId)
            ->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($posts);
    }
}
