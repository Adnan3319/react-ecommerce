<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // CK-YYMMDD-NNNN; the unique index is what keeps concurrent orders apart.
            $table->string('number', 20)->unique();
            // Used in the success page URL instead of the id.
            $table->char('public_token', 32)->unique();
            $table->foreignId('customer_id')->index()->constrained('customers')->restrictOnDelete();

            $table->string('status');
            $table->string('payment_method');
            $table->string('payment_status');
            $table->string('source');

            // Minor units.
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('delivery_charge');
            $table->unsignedBigInteger('total');

            // Snapshot of what the customer typed.
            $table->string('shipping_name', 100);
            $table->string('shipping_phone', 11);
            $table->text('shipping_address');
            $table->string('delivery_zone', 50);
            $table->string('delivery_zone_label', 100);
            $table->string('customer_note', 500)->nullable();

            // Sent by the order form so a double click never creates two orders.
            $table->string('idempotency_key', 64)->unique();

            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('fbclid')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('shipping_phone');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
