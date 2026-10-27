<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return Inertia::render('storefront/features/pages/Index', [
            'page' => $page,
        ]);
    }
}