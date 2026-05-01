<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'titulo',
        'subtitulo',
        'data_inicio',
        'data_fim',
        'hora_inicio',
        'local',
        'categoria',
        'status',
        'resumo',
        'conteudo',
        'imagem_capa',
        'programacao',
        'inscricao',
        'publicado',
        'destaque',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'programacao' => 'array',
        'inscricao' => 'array',
        'publicado' => 'boolean',
        'destaque' => 'boolean',
    ];

    /**
     * Serializa o model para o formato exato esperado em data/eventos.json,
     * compativel com schemas/evento.schema.json e build-content.js.
     */
    public function toJsonExport(): array
    {
        return array_filter([
            'slug' => $this->slug,
            'titulo' => $this->titulo,
            'subtitulo' => $this->subtitulo,
            'data_inicio' => $this->data_inicio?->format('Y-m-d'),
            'data_fim' => $this->data_fim?->format('Y-m-d'),
            'hora_inicio' => $this->hora_inicio,
            'local' => $this->local,
            'categoria' => $this->categoria,
            'status' => $this->status,
            'resumo' => $this->resumo,
            'conteudo' => $this->conteudo,
            'imagem_capa' => $this->imagem_capa,
            'programacao' => $this->programacao,
            'inscricao' => $this->inscricao,
            'publicado' => $this->publicado,
            'destaque' => $this->destaque,
        ], static fn ($v) => $v !== null);
    }
}
