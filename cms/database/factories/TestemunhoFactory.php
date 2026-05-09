<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testemunho;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestemunhoFactory extends Factory
{
    protected $model = Testemunho::class;

    public function definition(): array
    {
        return [
            'nome'               => $this->faker->name(),
            'email'              => $this->faker->safeEmail(),
            'cidade'             => $this->faker->city(),
            'texto'              => $this->faker->paragraph(3),
            'status'             => 'pendente',
            'consentimento_lgpd' => true,
            'aprovado_em'        => null,
            'motivo_rejeicao'    => null,
        ];
    }

    public function aprovado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'aprovado',
            'aprovado_em' => now(),
        ]);
    }

    public function pendente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'pendente',
            'aprovado_em' => null,
        ]);
    }

    public function rejeitado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'           => 'rejeitado',
            'aprovado_em'      => null,
            'motivo_rejeicao'  => 'Conteúdo inadequado.',
        ]);
    }
}
