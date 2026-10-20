<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A variant value belongs to a head (M under Size, Pink under Color).
     */
    public function up(): void
    {
        Schema::create('variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_head_id')->constrained('variant_heads')->cascadeOnDelete();
            $table->string('value', 50);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['variant_head_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_values');
    }
};
