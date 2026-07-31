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
        Schema::table('google_calendar_connections', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')
                ->constrained('businesses')->cascadeOnDelete();
        });

        $businessId = DB::table('businesses')->where('slug', 'negocio-padrao')->value('id');

        if ($businessId) {
            DB::table('google_calendar_connections')->update(['business_id' => $businessId]);
        }

        Schema::table('google_calendar_connections', function (Blueprint $table) {
            // Uma conexão por negócio; antes isso só era garantido apagando a linha
            // anterior no código do app (GoogleCalendarService::handleCallback).
            $table->unique('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_calendar_connections', function (Blueprint $table) {
            $table->dropUnique(['business_id']);
            $table->dropConstrainedForeignId('business_id');
        });
    }
};
