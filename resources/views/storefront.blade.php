@php
    $seo = $page['props']['seo'] ?? [];
    $appName = config('app.name');
    $title = $seo['title'] ?? $appName;
    $description = $seo['description'] ?? null;
    $canonical = $seo['canonical'] ?? null;
    $image = $seo['image'] ?? null;
    $robots = ($seo['noindex'] ?? false) ? 'noindex,follow' : 'index,follow';
    // First Home hero slide: start downloading its image before the JS bundle runs (LCP).
    $heroSlide = ($page['component'] ?? null) === 'Home' ? ($page['props']['heroSlides'][0] ?? null) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @if (! empty($heroSlide['image']))
            <link rel="preload" as="image" href="{{ $heroSlide['image'] }}" @if (! empty($heroSlide['image_srcset'])) imagesrcset="{{ $heroSlide['image_srcset'] }}" imagesizes="100vw" @endif fetchpriority="high">
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/storefront/app.tsx'])
        <x-inertia::head>
            <title data-inertia="title">{{ $title }}</title>
            <meta name="robots" content="{{ $robots }}" data-inertia="robots">
            @if ($description)
                <meta name="description" content="{{ $description }}" data-inertia="description">
            @endif
            @if ($canonical)
                <link rel="canonical" href="{{ $canonical }}" data-inertia="canonical">
            @endif
            <meta property="og:type" content="website" data-inertia="og:type">
            <meta property="og:site_name" content="{{ $appName }}" data-inertia="og:site_name">
            <meta property="og:title" content="{{ $title }}" data-inertia="og:title">
            @if ($description)
                <meta property="og:description" content="{{ $description }}" data-inertia="og:description">
            @endif
            @if ($canonical)
                <meta property="og:url" content="{{ $canonical }}" data-inertia="og:url">
            @endif
            @if ($image)
                <meta property="og:image" content="{{ $image }}" data-inertia="og:image">
            @endif
            <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}" data-inertia="twitter:card">
            @foreach ($seo['jsonLd'] ?? [] as $index => $schema)
                <script type="application/ld+json" data-inertia="jsonld-{{ $index }}">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
            @endforeach
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
