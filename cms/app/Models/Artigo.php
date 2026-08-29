<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Artigo extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'titulo', 'data_publicacao', 'data_atualizacao',
        'autor_nome', 'autor_papel', 'autor_foto',
        'categoria', 'tags', 'resumo',
        'imagem_capa_url', 'imagem_capa_alt', 'imagem_capa_largura', 'imagem_capa_altura',
        'conteudo', 'destaque', 'publicado',
    ];

    protected $casts = [
        'data_publicacao'  => 'date',
        'data_atualizacao' => 'date',
        'tags'             => 'array',
        'destaque'         => 'boolean',
        'publicado'        => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Artigo $artigo): void {
            $artigo->slug = str($artigo->slug)->lower()->toString();

            if ($artigo->conteudo && mb_strlen($artigo->conteudo) < 100) {
                throw ValidationException::withMessages([
                    'conteudo' => 'O conteudo deve ter no minimo 100 caracteres.',
                ]);
            }
        });
    }

    public function toJsonExport(): array
    {
        $data = [
            'slug'             => str($this->slug)->lower()->toString(),
            'titulo'           => $this->titulo,
            'data_publicacao'  => $this->data_publicacao?->format('Y-m-d'),
            'data_atualizacao' => $this->data_atualizacao?->format('Y-m-d'),
            'autor'            => array_filter([
                'nome'  => $this->autor_nome,
                'papel' => $this->autor_papel,
                'foto'  => $this->autor_foto,
            ]),
            'categoria'        => $this->categoria,
            'tags'             => $this->tags ?? [],
            'resumo'           => $this->resumo,
            'conteudo'         => $this->conteudo,
            'destaque'         => $this->destaque,
            'publicado'        => $this->publicado,
        ];

        if ($this->imagem_capa_url) {
            $data['imagem_capa'] = array_filter([
                'url'     => $this->imagem_capa_url,
                'alt'     => $this->imagem_capa_alt,
                'largura' => $this->imagem_capa_largura,
                'altura'  => $this->imagem_capa_altura,
            ]);
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
