<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreContactMessageRequest;
use App\Services\Contact\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    private const SENT_MESSAGE = 'Thanks! Your message has been sent. We will get back to you soon.';

    public function __construct(private readonly ContactMessageService $messages) {}

    public function index(): Response
    {
        return Inertia::render('Contact');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        // A bot gets the same answer as a visitor, so it learns nothing from the response.
        if (! $request->isSpam()) {
            $this->messages->create($request->messageData());
        }

        Inertia::flash('success', self::SENT_MESSAGE);

        return back();
    }
}
