<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Radio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RadioFactory extends Factory
{
    protected $model = Radio::class;

    public function definition(): array
    {
        return [
            'nome'      => $this->faker->company().' Rádio',
            'url'       => 'https://stream.example.com/'.Str::random(8),
            'categoria' => $this->faker->randomElement(['catolica', 'gospel', 'religiosa']),
            'estado'    => $this->faker->stateAbbr(),
            'cidade'    => $this->faker->city(),
            'destaque'  => false,
            'ordem'     => rand(1, 99),
            'ativa'     => true,
        ];
    }
}
