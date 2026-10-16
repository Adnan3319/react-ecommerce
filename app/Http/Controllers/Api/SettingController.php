<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all();

        return ApiResponse::success(
            SettingResource::collection($settings),
            'Settings fetched successfully'
        );
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        foreach ($request->validated()['settings'] as $item) {
            Setting::where('key', $item['key'])->update([
                'value' => $item['value'] ?? null
            ]);
        }

        $settings = Setting::all();

        return ApiResponse::success(
            SettingResource::collection($settings),
            'Settings updated successfully'
        );
    }
}