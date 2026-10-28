<?php

use App\Mail\NewOrderMail;
use App\Models\Order;
use App\Models\OrderItem;

beforeEach(function () {
    $this->order = Order::factory()->create([
        'number' => 'CK-261020-0042',
        'shipping_name' => 'Nusrat Jahan',
        'shipping_phone' => '01712345678',
        'shipping_address' => 'House 12, Road 5, Dhanmondi, Dhaka',
        'customer_note' => 'Call before delivery',
        'delivery_zone_label' => 'Inside City',
        'subtotal' => 330000,
        'delivery_charge' => 7000,
        'total' => 337000,
    ]);
    OrderItem::factory()->quantity(2)->create([
        'order_id' => $this->order->id,
        'product_name' => "Women's Summer Dress",
        'variant_label' => 'S / Pink',
        'unit_price' => 165000,
        'line_total' => 330000,
    ]);
    $this->mail = new NewOrderMail($this->order->load('items'));
});

it('has the order number in the subject', function () {
    $this->mail->assertHasSubject('New order CK-261020-0042');
});

it('shows the customer, area, lines and totals', function () {
    $this->mail
        ->assertSeeInText('CK-261020-0042')
        ->assertSeeInText('Nusrat Jahan')
        ->assertSeeInText('01712345678')
        ->assertSeeInText('Inside City')
        ->assertSeeInText("Women's Summer Dress (S / Pink)")
        ->assertSeeInText('৳3,300')
        ->assertSeeInText('৳70')
        ->assertSeeInText('৳3,370');
});

it('links to the order in the admin panel', function () {
    $this->mail->assertSeeInHtml(route('admin.orders.show', $this->order), false);
});

it('keeps the address and the customer note out of the mail', function () {
    $this->mail
        ->assertDontSeeInText('Dhanmondi')
        ->assertDontSeeInText('Call before delivery');
});
