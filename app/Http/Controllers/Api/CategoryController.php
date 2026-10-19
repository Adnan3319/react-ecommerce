<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $categories = Category::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->when(request()->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . request('search') . '%')
            )
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage);

        return ApiResponse::success(
            CategoryResource::collection($categories),
            'Categories fetched successfully'
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return ApiResponse::success(
            new CategoryResource($category),
            'Category created successfully',
            201
        );
    }

    public function show(Category $category): JsonResponse
    {
        return ApiResponse::success(
            new CategoryResource($category),
            'Category fetched successfully'
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return ApiResponse::success(
            new CategoryResource($category),
            'Category updated successfully'
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return ApiResponse::success(
            null,
            'Category deleted successfully'
        );
    }

    /**
     * Upload a category photo to public/images/categories and return its public path.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $file = $request->file('image');
        $name = Str::uuid()->toString() . '.' . $file->extension();

        $file->move(public_path('images/categories'), $name);

        return ApiResponse::success(
            ['path' => "/images/categories/{$name}"],
            'Image uploaded successfully',
            201
        );
    }
}