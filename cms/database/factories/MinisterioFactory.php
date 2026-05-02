<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ministerio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MinisterioFactory extends Factory
{
    protected $model = Ministerio::class;

    public function definition(): array
    {
        $nome = $this->faker->words(3, true);
        return [
            'slug'      => Str::slug($nome).'-'.Str::random(4),
            'nome'      => ucwords($nome),
            'categoria' => $this->faker->randomElement(['ministerio', 'catequese', 'grupo-oracao', 'estudo-biblico', 'outro']),
            'descricao' => $this->faker->sentence(8),
            'icone'     => 'fa-church',
            'ativo'     => true,
        ];
    }
}
