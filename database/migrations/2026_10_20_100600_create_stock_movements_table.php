<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            // Variants are only soft-deleted, so history is never orphaned.
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            // Signed: +5 / -2. The sum per variant always equals product_variants.stock.
            $table->integer('quantity');
            $table->string('reason');
            // Link to the order that caused the movement (later).
            $table->nullableMorphs('reference');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Rows are never edited, so there is no updated_at.
            $table->timestamp('created_at')->nullable();

            $table->index(['product_variant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
