<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Radio extends Model
{
    use HasFactory;
    protected $table = 'radios';

    protected $fillable = [
        'nome',
        'url',
        'descricao',
        'programacao',
        'programacao_url',
        'favicon',
        'destaque',
        'ativa',
        'ordem',
        'categoria',
        'estado',
        'cidade',
    ];

    protected $casts = [
        'destaque' => 'boolean',
        'ativa'    => 'boolean',
        'ordem'    => 'integer',
    ];
}
