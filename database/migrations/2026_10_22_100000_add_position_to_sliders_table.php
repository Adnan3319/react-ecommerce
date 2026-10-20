<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            // hero | promo (App\Enums\SliderPosition); existing sliders are hero slides.
            $table->string('position', 20)->default('hero')->index()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            $table->dropIndex(['position']);
            $table->dropColumn('position');
        });
    }
};
