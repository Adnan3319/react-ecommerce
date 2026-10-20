<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\AddOrderNoteRequest;
use App\Http\Requests\Admin\Order\IndexOrderRequest;
use App\Http\Requests\Admin\Order\TransitionOrderRequest;
use App\Http\Resources\Admin\Order\OrderDetailResource;
use App\Http\Resources\Admin\Order\OrderListResource;
use App\Models\Order;
use App\Services\Order\OrderQueryService;
use App\Services\Order\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderQueryService $orders,
        private readonly OrderStatusService $statuses,
    ) {}

    public function index(IndexOrderRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => OrderListResource::collection($this->orders->paginate($filters)),
            'filters' => [
                'search' => $filters['search'],
                'order_status' => $filters['status']?->value,
                'source' => $filters['source']?->value,
                'range' => $filters['range'],
                'per_page' => $filters['per_page'],
            ],
            'statuses' => $this->statusTabs(),
            'sources' => array_map(
                fn (OrderSource $source) => ['value' => $source->value, 'label' => $source->label()],
                OrderSource::cases(),
            ),
        ]);
    }

    public function show(Order $order): Response
    {
        $this->orders->detail($order);

        return Inertia::render('Admin/Orders/Show', [
            'order' => (new OrderDetailResource($order, $this->orders->customerHistory($order)))->resolve(),
        ]);
    }

    public function transition(TransitionOrderRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->statuses->transition($order, $request->target(), $request->note(), $request->user());
        } catch (InvalidOrderTransition) {
            // Another admin moved the order after the request was validated.
            throw ValidationException::withMessages(['to' => 'This order was just changed. Reload and try again.']);
        }

        Inertia::flash('success', 'Order updated.');

        return back();
    }

    public function note(AddOrderNoteRequest $request, Order $order): RedirectResponse
    {
        $this->statuses->addNote($order, $request->validated('note'), $request->user());

        Inertia::flash('success', 'Note added.');

        return back();
    }

    /**
     * One tab per status, in lifecycle order, with its order count.
     *
     * @return list<array{value: string, label: string, color: string, count: int}>
     */
    private function statusTabs(): array
    {
        $counts = $this->orders->statusCounts();

        return array_map(
            fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $counts[$status->value],
            ],
            OrderStatus::cases(),
        );
    }
}
