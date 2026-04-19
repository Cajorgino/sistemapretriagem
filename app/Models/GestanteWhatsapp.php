<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GestanteWhatsapp extends Model
{
    protected $table = 'gestante_whatsapp';

    protected $fillable = [
        'gestante_id',
        'mensagem',
        'tipo',
        'tempo_atendimento',
    ];

    protected function casts(): array
    {
        return [
            'tempo_atendimento' => 'integer',
        ];
    }

    public function gestante(): BelongsTo
    {
        return $this->belongsTo(Gestante::class);
    }

    public function getTempoAtendimentoFormatadoAttribute(): ?string
    {
        if ($this->tempo_atendimento === null) {
            return null;
        }

        $s = (int) $this->tempo_atendimento;
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;

        if ($h > 0) {
            return sprintf('%dh %dm %ds', $h, $m, $sec);
        }
        if ($m > 0) {
            return sprintf('%dm %ds', $m, $sec);
        }

        return sprintf('%ds', $sec);
    }
}
