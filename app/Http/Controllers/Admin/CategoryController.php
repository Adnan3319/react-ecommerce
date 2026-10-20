<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\SaveCategoryRequest;
use App\Http\Requests\Admin\IndexListRequest;
use App\Http\Resources\Admin\Category\CategoryResource;
use App\Models\Category;
use App\Services\Category\CategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(IndexListRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => CategoryResource::collection($this->categories->paginate($filters)),
            'filters' => [
                'search' => $filters['search'],
                'status' => $filters['status']?->value,
                'per_page' => $filters['per_page'],
            ],
            'statuses' => array_map(
                fn (ActiveStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ActiveStatus::cases(),
            ),
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->categoryData(), $request->file('image'));

        Inertia::flash('success', 'Category created.');

        return back();
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update(
            $category,
            $request->categoryData(),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        Inertia::flash('success', 'Category updated.');

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);

        Inertia::flash('success', 'Category deleted.');

        return back();
    }
}
