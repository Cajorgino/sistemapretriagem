<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestantes', function (Blueprint $table) {
            $table->string('nome', 255)->nullable()->after('gestante_id');
        });

        $rows = DB::table('gestantes')->whereNull('nome')->get();
        foreach ($rows as $row) {
            $nome = $row->gestante_id !== null && trim((string) $row->gestante_id) !== ''
                ? trim((string) $row->gestante_id)
                : 'Gestante #'.$row->id;
            DB::table('gestantes')->where('id', $row->id)->update([
                'nome' => $nome,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('gestantes', function (Blueprint $table) {
            $table->dropColumn('nome');
        });
    }
};
