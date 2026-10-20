<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 80);
            $table->string('text', 500);
            $table->unsignedTinyInteger('rating');
            // facebook | messenger | whatsapp | other (App\Enums\TestimonialSource)
            $table->string('source', 20)->default('other');
            $table->string('status')->default('active')->index();
            $table->integer('sort_order')->default(0)->index();
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
        Schema::dropIfExists('testimonials');
    }
};
