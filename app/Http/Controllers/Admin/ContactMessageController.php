<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Inertia\Inertia;

class ContactMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(10);

        return Inertia::render('Admin/ContactMessages/Index', [
            'messages' => $messages,
        ]);
    }

    public function markAsRead(ContactMessage $contactMessage)
    {
        $contactMessage->update(['is_read' => true]);

        return back();
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return back();
    }
}