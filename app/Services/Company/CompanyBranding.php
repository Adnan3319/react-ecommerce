<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Services\Media\FileUploadService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The store name, logo and favicon shown everywhere (storefront, admin, browser tab). They come from Company
 * settings; with no uploaded logo the UI shows the store name as text and the favicon is the name's first letter.
 */
class CompanyBranding
{
    public const CACHE_KEY = 'company.branding';

    private const FAVICON_COLOR = '#e11d48';

    public function __construct(private readonly FileUploadService $uploads) {}

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{name: string, logo_url: ?string, favicon_url: string, is_custom: bool}
     */
    public function data(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function (): array {
            $company = Company::query()->first(['name', 'logo']);
            $name = $company?->name ?: config('app.name');
            $logoUrl = $this->uploads->url($company?->logo);

            return [
                'name' => $name,
                'logo_url' => $logoUrl,
                'favicon_url' => $logoUrl ?? $this->letterFavicon($name),
                'is_custom' => $logoUrl !== null,
            ];
        });
    }

    /**
     * Inline SVG data URI: the first letter of the store name on a rounded square.
     */
    private function letterFavicon(string $name): string
    {
        $letter = htmlspecialchars(Str::upper(Str::substr(trim($name), 0, 1)) ?: '?', ENT_XML1);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" rx="14" fill="'.self::FAVICON_COLOR.'"/>'
            .'<text x="32" y="44" font-family="Arial,sans-serif" font-size="36" font-weight="700" text-anchor="middle" fill="#fff">'.$letter.'</text>'
            .'</svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }
}
