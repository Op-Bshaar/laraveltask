<?php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
    }

    // a. Authenticated users can view only their posts.
    public function index(): JsonResponse
    {
        $posts = Auth::user()->posts()->with('tags')->orderBy('pinned', 'desc')->get();
        return response()->json($posts);
    }

    // b. Authenticated users can store new posts.
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'cover_image' => 'nullable|url',
            'pinned' => 'required|boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Auth::user()->posts()->create($request->all());
        $post->tags()->sync($request->tags);

        return response()->json($post, 201);
    }

    // c. Authenticated users can view a single post of their posts.
    public function show(Post $post): JsonResponse
    {
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($post->load('tags'));
    }

    // Authenticated users can update a single post of their posts.
    public function update(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'cover_image' => 'sometimes|image',
            'pinned' => 'sometimes|required|boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post->update($request->all());
        $post->tags()->sync($request->tags);

        return response()->json($post);
    }

    //  Authenticated users can delete (Softly) a single post of their posts.
    public function destroy(Post $post): JsonResponse
    {
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();
        return response()->json(['message' => 'Post deleted successfully']);
    }

    //  Authenticated users can view their deleted posts.
    public function trashed(): JsonResponse
    {
        $posts = Auth::user()->posts()->onlyTrashed()->with('tags')->get();
        return response()->json($posts);
    }

    //  Authenticated users can restore one of their deleted posts.
    public function restore($id): JsonResponse
    {
        $post = Auth::user()->posts()->onlyTrashed()->findOrFail($id);
        $post->restore();
        return response()->json(['message' => 'Post restored successfully']);
    }
}

