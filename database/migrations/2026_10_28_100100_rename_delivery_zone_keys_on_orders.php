<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RENAMES = [
        'inside_dhaka' => 'inside',
        'outside_dhaka' => 'outside',
    ];

    /**
     * Zone keys no longer name a city. The label snapshot on each order ("Inside Dhaka") stays as it was placed.
     */
    public function up(): void
    {
        foreach (self::RENAMES as $old => $new) {
            DB::table('orders')->where('delivery_zone', $old)->update(['delivery_zone' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $old => $new) {
            DB::table('orders')->where('delivery_zone', $new)->update(['delivery_zone' => $old]);
        }
    }
};
