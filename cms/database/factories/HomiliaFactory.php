<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Homilia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HomiliaFactory extends Factory
{
    protected $model = Homilia::class;

    public function definition(): array
    {
        $titulo = $this->faker->sentence(4);
        return [
            'slug'               => Str::slug($titulo).'-'.Str::random(4),
            'titulo'             => $titulo,
            'data'               => now()->subDays(rand(1, 30))->format('Y-m-d'),
            'celebrante'         => $this->faker->name('male'),
            'ocasiao'            => 'Domingo comum',
            'leitura_referencia' => 'Jo 3,16',
            'leitura_texto'      => $this->faker->paragraph(),
            'resumo'             => $this->faker->sentence(10),
            'transcricao'        => '<p>'.$this->faker->paragraph().'</p>',
            'publicado'          => true,
        ];
    }

    public function publicado(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicado' => true,
        ]);
    }

    public function rascunho(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicado' => false,
        ]);
    }
}
