<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Must be a leaf category (checked in the request); a category with products cannot be removed.
            $table->foreignId('category_id')->index()->constrained('categories')->restrictOnDelete();
            $table->string('name', 150);
            // Unique across soft-deleted rows too, so old URLs are never reused.
            $table->string('slug')->unique();
            $table->string('short_description', 300)->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('meta_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->softDeletes();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
