<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homilia extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'titulo', 'data', 'celebrante', 'ocasiao',
        'leitura_referencia', 'leitura_texto',
        'resumo', 'transcricao', 'audio_url', 'video_url',
        'imagem_capa_url', 'imagem_capa_alt',
        'publicado',
    ];

    protected $casts = [
        'data'      => 'date',
        'publicado' => 'boolean',
    ];

    public function toJsonExport(): array
    {
        $data = [
            'slug'       => $this->slug,
            'titulo'     => $this->titulo,
            'data'       => $this->data?->format('Y-m-d'),
            'celebrante' => $this->celebrante,
            'ocasiao'    => $this->ocasiao,
            'resumo'     => $this->resumo,
            'transcricao' => $this->transcricao,
            'audio_url'  => $this->audio_url,
            'video_url'  => $this->video_url,
            'publicado'  => $this->publicado,
        ];

        if ($this->leitura_referencia) {
            $data['leitura_evangelho'] = array_filter([
                'referencia' => $this->leitura_referencia,
                'texto'      => $this->leitura_texto,
            ]);
        }

        if ($this->imagem_capa_url) {
            $data['imagem_capa'] = array_filter([
                'url' => $this->imagem_capa_url,
                'alt' => $this->imagem_capa_alt,
            ]);
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
