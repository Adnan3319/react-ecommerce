<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactMessage\IndexContactMessageRequest;
use App\Http\Resources\Admin\ContactMessage\ContactMessageResource;
use App\Models\ContactMessage;
use App\Services\Contact\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactMessageService $messages) {}

    public function index(IndexContactMessageRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Admin/Messages/Index', [
            'messages' => ContactMessageResource::collection($this->messages->paginate($filters)),
            'filters' => $filters,
        ]);
    }

    public function show(ContactMessage $contactMessage): Response
    {
        $this->messages->markRead($contactMessage);

        $ordersCount = $this->messages->ordersCount($contactMessage);

        return Inertia::render('Admin/Messages/Show', [
            'message' => ContactMessageResource::make($contactMessage->refresh())->resolve(),
            'orders' => [
                'count' => $ordersCount,
                'url' => $ordersCount > 0 ? route('admin.orders.index', ['search' => $contactMessage->mobile], absolute: false) : null,
            ],
        ]);
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $this->messages->delete($contactMessage);

        Inertia::flash('success', 'Message deleted.');

        return to_route('admin.messages.index');
    }
}
