<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Page\PageService;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private readonly PageService $pages) {}

    public function show(string $slug): Response
    {
        $data = $this->pages->forStorefront($slug) ?? abort(404);

        return Inertia::render('Pages/Show', $data);
    }
}
