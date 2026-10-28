<?php

use App\Enums\ActiveStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('sub_title')->nullable();
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('image')->nullable();
            $table->string('status', 20)->default(ActiveStatus::Active->value)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
