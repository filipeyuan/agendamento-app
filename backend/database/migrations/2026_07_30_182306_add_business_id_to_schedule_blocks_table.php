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
        Schema::table('schedule_blocks', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')
                ->constrained('businesses')->cascadeOnDelete();
            $table->index(['business_id', 'date']);
        });

        $businessId = DB::table('businesses')->where('slug', 'negocio-padrao')->value('id');

        if ($businessId) {
            DB::table('schedule_blocks')->update(['business_id' => $businessId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_blocks', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'date']);
            $table->dropConstrainedForeignId('business_id');
        });
    }
};
