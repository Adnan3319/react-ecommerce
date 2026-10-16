<?php

namespace App\Http\Controllers\Api;

use App\Models\Blog;
use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $blogs = Blog::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->when(request()->filled('category'), fn ($q) =>
                $q->where('category', request('category'))
            )
            ->when(request()->filled('search'), fn ($q) =>
                $q->where('title', 'like', '%' . request('search') . '%')
                  ->orWhere('description', 'like', '%' . request('search') . '%')
            )
            ->orderBy('sort_order', 'asc')
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            BlogResource::collection($blogs),
            'Blogs fetched successfully'
        );
    }

    public function store(StoreBlogRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $blog = Blog::create($data);

        return ApiResponse::success(
            new BlogResource($blog),
            'Blog created successfully',
            201
        );
    }

    public function show(Blog $blog): JsonResponse
    {
        return ApiResponse::success(
            new BlogResource($blog),
            'Blog fetched successfully'
        );
    }

    public function update(UpdateBlogRequest $request, Blog $blog): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $blog->update($data);

        return ApiResponse::success(
            new BlogResource($blog),
            'Blog updated successfully'
        );
    }

    public function destroy(Blog $blog): JsonResponse
    {
        $blog->delete();

        return ApiResponse::success(
            null,
            'Blog deleted successfully'
        );
    }
}