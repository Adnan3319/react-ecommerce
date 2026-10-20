<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\TestimonialSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexListRequest;
use App\Http\Requests\Admin\Testimonial\SaveTestimonialRequest;
use App\Http\Resources\Admin\Testimonial\TestimonialResource;
use App\Models\Testimonial;
use App\Services\Testimonial\TestimonialService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function __construct(private readonly TestimonialService $testimonials) {}

    public function index(IndexListRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Admin/Testimonials/Index', [
            'testimonials' => TestimonialResource::collection($this->testimonials->paginate($filters)),
            'filters' => [
                'search' => $filters['search'],
                'status' => $filters['status']?->value,
                'per_page' => $filters['per_page'],
            ],
            'statuses' => array_map(
                fn (ActiveStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ActiveStatus::cases(),
            ),
            'sources' => array_map(
                fn (TestimonialSource $source) => ['value' => $source->value, 'label' => $source->label()],
                TestimonialSource::cases(),
            ),
            'limits' => [
                'text' => Testimonial::MAX_TEXT_LENGTH,
                'min_rating' => Testimonial::MIN_RATING,
                'max_rating' => Testimonial::MAX_RATING,
            ],
        ]);
    }

    public function store(SaveTestimonialRequest $request): RedirectResponse
    {
        $this->testimonials->create($request->testimonialData());

        Inertia::flash('success', 'Testimonial created.');

        return back();
    }

    public function update(SaveTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->testimonials->update($testimonial, $request->testimonialData());

        Inertia::flash('success', 'Testimonial updated.');

        return back();
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->testimonials->delete($testimonial);

        Inertia::flash('success', 'Testimonial deleted.');

        return back();
    }
}
