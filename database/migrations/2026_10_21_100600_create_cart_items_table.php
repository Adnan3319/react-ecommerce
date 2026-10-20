<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            // Random token kept in the visitor's cookie; guests have no account to attach a cart to.
            $table->char('cart_token', 40)->index();
            // Variants are soft-deleted, so this only cascades on a real purge.
            $table->foreignId('product_variant_id')->index()->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            // Unit price (minor units) when the line was added or last changed, to flag later price changes.
            $table->unsignedInteger('price_at_add');
            $table->timestamps();

            $table->unique(['cart_token', 'product_variant_id']);
            // Used by the nightly prune.
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
