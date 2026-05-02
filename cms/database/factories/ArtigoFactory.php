<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Artigo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArtigoFactory extends Factory
{
    protected $model = Artigo::class;

    public function definition(): array
    {
        $titulo = $this->faker->sentence(4);
        return [
            'slug'             => Str::slug($titulo).'-'.Str::random(4),
            'titulo'           => $titulo,
            'resumo'           => $this->faker->sentence(12),
            'conteudo'         => '<p>'.$this->faker->paragraph().'</p>',
            'categoria'        => $this->faker->randomElement(['noticias', 'espiritualidade', 'pastoral', 'comunidade', 'formacao', 'evangelho', 'outro']),
            'autor_nome'       => $this->faker->name(),
            'data_publicacao'  => now()->subDays(rand(1, 30)),
            'imagem_capa_url'  => 'images/blog/artigo-default.jpg',
            'imagem_capa_alt'  => $titulo,
            'publicado'        => true,
        ];
    }

    public function publicado(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicado'       => true,
            'data_publicacao' => $attributes['data_publicacao'] ?? now(),
        ]);
    }

    public function rascunho(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicado'       => false,
            'data_publicacao' => $attributes['data_publicacao'] ?? now()->subDays(1),
        ]);
    }
}
