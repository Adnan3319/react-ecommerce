<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->index()->constrained('products')->cascadeOnDelete();
            // Main file; the `-thumb` variant sits beside it (config/media.php `product` preset).
            $table->string('path');
            $table->boolean('is_main')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // At most one main image per product: the expression is NULL for gallery rows and NULLs never collide.
        DB::statement('CREATE UNIQUE INDEX product_images_one_main_unique ON product_images (product_id, (CASE WHEN is_main = 1 THEN 1 END))');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
