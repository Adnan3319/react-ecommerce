<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('client_name')->nullable();
            $table->string('location')->nullable();
            $table->string('completion_date')->nullable();
            $table->string('budget')->nullable();
            $table->longText('description')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('status')->default('Active'); // Active / Inactive
            $table->softDeletes();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
