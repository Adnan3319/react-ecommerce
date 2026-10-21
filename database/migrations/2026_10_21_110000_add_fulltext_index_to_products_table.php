<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'products_search_fulltext';

    public function up(): void
    {
        if (! $this->isMysql()) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(['name', 'short_description'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! $this->isMysql()) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText(self::INDEX);
        });
    }

    private function isMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
