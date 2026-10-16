<?php

namespace App\Http\Controllers\Api;

use App\Models\About;
use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Http\Requests\StoreAboutRequest;
use App\Http\Requests\UpdateAboutRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AboutController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $abouts = About::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            AboutResource::collection($abouts),
            'About sections fetched successfully'
        );
    }

    public function store(StoreAboutRequest $request): JsonResponse
    {
        $about = About::create($request->validated());

        return ApiResponse::success(
            new AboutResource($about),
            'About section created successfully',
            201
        );
    }

    public function show(About $about): JsonResponse
    {
        return ApiResponse::success(
            new AboutResource($about),
            'About section fetched successfully'
        );
    }

    public function update(UpdateAboutRequest $request, About $about): JsonResponse
    {
        $about->update($request->validated());

        return ApiResponse::success(
            new AboutResource($about),
            'About section updated successfully'
        );
    }

    public function destroy(About $about): JsonResponse
    {
        $about->delete();

        return ApiResponse::success(
            null,
            'About section deleted successfully'
        );
    }
}