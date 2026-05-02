<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testemunho extends Model
{
    protected $table = 'testemunhos';

    protected $fillable = [
        'nome', 'email', 'cidade', 'texto',
        'status', 'consentimento_lgpd', 'motivo_rejeicao', 'aprovado_em',
    ];

    protected $casts = [
        'consentimento_lgpd' => 'boolean',
        'aprovado_em'        => 'datetime',
    ];

    public function scopeAprovados($query)
    {
        return $query->where('status', 'aprovado');
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    public function toJsonExport(): array
    {
        return array_filter([
            'id'          => $this->id,
            'nome'        => $this->nome,
            'cidade'      => $this->cidade,
            'texto'       => $this->texto,
            'aprovado_em' => $this->aprovado_em?->format('Y-m-d'),
        ], static fn ($v) => $v !== null && $v !== '');
    }
}
