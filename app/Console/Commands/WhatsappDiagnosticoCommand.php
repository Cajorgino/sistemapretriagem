<?php

namespace App\Console\Commands;

use App\Http\Controllers\GestanteWhatsappController;
use App\Models\Gestante;
use App\Models\GestanteWhatsapp;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsappDiagnosticoCommand extends Command
{
    protected $signature = 'whatsapp:diagnostico';

    protected $description = 'Mostra contagens e o resultado da mesma consulta do histórico web (para depuração).';

    public function handle(): int
    {
        $this->line('Conexão: '.config('database.default'));
        $this->line('Database: '.(string) config('database.connections.'.config('database.default').'.database'));

        $this->newLine();
        $this->info('Contagens');
        $this->table(
            ['Tabela / métrica', 'Valor'],
            [
                ['gestantes', (string) Gestante::query()->count()],
                ['gestante_whatsapp (total)', (string) GestanteWhatsapp::query()->count()],
                ['gestante_whatsapp (gestante_id preenchido)', (string) GestanteWhatsapp::query()->whereNotNull('gestante_id')->count()],
                ['gestante_whatsapp (gestante_id NULL)', (string) GestanteWhatsapp::query()->whereNull('gestante_id')->count()],
            ]
        );

        $controller = app(GestanteWhatsappController::class);
        $ref = new \ReflectionClass($controller);
        $build = $ref->getMethod('buildHistoricoResumoQuery');
        $build->setAccessible(true);
        $query = $build->invoke($controller, Request::create('/historico-atendimento-whatsapp', 'GET'));

        $this->newLine();
        $this->info('Consulta do histórico (sem filtros)');
        $this->line($query->toSql());
        $this->line('Bindings: '.json_encode($query->getBindings()));

        $count = $query->count();
        $this->newLine();
        $this->line("Linhas retornadas pela consulta: {$count}");

        if ($count === 0 && GestanteWhatsapp::query()->whereNotNull('gestante_id')->exists()) {
            $this->warn('Há mensagens com gestante_id, mas o JOIN não retornou linhas. Verifique se os IDs em gestante_whatsapp existem em gestantes.id.');
            $orphan = DB::table('gestante_whatsapp as gw')
                ->leftJoin('gestantes as g', 'g.id', '=', 'gw.gestante_id')
                ->whereNotNull('gw.gestante_id')
                ->whereNull('g.id')
                ->count();
            $this->line("Mensagens com gestante_id órfão (sem gestante correspondente): {$orphan}");
        }

        return self::SUCCESS;
    }
}
