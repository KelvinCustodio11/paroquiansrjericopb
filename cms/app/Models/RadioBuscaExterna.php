<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadioBuscaExterna extends Model
{
    protected $table = 'radio_buscas_externas';

    protected $fillable = [
        'label',
        'tag',
        'pais',
        'estado',
        'regiao',
        'limite',
        'ativo',
        'ordem',
    ];

    protected $casts = [
        'ativo'  => 'boolean',
        'limite' => 'integer',
        'ordem'  => 'integer',
    ];
}
