<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->input('per_page', 10), 50);

        $contacts = Contact::query()
            ->when(request()->filled('status'), fn ($q) =>
                $q->where('status', request('status'))
            )
            ->when(request()->filled('is_read'), fn ($q) =>
                $q->where('is_read', request('is_read'))
            )
            ->when(request()->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . request('search') . '%')
                  ->orWhere('email', 'like', '%' . request('search') . '%')
                  ->orWhere('subject', 'like', '%' . request('search') . '%')
            )
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(
            ContactResource::collection($contacts),
            'Contacts fetched successfully'
        );
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = Contact::create($request->validated());

        return ApiResponse::success(
            new ContactResource($contact),
            'Message sent successfully',
            201
        );
    }

    public function show(Contact $contact): JsonResponse
    {
        // Automatically mark as read when viewed if unread
        if (!$contact->is_read) {
            $contact->update(['is_read' => true]);
        }

        return ApiResponse::success(
            new ContactResource($contact),
            'Contact fetched successfully'
        );
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $contact->update($request->validated());

        return ApiResponse::success(
            new ContactResource($contact),
            'Contact updated successfully'
        );
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return ApiResponse::success(
            null,
            'Contact deleted successfully'
        );
    }
}