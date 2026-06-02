<?php

namespace Database\Seeders;

use App\Models\Consulta;
use Carbon\Carbon;
use Faker\Generator;

/**
 * Atributos fictícios de consulta para seeders (sem disparar WhatsApp).
 */
final class DemoConsultaFactory
{
    public static function attributes(
        Generator $faker,
        int $gestanteId,
        int $consultaNumero,
        Carbon|string $dataConsulta,
        int $idadeGestacional
    ): array {
        $data = $dataConsulta instanceof Carbon ? $dataConsulta->format('Y-m-d') : $dataConsulta;
        $chd = $faker->boolean(18);

        return [
            'gestante_id' => $gestanteId,
            'consulta_numero' => $consultaNumero,
            'data_consulta' => $data,
            'idade_gestacional' => $idadeGestacional,
            'pressao_sistolica' => $faker->numberBetween(100, 130),
            'bpm_materno' => $faker->numberBetween(68, 98),
            'saturacao' => $faker->numberBetween(96, 100),
            'temperatura_corporal' => round($faker->randomFloat(1, 36.0, 37.2), 1),
            'altura' => $faker->numberBetween(152, 176),
            'peso' => round($faker->randomFloat(2, 52, 92), 2),
            'glicemia_jejum' => $faker->optional(0.7)->randomFloat(2, 65, 99),
            'glicemia_pos_prandial' => $faker->optional(0.5)->randomFloat(2, 90, 140),
            'hba1c' => $faker->optional(0.4)->randomFloat(2, 4.8, 6.2),
            'diabetes_gestacional' => $faker->boolean(12),
            'hipertensao' => $faker->boolean(15),
            'hipertensao_pre_eclampsia' => $faker->boolean(8),
            'obesidade_pre_gestacional' => $faker->boolean(10),
            'historico_familiar_chd' => $faker->boolean(12),
            'uso_medicamentos' => $faker->boolean(25),
            'tabagismo' => $faker->boolean(5),
            'alcoolismo' => $faker->boolean(4),
            'frequencia_cardiaca_fetal' => $faker->numberBetween(125, 158),
            'circunferencia_cefalica_fetal_mm' => $faker->numberBetween(210, 295),
            'circunferencia_abdominal_mm' => $faker->numberBetween(185, 255),
            'comprimento_femur_mm' => $faker->numberBetween(42, 58),
            'translucencia_nucal_mm' => $faker->numberBetween(1, 3),
            'doppler_ducto_venoso' => $faker->randomElement(['Normal', 'Alterado leve', 'Adequado para IG']),
            'eixo_cardiaco' => $faker->randomElement(['Normal', 'Leve desvio', 'Eixo preservado']),
            'quatro_camaras' => $faker->randomElement(['Visualização adequada', 'Simétrico', 'Câmaras balanceadas']),
            'chd_confirmada' => $chd,
            'tipo_chd' => $chd ? $faker->randomElement(['VSD', 'ASD', 'Coarctação', 'Estenose pulmonar']) : null,
        ];
    }

    public static function create(
        Generator $faker,
        int $gestanteId,
        int $consultaNumero,
        Carbon|string $dataConsulta,
        int $idadeGestacional
    ): Consulta {
        return Consulta::create(self::attributes($faker, $gestanteId, $consultaNumero, $dataConsulta, $idadeGestacional));
    }
}
