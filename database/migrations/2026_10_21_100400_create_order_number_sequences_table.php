<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per day, locked while an order number is taken, so concurrent orders never share one.
        Schema::create('order_number_sequences', function (Blueprint $table) {
            $table->char('day', 6)->primary();
            $table->unsignedInteger('last_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_number_sequences');
    }
};
