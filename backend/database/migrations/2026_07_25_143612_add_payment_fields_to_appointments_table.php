<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('status');
            $table->string('stripe_checkout_session_id')->nullable()->after('payment_status');
        });

        DB::table('appointments')
            ->whereIn('status', ['confirmed', 'completed'])
            ->update(['payment_status' => 'paid']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'stripe_checkout_session_id']);
        });
    }
};
