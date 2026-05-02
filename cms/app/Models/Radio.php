<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Radio extends Model
{
    protected $table = 'radios';

    protected $fillable = [
        'nome',
        'url',
        'descricao',
        'favicon',
        'destaque',
        'ativa',
        'ordem',
    ];

    protected $casts = [
        'destaque' => 'boolean',
        'ativa'    => 'boolean',
        'ordem'    => 'integer',
    ];
}
