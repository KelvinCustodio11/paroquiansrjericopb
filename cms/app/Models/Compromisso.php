<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compromisso extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo', 'data', 'hora', 'tipo', 'local', 'responsavel', 'publico', 'observacao',
    ];

    protected $casts = [
        'data'    => 'date',
        'publico' => 'boolean',
    ];

    public function toJsonExport(): array
    {
        return array_filter([
            'titulo'      => $this->titulo,
            'data'        => $this->data?->format('Y-m-d'),
            'hora'        => $this->hora,
            'tipo'        => $this->tipo,
            'local'       => $this->local,
            'responsavel' => $this->responsavel,
            'publico'     => $this->publico,
            'observacao'  => $this->observacao,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
