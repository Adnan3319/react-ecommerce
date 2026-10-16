<?php

namespace App\Http\Controllers\Api;

use App\Models\Team;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $teams = Team::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->when(request()->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . request('search') . '%')
                  ->orWhere('designation', 'like', '%' . request('search') . '%')
            )
            ->orderBy('sort_order', 'asc')
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            TeamResource::collection($teams),
            'Team members fetched successfully'
        );
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::create($request->validated());

        return ApiResponse::success(
            new TeamResource($team),
            'Team member created successfully',
            201
        );
    }

    public function show(Team $team): JsonResponse
    {
        return ApiResponse::success(
            new TeamResource($team),
            'Team member fetched successfully'
        );
    }

    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $team->update($request->validated());

        return ApiResponse::success(
            new TeamResource($team),
            'Team member updated successfully'
        );
    }

    public function destroy(Team $team): JsonResponse
    {
        $team->delete();

        return ApiResponse::success(
            null,
            'Team member deleted successfully'
        );
    }
}