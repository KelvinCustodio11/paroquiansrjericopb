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
        // Campos de layout rico
        'stats_bar',
        'topicos_destaque',
        'texto_pos_topicos',
        'galeria_titulo',
        'galeria_titulo_destaque',
        'galeria_subtitulo',
        'galeria_imagens',
        'programacao_titulo',
        'programacao_titulo_destaque',
        'programacao_subtitulo',
        'programacao',
        'sidebar_descricao',
        'sidebar_items',
        'sidebar_milestones',
        'inscricao',
        'publicado',
        'destaque',
    ];

    protected $casts = [
        'data_inicio'             => 'date',
        'data_fim'                => 'date',
        'stats_bar'               => 'array',
        'topicos_destaque'        => 'array',
        'galeria_imagens'         => 'array',
        'programacao'             => 'array',
        'sidebar_items'           => 'array',
        'sidebar_milestones'      => 'array',
        'inscricao'               => 'array',
        'publicado'               => 'boolean',
        'destaque'                => 'boolean',
    ];

    /**
     * Serializa o model para o formato exato esperado em data/eventos.json,
     * compativel com schemas/evento.schema.json e build-content.js.
     */
    public function toJsonExport(): array
    {
        // Monta galeria como objeto (compatível com data/eventos.json) quando os campos existem
        $galeria = null;
        if ($this->galeria_imagens && count($this->galeria_imagens) > 0) {
            $galeria = array_filter([
                'titulo'           => $this->galeria_titulo,
                'titulo_destaque'  => $this->galeria_titulo_destaque,
                'subtitulo'        => $this->galeria_subtitulo,
                'imagens'          => $this->galeria_imagens,
            ], static fn ($v) => $v !== null && $v !== '');
        }

        return array_filter([
            'slug'                       => $this->slug,
            'titulo'                     => $this->titulo,
            'subtitulo'                  => $this->subtitulo,
            'data_inicio'                => $this->data_inicio?->format('Y-m-d'),
            'data_fim'                   => $this->data_fim?->format('Y-m-d'),
            'hora_inicio'                => $this->hora_inicio,
            'local'                      => $this->local,
            'categoria'                  => $this->categoria,
            'status'                     => $this->status,
            'resumo'                     => $this->resumo,
            'conteudo'                   => $this->conteudo,
            'imagem_capa'                => $this->imagem_capa,
            'stats_bar'                  => $this->stats_bar,
            'topicos_destaque'           => $this->topicos_destaque,
            'texto_pos_topicos'          => $this->texto_pos_topicos,
            'galeria'                    => $galeria,
            'programacao_titulo'         => $this->programacao_titulo,
            'programacao_titulo_destaque'=> $this->programacao_titulo_destaque,
            'programacao_subtitulo'      => $this->programacao_subtitulo,
            'programacao'                => $this->programacao,
            'sidebar_descricao'          => $this->sidebar_descricao,
            'sidebar_items'              => $this->sidebar_items,
            'sidebar_milestones'         => $this->sidebar_milestones,
            'inscricao'                  => $this->inscricao,
            'publicado'                  => $this->publicado,
            'destaque'                   => $this->destaque,
        ], static fn ($v) => $v !== null);
    }
}
