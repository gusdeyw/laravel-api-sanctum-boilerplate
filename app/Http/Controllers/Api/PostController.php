<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Post::with('user')->where('user_id', $request->user()->id);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->latest('datepost')->paginate(10);

        return PostResource::collection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request): PostResource
    {
        $post = Post::create([
            'title' => $request->title,
            'description' => $request->description,
            'datepost' => $request->datepost,
            'status' => $request->status ?? 'draft',
            'user_id' => $request->user()->id,
        ]);

        return new PostResource($post->load('user'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post): PostResource
    {
        return new PostResource($post->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        // Check if user owns the post
        if ($post->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $post->update($request->validated());

        return new PostResource($post->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): JsonResponse
    {
        // Check if user owns the post
        if ($post->user_id !== request()->user()->id) {
            abort(403, 'Unauthorized');
        }

        $post->delete();

        return response()->json(null, 204);
    }
}
