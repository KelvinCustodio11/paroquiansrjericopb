<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventoFactory extends Factory
{
    protected $model = Evento::class;

    public function definition(): array
    {
        $titulo = $this->faker->sentence(3);
        return [
            'slug'        => Str::slug($titulo).'-'.Str::random(4),
            'titulo'      => $titulo,
            'subtitulo'   => $this->faker->sentence(6),
            'data_inicio' => now()->addDays(rand(1, 30))->format('Y-m-d'),
            'data_fim'    => now()->addDays(rand(31, 60))->format('Y-m-d'),
            'local'       => 'Igreja Matriz — Jericó/PB',
            'categoria'   => $this->faker->randomElement(['liturgico', 'pastoral', 'social', 'formativo', 'festivo', 'outro']),
            'imagem_capa' => 'images/event-image.jpg',
            'status'      => 'agendado',
            'publicado'   => true,
        ];
    }

    public function publicado(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicado' => true,
            'status'    => 'agendado',
        ]);
    }

    public function rascunho(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicado' => false,
            'status'    => 'agendado',
        ]);
    }
}
