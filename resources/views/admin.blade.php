<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">

        @php($branding = app(\App\Services\Company\CompanyBranding::class)->data())
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
        @if ($branding['is_custom'])
            <link rel="apple-touch-icon" href="{{ $branding['favicon_url'] }}">
        @endif

        @viteReactRefresh
        @vite(['resources/css/admin.css', 'resources/js/admin/app.tsx'])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>