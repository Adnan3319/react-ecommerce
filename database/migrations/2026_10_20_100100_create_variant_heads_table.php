<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A variant head is an option name of one product (Size, Color). How many a product may
     * have is a business rule (config shop.max_variant_heads), not a schema limit.
     */
    public function up(): void
    {
        Schema::create('variant_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 30);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'name']);
            $table->unique(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_heads');
    }
};
