<?php

namespace App\Http\Requests\Admin\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionOrderRequest extends FormRequest
{
    /** Moves that need a reason on record. */
    private const NOTE_REQUIRED_FOR = [OrderStatus::Cancelled, OrderStatus::Returned];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Order $order */
        $order = $this->route('order');
        $allowed = array_map(fn (OrderStatus $status) => $status->value, $order->status->allowedNext());

        return [
            'to' => ['required', Rule::enum(OrderStatus::class), Rule::in($allowed)],
            'note' => [
                Rule::requiredIf(fn () => in_array($this->enum('to', OrderStatus::class), self::NOTE_REQUIRED_FOR, true)),
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function target(): OrderStatus
    {
        return OrderStatus::from($this->validated('to'));
    }

    public function note(): ?string
    {
        return $this->validated('note');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.in' => 'This order cannot move to that status.',
            'note.required' => 'Add a note explaining why.',
        ];
    }
}
