<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            // Null for the "placed" row.
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('note', 500)->nullable();
            // Null when the customer or the system made the change.
            $table->foreignId('changed_by')->nullable()->index()->constrained('users')->nullOnDelete();
            // Rows are never edited, so there is no updated_at.
            $table->timestamp('created_at')->nullable();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
