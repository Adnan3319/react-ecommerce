<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Http\Resources\Admin\Page\PageResource;
use App\Models\Page;
use App\Services\Media\FileUploadService;
use App\Services\Page\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    private const IMAGE_DIRECTORY = 'pages';

    public function __construct(
        private readonly FileUploadService $fileUploadService,
        private readonly PageService $pages,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $perPage = $request->integer('per_page', 10);

        $pages = Page::query()
            ->when($search, fn ($query) => $query->where(
                fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"),
            ))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Pages/Index', [
            'pages' => [
                'data' => PageResource::collection($pages->items())->resolve(),
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
            'statuses' => collect(ActiveStatus::cases())
                ->map(fn (ActiveStatus $case): array => ['value' => $case->value, 'label' => $case->label()])
                ->all(),
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->fileUploadService->upload($request->file('image'), self::IMAGE_DIRECTORY, 'default');
        }

        $page = Page::create($data);
        $this->pages->flush($page->slug);

        return redirect()->back()->with('success', 'Page created successfully.');
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();
        unset($data['remove_image']);

        $oldImage = $page->image;
        $oldSlug = $page->slug;

        if ($request->hasFile('image')) {
            $data['image'] = $this->fileUploadService->upload($request->file('image'), self::IMAGE_DIRECTORY, 'default');
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        $page->update($data);

        if ($oldImage !== null && $page->image !== $oldImage) {
            $this->fileUploadService->delete($oldImage);
        }

        $this->pages->flush($oldSlug, $page->slug);

        return redirect()->back()->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        if ($page->image !== null) {
            $this->fileUploadService->delete($page->image);
        }

        $this->pages->flush($page->slug);

        return redirect()->back()->with('success', 'Page deleted successfully.');
    }
}
