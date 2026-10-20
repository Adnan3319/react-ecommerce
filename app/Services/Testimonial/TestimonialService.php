<?php

namespace App\Services\Testimonial;

use App\Enums\ActiveStatus;
use App\Models\Testimonial;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TestimonialService
{
    public const ACTIVE_CACHE_KEY = 'testimonials.active';

    public const STOREFRONT_LIMIT = 6;

    /**
     * @param  array{search?: ?string, status?: ?ActiveStatus, per_page: int}  $filters
     * @return LengthAwarePaginator<int, Testimonial>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Testimonial::query()
            ->search($filters['search'] ?? null)
            ->withStatus($filters['status'] ?? null)
            ->ordered()
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Testimonial
    {
        $testimonial = Testimonial::create($data);

        $this->flushCache();

        return $testimonial;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Testimonial $testimonial, array $data): Testimonial
    {
        $testimonial->update($data);

        $this->flushCache();

        return $testimonial;
    }

    public function delete(Testimonial $testimonial): void
    {
        $testimonial->delete();

        $this->flushCache();
    }

    /**
     * Up to 6 active testimonials in order as plain arrays (cached forever, flushed on every write).
     * The Home page hides the section when this is empty.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForStorefront(): array
    {
        return Cache::rememberForever(self::ACTIVE_CACHE_KEY, fn () => Testimonial::query()
            ->active()
            ->ordered()
            ->limit(self::STOREFRONT_LIMIT)
            ->get(['id', 'customer_name', 'text', 'rating', 'source'])
            ->map(fn (Testimonial $testimonial) => [
                'id' => $testimonial->id,
                'customer_name' => $testimonial->customer_name,
                'text' => $testimonial->text,
                'rating' => $testimonial->rating,
                'source' => $testimonial->source->value,
                'source_label' => $testimonial->source->label(),
            ])
            ->all());
    }

    private function flushCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }
}
