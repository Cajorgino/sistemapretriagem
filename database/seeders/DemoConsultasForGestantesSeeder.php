<?php

namespace Database\Seeders;

use App\Models\Gestante;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

/**
 * Cria consultas fictícias para gestantes que ainda não têm nenhuma consulta.
 * Útil para preencher o dashboard quando você já cadastrou gestantes manualmente.
 *
 * Não dispara WhatsApp.
 *
 * Uso: php artisan db:seed --class=DemoConsultasForGestantesSeeder
 */
class DemoConsultasForGestantesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create('pt_BR');

        $gestantes = Gestante::query()
            ->whereDoesntHave('consultas')
            ->orderBy('id')
            ->get();

        if ($gestantes->isEmpty()) {
            $this->command?->info('Nenhuma gestante sem consultas. Nada a fazer.');

            return;
        }

        $inicio = Carbon::now()->subMonths(5)->startOfMonth();

        foreach ($gestantes as $gestante) {
            $numConsultas = $faker->numberBetween(2, 4);

            for ($c = 1; $c <= $numConsultas; $c++) {
                $dataConsulta = (clone $inicio)
                    ->addWeeks(($gestante->id % 3) + $c * 5)
                    ->addDays($faker->numberBetween(0, 10));

                $semanas = min(40, max(8, 10 + $c * 7 + $faker->numberBetween(-2, 6)));

                DemoConsultaFactory::create(
                    $faker,
                    $gestante->id,
                    $c,
                    $dataConsulta,
                    $semanas
                );
            }
        }

        $this->command?->info(sprintf(
            'Consultas criadas para %d gestante(s).',
            $gestantes->count()
        ));
    }
}
