<?php

namespace Database\Seeders;

use App\Models\Gestante;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

/**
 * Dados fictícios para montar o dashboard em apresentações.
 *
 * Cria gestantes **e** várias consultas por gestante.
 *
 * Telefones usam DDD 10 (inexistente no plano brasileiro) — não correspondem a linhas reais.
 * CPFs começam com 003… para você poder remover tudo de uma vez se precisar.
 *
 * Não dispara WhatsApp (somente o cadastro via GestanteController faz isso).
 *
 * Uso: php artisan db:seed --class=PresentationDataSeeder
 *
 * Para só preencher consultas em gestantes já existentes (sem CPF demo):
 * php artisan db:seed --class=DemoConsultasForGestantesSeeder
 */
class PresentationDataSeeder extends Seeder
{
    private const GESTANTES_COUNT = 18;

    public function run(): void
    {
        $faker = FakerFactory::create('pt_BR');

        // Remove execuções anteriores deste demo (cascade apaga consultas)
        Gestante::query()->where('cpf', 'like', '003%')->delete();

        $baseDate = Carbon::now()->subMonths(4)->startOfMonth();

        for ($i = 1; $i <= self::GESTANTES_COUNT; $i++) {
            $gestante = Gestante::create([
                'nome' => $faker->name('female'),
                'data_nascimento' => $faker->dateTimeBetween('-38 years', '-22 years')->format('Y-m-d'),
                'cpf' => sprintf('003%08d', $i),
                // DDD 10 não existe no Brasil — formato válido de dígitos, sem risco de SMS em massa
                'telefone' => sprintf('109%08d', $i),
            ]);

            $numConsultas = $faker->numberBetween(2, 5);
            for ($c = 1; $c <= $numConsultas; $c++) {
                $semanas = $faker->numberBetween(8 + $c * 4, min(40, 12 + $c * 8));
                $dataConsulta = (clone $baseDate)->addWeeks($i + $c * 2)->addDays($faker->numberBetween(0, 6));

                DemoConsultaFactory::create(
                    $faker,
                    $gestante->id,
                    $c,
                    $dataConsulta,
                    $semanas
                );
            }
        }
    }
}
