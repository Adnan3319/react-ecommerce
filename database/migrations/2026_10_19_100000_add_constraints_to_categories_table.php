<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')->update(['status' => DB::raw('LOWER(status)')]);

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            // Soft-deleted rows keep their slug reserved, so old URLs are never reused.
            $table->unique('slug');
            $table->index('status');
            $table->index('sort_order');
            $table->string('status')->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['status']);
            $table->dropUnique(['slug']);
            $table->index('slug');
            $table->string('status')->default('Active')->change();
        });

        DB::table('categories')->update(['status' => DB::raw('CONCAT(UPPER(LEFT(status, 1)), SUBSTRING(status, 2))')]);
    }
};
