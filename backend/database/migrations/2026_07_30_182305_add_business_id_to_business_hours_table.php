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
        Schema::table('business_hours', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')
                ->constrained('businesses')->cascadeOnDelete();
        });

        $businessId = DB::table('businesses')->where('slug', 'negocio-padrao')->value('id');

        if ($businessId) {
            DB::table('business_hours')->update(['business_id' => $businessId]);
        }

        Schema::table('business_hours', function (Blueprint $table) {
            // Antes o dia da semana era único globalmente; agora só precisa ser
            // único dentro do próprio negócio (cada negócio tem seu próprio horário).
            $table->dropUnique(['day_of_week']);
            $table->unique(['business_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_hours', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'day_of_week']);
            $table->dropConstrainedForeignId('business_id');
            $table->unique('day_of_week');
        });
    }
};
