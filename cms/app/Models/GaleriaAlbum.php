<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GaleriaAlbum extends Model
{
    protected $table = 'galeria_albuns';

    protected $fillable = [
        'titulo',
        'slug',
        'descricao',
        'capa_imagem',
        'categoria',
        'ordem',
        'publico',
    ];

    protected $casts = [
        'publico' => 'boolean',
        'ordem'   => 'integer',
    ];

    public function fotos(): HasMany
    {
        return $this->hasMany(GaleriaFoto::class, 'album_id')->orderBy('ordem');
    }

    public function toJsonExport(): array
    {
        return [
            'slug'        => $this->slug,
            'titulo'      => $this->titulo,
            'descricao'   => $this->descricao,
            'categoria'   => $this->categoria,
            'capa_imagem' => $this->capa_imagem,
            'fotos'       => $this->fotos->map(fn (GaleriaFoto $f) => [
                'arquivo' => $f->arquivo,
                'legenda' => $f->legenda,
                'alt'     => $f->alt ?: $f->legenda ?: $this->titulo,
            ])->values()->all(),
        ];
    }
}
