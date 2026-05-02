<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Testemunho;
use Illuminate\Database\Seeder;

class TestemunhoSeeder extends Seeder
{
    public function run(): void
    {
        $testemunhos = [
            [
                'nome'             => 'Maria das Graças',
                'email'            => null,
                'cidade'           => 'Jericó/PB',
                'texto'            => 'A paróquia é uma família para mim. Encontrei fé e força nos momentos mais difíceis através da comunidade e das celebrações.',
                'status'           => 'aprovado',
                'consentimento_lgpd' => true,
                'aprovado_em'      => now()->subDays(10),
            ],
            [
                'nome'             => 'José Augusto',
                'email'            => null,
                'cidade'           => 'Brejo dos Santos/PB',
                'texto'            => 'As celebrações da Semana Santa me tocaram profundamente. A oração comunitária transformou minha vida.',
                'status'           => 'aprovado',
                'consentimento_lgpd' => true,
                'aprovado_em'      => now()->subDays(5),
            ],
            [
                'nome'             => 'Ana Beatriz',
                'email'            => null,
                'cidade'           => 'Jericó/PB',
                'texto'            => 'O grupo de jovens me ajudou a encontrar meu caminho. Gratidão a todos da pastoral.',
                'status'           => 'aprovado',
                'consentimento_lgpd' => true,
                'aprovado_em'      => now()->subDays(2),
            ],
        ];

        foreach ($testemunhos as $dados) {
            Testemunho::create($dados);
        }
    }
}
