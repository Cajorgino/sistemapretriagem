<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->date('data_consulta')->nullable()->change();
            $table->integer('idade_gestacional')->nullable()->change();
            $table->integer('pressao_sistolica')->nullable()->change();
            $table->integer('bpm_materno')->nullable()->change();
            $table->integer('saturacao')->nullable()->change();
            $table->decimal('temperatura_corporal', 4, 1)->nullable()->change();
            $table->integer('altura')->nullable()->change();
            $table->decimal('peso', 5, 2)->nullable()->change();
            $table->integer('frequencia_cardiaca_fetal')->nullable()->change();
            $table->integer('circunferencia_cefalica_fetal_mm')->nullable()->change();
            $table->integer('circunferencia_abdominal_mm')->nullable()->change();
            $table->integer('comprimento_femur_mm')->nullable()->change();
            $table->integer('translucencia_nucal_mm')->nullable()->change();
            $table->string('doppler_ducto_venoso')->nullable()->change();
            $table->string('eixo_cardiaco')->nullable()->change();
            $table->string('quatro_camaras')->nullable()->change();
        });

        Schema::table('consultas', function (Blueprint $table) {
            $table->string('registro_planilha', 32)->nullable()->after('consulta_numero');
            $table->unsignedSmallInteger('idade_crianca_meses')->nullable()->after('idade_materna');
            $table->boolean('prematuridade')->default(false)->after('tipo_gestacao');
            $table->boolean('sindrome_sjogren')->default(false)->after('lupus_eritomatoso_sistemico');
            $table->boolean('hipotireoidismo')->default(false)->after('doencas_tireoidianas');
            $table->boolean('hipertireoidismo')->default(false)->after('hipotireoidismo');
            $table->boolean('eclampsia')->default(false)->after('hipertensao_pre_eclampsia');
            $table->boolean('licitas_teratogenicas')->default(false)->after('uso_litio');
            $table->boolean('infeccao_materna_bacteriana')->default(false)->after('sifilis');
            $table->unsignedTinyInteger('imc_gestacional_categoria')->nullable()->after('imc_pre_gestacional');
        });
    }

    public function down(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->dropColumn([
                'registro_planilha',
                'idade_crianca_meses',
                'prematuridade',
                'sindrome_sjogren',
                'hipotireoidismo',
                'hipertireoidismo',
                'eclampsia',
                'licitas_teratogenicas',
                'infeccao_materna_bacteriana',
                'imc_gestacional_categoria',
            ]);
        });
    }
};
