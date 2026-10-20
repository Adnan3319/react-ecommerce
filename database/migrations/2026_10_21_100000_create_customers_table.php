<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // Last name used by this customer.
            $table->string('name', 100);
            // Normalized 01XXXXXXXXX; customers are matched by phone.
            $table->string('phone', 11)->unique();
            $table->boolean('is_blocked')->default(false);
            // Counters for the admin "order history" card.
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('cancelled_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
