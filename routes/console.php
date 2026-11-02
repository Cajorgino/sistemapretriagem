<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| A base clínica é a planilha importada. O sync da API não deve repor
| o conjunto antigo.
|--------------------------------------------------------------------------
*/

Schedule::command('analises:reprocess --only-missing')
    ->cron('5,20,35,50 * * * *')
    ->withoutOverlapping(40)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/analises-reprocess.log'));

/*
|--------------------------------------------------------------------------
| Lembretes WhatsApp (agendado_para = hoje)
|--------------------------------------------------------------------------
| Requer WHATSAPP_AUTO_LEMBRETE=true, WAHA WORKING e containers scheduler + queue.
*/
Schedule::command('whatsapp:lembretes-agendados')
    ->dailyAt('08:00')
    ->timezone(config('app.timezone', 'America/Sao_Paulo'))
    ->withoutOverlapping(120)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/whatsapp-lembretes.log'));
