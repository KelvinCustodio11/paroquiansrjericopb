<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Paroco;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ParocoSeeder extends Seeder
{
    public function run(): void
    {
        $path = realpath(base_path('../data/paroco.json'));
        if (! $path || ! File::exists($path)) {
            $this->command->warn("data/paroco.json não encontrado.");
            return;
        }

        $data    = json_decode(File::get($path), true);
        $contato = $data['contato'] ?? [];
        $redes   = $data['redes_sociais'] ?? [];

        Paroco::updateOrCreate(['nome' => $data['nome']], [
            'saudacao'              => $data['saudacao'] ?? 'Padre',
            'data_ordenacao'        => $data['data_ordenacao'] ?? null,
            'data_inicio_paroquia'  => $data['data_inicio_paroquia'] ?? null,
            'biografia'             => $data['biografia'],
            'foto'                  => $data['foto'] ?? null,
            'contato_email'         => $contato['email'] ?? null,
            'contato_telefone'      => $contato['telefone'] ?? null,
            'redes_facebook'        => $redes['facebook'] ?? null,
            'redes_instagram'       => $redes['instagram'] ?? null,
            'ativo'                 => true,
        ]);

        $this->command->info("ParocoSeeder: pároco importado.");
    }
}
