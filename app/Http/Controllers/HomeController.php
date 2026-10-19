<?php

namespace App\Http\Controllers;

use App\Services\Home\HomeService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly HomeService $home) {}

    public function __invoke(): Response
    {
        $name = config('app.name');

        return Inertia::render('Home', [
            ...$this->home->data(),
            'seo' => [
                'title' => $name,
                'description' => "Shop online at {$name}.",
                'canonical' => route('home'),
                'noindex' => false,
                'image' => null,
                'jsonLd' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'Organization',
                        'name' => $name,
                        'url' => route('home'),
                    ],
                ],
            ],
        ]);
    }
}
