<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('city', 100)->default('Dhaka')->after('address');
            $table->string('latitude')->nullable()->after('city');
            $table->string('longitude')->nullable()->after('latitude');
            // Minor units; the defaults match the charges that were in config/shop.php.
            $table->unsignedInteger('delivery_charge_inside')->default(7000)->after('about_text');
            $table->unsignedInteger('delivery_charge_outside')->default(13000)->after('delivery_charge_inside');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['city', 'latitude', 'longitude', 'delivery_charge_inside', 'delivery_charge_outside']);
        });
    }
};
