<?php

namespace App\Http\Controllers;

use App\Http\Resources\SliderResource;
use App\Models\Slider;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $name = config('app.name');

        $sliders = Slider::query()
            ->where('status', 'Active')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return Inertia::render('Home', [
            'sliders' => SliderResource::collection($sliders)->resolve(),
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