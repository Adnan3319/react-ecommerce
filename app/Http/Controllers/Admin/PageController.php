<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use App\Services\Media\FileUploadService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $perPage = $request->input('per_page', 10);

        $query = Page::query();

        if ($search) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $pages = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Pages/Index', [
            'pages' => [
                'data' => $pages->items(),
                'meta' => [
                    'current_page' => $pages->currentPage(),
                    'last_page' => $pages->lastPage(),
                    'total' => $pages->total(),
                    'per_page' => $pages->perPage(),
                    'from' => $pages->firstItem(),
                    'to' => $pages->lastItem(),
                    'links' => $pages->linkCollection()->toArray(),
                ],
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ]);
    }

    public function store(StorePageRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->fileUploadService->upload($request->file('image'), 'pages');
        }

        Page::create($data);

        return redirect()->back()->with('success', 'Page created successfully.');
    }

    public function update(UpdatePageRequest $request, Page $page)
    {
        $data = $request->validated();

        if ($request->boolean('remove_image')) {
            if ($page->image) {
                $this->fileUploadService->delete($page->image);
            }
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($page->image) {
                $this->fileUploadService->delete($page->image);
            }
            $data['image'] = $this->fileUploadService->upload($request->file('image'), 'pages');
        } else {
            unset($data['image']);
        }

        $page->update($data);

        return redirect()->back()->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page)
    {
        if ($page->image) {
            $this->fileUploadService->delete($page->image);
        }

        $page->delete();

        return redirect()->back()->with('success', 'Page deleted successfully.');
    }
}