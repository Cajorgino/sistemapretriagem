<?php

namespace App\Console\Commands;

use App\Models\Gestante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedGestanteWhatsappHistoricoCommand extends Command
{
    protected $signature = 'whatsapp:seed-historico
                            {gestante_id? : ID (PK) da gestante na tabela gestantes}';

    protected $description = 'Insere mensagens de exemplo em gestante_whatsapp para uma gestante já cadastrada.';

    public function handle(): int
    {
        $id = $this->argument('gestante_id');
        $gestante = $id !== null
            ? Gestante::query()->find($id)
            : Gestante::query()->orderBy('id')->first();

        if ($gestante === null) {
            $this->error($id !== null
                ? "Nenhuma gestante encontrada com id {$id}."
                : 'Não há gestantes cadastradas. Cadastre uma gestante antes de rodar este comando.');

            return self::FAILURE;
        }

        $base = now()->subHours(2);

        $linhas = [
            ['tipo' => 'entrada', 'mensagem' => 'Bom dia, preciso confirmar o horário da próxima consulta.', 'segundos' => 0],
            ['tipo' => 'saida', 'mensagem' => 'Olá! Sua consulta está agendada para quinta-feira, 10h. Posso ajudar em mais algo?', 'segundos' => 95],
            ['tipo' => 'entrada', 'mensagem' => 'Perfeito, obrigada. Posso levar os exames em PDF?', 'segundos' => 210],
            ['tipo' => 'saida', 'mensagem' => 'Sim, pode enviar por aqui ou trazer impresso no dia. Até lá!', 'segundos' => 340],
            ['tipo' => 'entrada', 'mensagem' => 'Combinado, até logo.', 'segundos' => 430],
        ];

        $prev = null;
        foreach ($linhas as $linha) {
            $created = $base->copy()->addSeconds($linha['segundos']);
            $tempo = $prev !== null
                ? (int) abs($prev->diffInSeconds($created))
                : null;

            DB::table('gestante_whatsapp')->insert([
                'gestante_id' => $gestante->id,
                'mensagem' => $linha['mensagem'],
                'tipo' => $linha['tipo'],
                'tempo_atendimento' => $tempo,
                'created_at' => $created,
                'updated_at' => $created,
            ]);

            $prev = $created;
        }

        $this->info("Histórico de WhatsApp criado para a gestante #{$gestante->id} ({$gestante->gestante_id}).");
        $this->line('Acesse Histórico de atendimento no sistema para ver a conversa.');

        return self::SUCCESS;
    }
}
