<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which value a variant picked for each head ("M" for Size, "Pink" for Color).
     * "Exactly one value per head of the product" is enforced by the product service.
     */
    public function up(): void
    {
        Schema::create('variant_selections', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            // Restrict: a value that variants use cannot be deleted.
            $table->foreignId('variant_value_id')->index()->constrained('variant_values')->restrictOnDelete();

            $table->primary(['product_variant_id', 'variant_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_selections');
    }
};
