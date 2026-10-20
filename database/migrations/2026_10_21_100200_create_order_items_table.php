<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->index()->constrained('orders')->restrictOnDelete();
            // Restrict: a product or variant that was ordered can never be hard-deleted.
            $table->foreignId('product_id')->index()->constrained('products')->restrictOnDelete();
            $table->foreignId('product_variant_id')->index()->constrained('product_variants')->restrictOnDelete();

            // Snapshot at purchase time.
            $table->string('product_name', 150);
            $table->string('variant_label')->nullable();
            $table->string('sku', 64);
            $table->string('image_path')->nullable();

            // Minor units.
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('line_total');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
