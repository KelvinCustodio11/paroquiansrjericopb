<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Ministerio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MinisterioSeeder extends Seeder
{
    public function run(): void
    {
        $path = realpath(base_path('../data/ministerios.json'));
        if (! $path || ! File::exists($path)) {
            $this->command->warn("data/ministerios.json não encontrado.");
            return;
        }

        $data = json_decode(File::get($path), true);
        $itens = $data['ministerios'] ?? [];
        $count = 0;

        foreach ($itens as $item) {
            $coord    = $item['coordenador'] ?? [];
            $encontros = $item['encontros'] ?? [];
            Ministerio::updateOrCreate(['slug' => $item['slug']], [
                'nome'                  => $item['nome'],
                'descricao'             => $item['descricao'],
                'coordenador_nome'      => $coord['nome'] ?? null,
                'coordenador_telefone'  => $coord['telefone'] ?? null,
                'coordenador_email'     => $coord['email'] ?? null,
                'encontros_dia_semana'  => $encontros['dia_semana'] ?? null,
                'encontros_horario'     => $encontros['horario'] ?? null,
                'encontros_local'       => $encontros['local'] ?? null,
                'imagem'                => $item['imagem'] ?? null,
                'icone'                 => $item['icone'] ?? null,
                'ativo'                 => $item['ativo'] ?? true,
            ]);
            $count++;
        }

        $this->command->info("MinisterioSeeder: {$count} ministério(s) importado(s).");
    }
}
