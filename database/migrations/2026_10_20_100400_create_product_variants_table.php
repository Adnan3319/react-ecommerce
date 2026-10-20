<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->index()->constrained('products')->cascadeOnDelete();
            // Unique across soft-deleted rows too.
            $table->string('sku', 64)->unique();
            // Minor units (paisa). sale_price < price is validated in the request.
            $table->unsignedInteger('price');
            $table->unsignedInteger('sale_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            // Must belong to the same product (checked in the request).
            $table->foreignId('product_image_id')->nullable()->constrained('product_images')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);

            // The variant's value ids sorted and joined with "-" ("12-47"); "default" for a simple product.
            // Always written together with the variant_selections rows by ProductVariant::syncVariantValues().
            $table->string('combination_key', 64)->default('default');
            $table->softDeletes();
            // Mirrors combination_key for live rows and is NULL once soft-deleted: NULLs never collide,
            // so the unique index below allows one live variant per combination and lets a deleted
            // combination be added again.
            $table->string('live_key', 64)->nullable()->storedAs('CASE WHEN deleted_at IS NULL THEN combination_key END');
            $table->timestamps();

            $table->unique(['product_id', 'live_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
