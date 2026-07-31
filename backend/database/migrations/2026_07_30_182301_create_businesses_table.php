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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Backfill: se já existe um admin (produção de single-tenant), cria um negócio
        // padrão pra ele ser dono. Num banco vazio (testes locais), não cria nada.
        if (DB::table('users')->where('role', 'admin')->exists()) {
            DB::table('businesses')->insert([
                'name' => 'Negócio Padrão',
                'slug' => 'negocio-padrao',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
