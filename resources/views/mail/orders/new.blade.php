<x-mail::message>
# New order {{ $order->number }}

**{{ $order->shipping_name }}** · {{ $order->shipping_phone }}
Delivery area: {{ $order->delivery_zone_label }}

<x-mail::table>
| Item | Qty | Total |
| :--- | :-: | ----: |
@foreach ($items as $item)
| {{ $item['name'] }} | {{ $item['quantity'] }} | {{ $item['total'] }} |
@endforeach
</x-mail::table>

Delivery: {{ $deliveryCharge }}
**Total (cash on delivery): {{ $total }}**

<x-mail::button :url="$url">
View order
</x-mail::button>
</x-mail::message>
