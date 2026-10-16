<?php

namespace App\Http\Controllers\Api;

use App\Models\Service;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $services = Service::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->when(request()->filled('search'), fn ($q) =>
                $q->where('title', 'like', '%' . request('search') . '%')
            )
            ->orderBy('sort_order', 'asc')
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            ServiceResource::collection($services),
            'Services fetched successfully'
        );
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $service = Service::create($data);

        return ApiResponse::success(
            new ServiceResource($service),
            'Service created successfully',
            201
        );
    }

    public function show(Service $service): JsonResponse
    {
        return ApiResponse::success(
            new ServiceResource($service),
            'Service fetched successfully'
        );
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $service->update($data);

        return ApiResponse::success(
            new ServiceResource($service),
            'Service updated successfully'
        );
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return ApiResponse::success(
            null,
            'Service deleted successfully'
        );
    }
}