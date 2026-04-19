<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Alinha gestante_id ao id numérico (sequencial), para não depender de valor digitado.
     */
    public function up(): void
    {
        foreach (DB::table('gestantes')->orderBy('id')->cursor() as $row) {
            DB::table('gestantes')->where('id', $row->id)->update([
                'gestante_id' => (string) $row->id,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // irreversível sem backup do valor anterior
    }
};
