<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $clients = Client::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->when(request()->filled('search'), fn ($q) =>
                $q->where('client_name', 'like', '%' . request('search') . '%')
                  ->orWhere('company_name', 'like', '%' . request('search') . '%')
            )
            ->orderBy('sort_order', 'asc')
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            ClientResource::collection($clients),
            'Clients fetched successfully'
        );
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return ApiResponse::success(
            new ClientResource($client),
            'Client created successfully',
            201
        );
    }

    public function show(Client $client): JsonResponse
    {
        return ApiResponse::success(
            new ClientResource($client),
            'Client fetched successfully'
        );
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());

        return ApiResponse::success(
            new ClientResource($client),
            'Client updated successfully'
        );
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return ApiResponse::success(
            null,
            'Client deleted successfully'
        );
    }
}