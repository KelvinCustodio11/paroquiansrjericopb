<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Compromisso;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CompromissoSeeder extends Seeder
{
    public function run(): void
    {
        $path = realpath(base_path('../data/agenda-pastoral.json'));
        if (! $path || ! File::exists($path)) {
            $this->command->warn("data/agenda-pastoral.json não encontrado.");
            return;
        }

        $data = json_decode(File::get($path), true);
        $itens = $data['compromissos'] ?? [];
        $count = 0;

        foreach ($itens as $item) {
            Compromisso::create([
                'titulo'      => $item['titulo'],
                'data'        => $item['data'],
                'hora'        => $item['hora'] ?? null,
                'tipo'        => $item['tipo'] ?? 'outro',
                'local'       => $item['local'] ?? null,
                'responsavel' => $item['responsavel'] ?? null,
                'publico'     => $item['publico'] ?? true,
                'observacao'  => $item['observacao'] ?? null,
            ]);
            $count++;
        }

        $this->command->info("CompromissoSeeder: {$count} compromisso(s) importado(s).");
    }
}
