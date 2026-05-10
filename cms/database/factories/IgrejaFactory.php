<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Igreja;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IgrejaFactory extends Factory
{
    protected $model = Igreja::class;

    public function definition(): array
    {
        return [
            'slug'     => 'paroquia-nsr-jerico-'.Str::random(4),
            'nome'     => 'Paróquia Nossa Senhora dos Remédios',
            'endereco' => 'Praça Matriz, s/n',
            'bairro'   => 'Centro',
            'tipo'     => 'matriz',
            'ativa'    => true,
        ];
    }
}
