<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gestantes', function (Blueprint $table) {
            $table->string('cpf', 11)->nullable()->unique()->after('data_nascimento');
            $table->string('telefone', 20)->nullable()->unique()->after('cpf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestantes', function (Blueprint $table) {
            $table->dropUnique(['cpf']);
            $table->dropUnique(['telefone']);
            $table->dropColumn(['cpf', 'telefone']);
        });
    }
};
