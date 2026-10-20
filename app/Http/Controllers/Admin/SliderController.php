<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexListRequest;
use App\Http\Requests\Admin\Slider\SaveSliderRequest;
use App\Http\Resources\Admin\Slider\SliderResource;
use App\Models\Slider;
use App\Services\Slider\SliderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SliderController extends Controller
{
    public function __construct(private readonly SliderService $sliders) {}

    public function index(IndexListRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Admin/Sliders/Index', [
            'sliders' => SliderResource::collection($this->sliders->paginate($filters)),
            'filters' => [
                'search' => $filters['search'],
                'status' => $filters['status']?->value,
                'per_page' => $filters['per_page'],
            ],
            'statuses' => array_map(
                fn (ActiveStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ActiveStatus::cases(),
            ),
        ]);
    }

    public function store(SaveSliderRequest $request): RedirectResponse
    {
        $this->sliders->create($request->sliderData(), $request->file('image'));

        Inertia::flash('success', 'Slider created.');

        return back();
    }

    public function update(SaveSliderRequest $request, Slider $slider): RedirectResponse
    {
        $this->sliders->update($slider, $request->sliderData(), $request->file('image'));

        Inertia::flash('success', 'Slider updated.');

        return back();
    }

    public function destroy(Slider $slider): RedirectResponse
    {
        $this->sliders->delete($slider);

        Inertia::flash('success', 'Slider deleted.');

        return back();
    }
}
