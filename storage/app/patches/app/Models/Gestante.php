<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gestante extends Model
{
    protected $fillable = [
        'nome',
        'data_nascimento',
        'cpf',
        'telefone',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (Gestante $gestante) {
            $gestante->forceFill(['gestante_id' => (string) $gestante->id])->saveQuietly();
        });
    }

    /**
     * Nome para exibição (cadastro antigo pode não ter nome preenchido).
     */
    public function getNomeExibicaoAttribute(): string
    {
        $n = $this->nome;

        return ($n !== null && trim($n) !== '') ? trim($n) : (string) ($this->gestante_id ?? '');
    }

    /**
     * CPF armazenado somente com dígitos (11) ou null.
     */
    public function getCpfFormatadoAttribute(): ?string
    {
        if (! $this->cpf || strlen($this->cpf) !== 11) {
            return $this->cpf;
        }

        return substr($this->cpf, 0, 3).'.'.substr($this->cpf, 3, 3).'.'.substr($this->cpf, 6, 3).'-'.substr($this->cpf, 9, 2);
    }

    /**
     * Telefone em dígitos (DDD + número ou 55…); exibição amigável.
     */
    public function getTelefoneFormatadoAttribute(): ?string
    {
        if (! $this->telefone) {
            return null;
        }

        $d = preg_replace('/\D/', '', $this->telefone);
        if (strlen($d) === 11) {
            return '('.substr($d, 0, 2).') '.substr($d, 2, 5).'-'.substr($d, 7, 4);
        }
        if (strlen($d) === 13 && str_starts_with($d, '55')) {
            return '+55 ('.substr($d, 2, 2).') '.substr($d, 4, 5).'-'.substr($d, 9, 4);
        }
        if (strlen($d) === 10) {
            return '('.substr($d, 0, 2).') '.substr($d, 2, 4).'-'.substr($d, 6, 4);
        }

        return $this->telefone;
    }

    /**
     * Data de nascimento para exibição (dd/mm/aaaa).
     */
    public function getDataNascimentoFormatadaAttribute(): ?string
    {
        if ($this->data_nascimento === null) {
            return null;
        }

        return $this->data_nascimento->format('d/m/Y');
    }

    /**
     * Data de nascimento para input type="date" (aaaa-mm-dd).
     */
    public function getDataNascimentoInputAttribute(): ?string
    {
        if ($this->data_nascimento === null) {
            return null;
        }

        return $this->data_nascimento->format('Y-m-d');
    }

    public function consultas()
    {
        return $this->hasMany(Consulta::class, 'gestante_id');
    }

    public function gestanteWhatsapps()
    {
        return $this->hasMany(GestanteWhatsapp::class, 'gestante_id');
    }

    public function analisesHistorico()
    {
        return $this->hasMany(AnaliseHistorico::class, 'gestante_id')->orderByDesc('created_at');
    }

    public function ultimaAnalise()
    {
        return $this->hasOne(AnaliseHistorico::class, 'gestante_id')->latestOfMany();
    }
}
