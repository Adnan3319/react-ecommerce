<?php

namespace App\Services\Page;

use App\Models\Page;
use App\Services\Media\FileUploadService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PageService
{
    private const CACHE_PREFIX = 'pages.';

    private const DESCRIPTION_LIMIT = 160;

    public function __construct(private readonly FileUploadService $uploads) {}

    /**
     * Active page as a plain array (cached per slug) plus its SEO data, or null when it does not exist.
     *
     * @return array{page: array<string, mixed>, seo: array<string, mixed>}|null
     */
    public function forStorefront(string $slug): ?array
    {
        $page = Cache::remember(self::CACHE_PREFIX.$slug, now()->addDay(), fn (): ?array => $this->load($slug));

        return $page === null ? null : ['page' => $page, 'seo' => $this->seo($page)];
    }

    public function flush(string ...$slugs): void
    {
        foreach ($slugs as $slug) {
            Cache::forget(self::CACHE_PREFIX.$slug);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function load(string $slug): ?array
    {
        $page = Page::query()
            ->active()
            ->where('slug', $slug)
            ->first(['title', 'sub_title', 'slug', 'content', 'image']);

        if ($page === null) {
            return null;
        }

        return [
            'title' => $page->title,
            'sub_title' => $page->sub_title,
            'slug' => $page->slug,
            'content' => $page->content,
            'image_url' => $this->uploads->url($page->image),
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    private function seo(array $page): array
    {
        $description = $page['sub_title'] ?: Str::limit(trim(strip_tags((string) $page['content'])), self::DESCRIPTION_LIMIT);

        return [
            'title' => $page['title'].' | '.config('app.name'),
            'description' => $description === '' ? null : $description,
            'canonical' => route('pages.show', $page['slug']),
            'noindex' => false,
            'image' => $page['image_url'] === null ? null : url($page['image_url']),
            'jsonLd' => [],
        ];
    }
}
