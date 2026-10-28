<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notification\IndexNotificationRequest;
use App\Http\Resources\Admin\Notification\NotificationResource;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    private const ADMIN_PATH_PREFIX = '/admin/';

    public function __construct(private readonly NotificationService $notifications) {}

    public function index(IndexNotificationRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Admin/Notifications/Index', [
            // `notifications` is the shared bell prop, so the full list uses its own name.
            'feed' => NotificationResource::collection($this->notifications->paginate($request->user(), $filters)),
            'filters' => ['type' => $filters['type'], 'status' => $filters['status']],
        ]);
    }

    /**
     * Marks the notification read and opens the page it points to.
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $url = $this->notifications->markRead($request->user(), $id)->data['url'] ?? '';

        return redirect()->to($this->isAdminPath($url) ? $url : route('admin.notifications.index', absolute: false));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        return back();
    }

    /**
     * Only same-site admin paths are followed; anything else (an absolute URL, `//host`) falls back to the list.
     */
    private function isAdminPath(string $url): bool
    {
        return str_starts_with($url, self::ADMIN_PATH_PREFIX) && ! str_starts_with($url, '//');
    }
}
