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
        Schema::create('gestante_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestante_id')->nullable()->constrained('gestantes')->nullOnDelete();
            $table->text('mensagem');
            $table->string('tipo', 16);
            $table->unsignedInteger('tempo_atendimento')->nullable();
            $table->timestamps();

            $table->index('gestante_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestante_whatsapp');
    }
};
