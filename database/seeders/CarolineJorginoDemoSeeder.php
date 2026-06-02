<?php

namespace Database\Seeders;

use App\Models\Gestante;
use App\Models\GestanteWhatsapp;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Gestante de exemplo: Caroline Jorgino — consultas + histórico WhatsApp (demo).
 *
 * Telefone: (14) 99776-6598 → armazenado com DDI 55 para mapeamento WAHA/n8n.
 * CPF: 497.390.078-80 (somente dígitos no banco).
 *
 * Uso: php artisan db:seed --class=CarolineJorginoDemoSeeder
 */
class CarolineJorginoDemoSeeder extends Seeder
{
    /** CPF 11 dígitos */
    private const CPF = '49739007880';

    /** (14) 99776-6598 → 55 + 14 + 997766598 */
    private const TELEFONE = '551499776598';

    /** CPF antigo do seeder (remove registro duplicado ao migrar) */
    private const CPF_SEEDER_ANTIGO = '00588776601';

    public function run(): void
    {
        $faker = FakerFactory::create('pt_BR');

        // Remove cadastro antigo do demo com CPF fictício (evita duas Carolines)
        Gestante::query()->where('cpf', self::CPF_SEEDER_ANTIGO)->delete();

        $gestante = Gestante::query()->updateOrCreate(
            ['cpf' => self::CPF],
            [
                'nome' => 'Caroline Jorgino',
                'data_nascimento' => '1994-08-12',
                'telefone' => self::TELEFONE,
            ]
        );

        // garante gestante_id = id (boot pode já ter rodado em create)
        if ((string) $gestante->gestante_id !== (string) $gestante->id) {
            $gestante->forceFill(['gestante_id' => (string) $gestante->id])->saveQuietly();
            $gestante->refresh();
        }

        // Remove consultas anteriores desta demo para reexecutar o seeder sem duplicar
        DB::table('consultas')->where('gestante_id', $gestante->id)->delete();

        $base = Carbon::now()->subMonths(3)->startOfMonth();

        $consultas = [
            ['num' => 1, 'semanas' => 12, 'weeks_offset' => 0],
            ['num' => 2, 'semanas' => 20, 'weeks_offset' => 6],
            ['num' => 3, 'semanas' => 28, 'weeks_offset' => 12],
        ];

        foreach ($consultas as $item) {
            $data = (clone $base)->addWeeks($item['weeks_offset'])->addDays(2);
            DemoConsultaFactory::create(
                $faker,
                $gestante->id,
                $item['num'],
                $data,
                $item['semanas']
            );
        }

        // Histórico WhatsApp (substitui mensagens demo anteriores desta gestante)
        GestanteWhatsapp::query()->where('gestante_id', $gestante->id)->delete();

        $baseMsg = now()->subDays(2)->startOfHour();
        $turnos = [
            ['tipo' => 'entrada', 'mensagem' => 'Oi, sou a Caroline. Estou com 24 semanas e queria tirar uma dúvida sobre o ecocardiograma fetal.', 'after' => 0],
            ['tipo' => 'saida', 'mensagem' => 'Olá, Caroline! Pode enviar sua dúvida com calma. O ecocardio fetal avalia a estrutura e a função do coração do bebê.', 'after' => 120],
            ['tipo' => 'entrada', 'mensagem' => 'O médico falou em “quatro câmaras” — isso quer dizer que está tudo certo?', 'after' => 380],
            ['tipo' => 'saida', 'mensagem' => 'A visualização em quatro câmaras é uma parte importante do exame. O laudo completo descreve se as estruturas estão dentro do esperado para a idade gestacional. Se tiver o PDF, pode anexar na próxima consulta.', 'after' => 560],
            ['tipo' => 'entrada', 'mensagem' => 'Certo, obrigada! Já agendo retorno com os exames.', 'after' => 890],
            ['tipo' => 'saida', 'mensagem' => 'Por nada! Qualquer coisa estamos por aqui. Bom acompanhamento pré-natal.', 'after' => 1020],
        ];

        $prev = null;
        foreach ($turnos as $t) {
            $created = $baseMsg->copy()->addSeconds($t['after']);
            $tempo = $prev !== null ? (int) abs($prev->diffInSeconds($created)) : null;

            GestanteWhatsapp::query()->create([
                'gestante_id' => $gestante->id,
                'mensagem' => $t['mensagem'],
                'tipo' => $t['tipo'],
                'tempo_atendimento' => $tempo,
                'created_at' => $created,
                'updated_at' => $created,
            ]);

            $prev = $created;
        }

        $this->command?->info(sprintf(
            'Caroline Jorgino — gestante id %d · %d consulta(s) · WhatsApp: %d mensagem(ns). Tel. %s · CPF %s',
            $gestante->id,
            count($consultas),
            count($turnos),
            self::TELEFONE,
            self::CPF
        ));
    }
}
