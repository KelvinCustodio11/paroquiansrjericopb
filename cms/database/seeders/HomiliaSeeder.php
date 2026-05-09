<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Homilia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class HomiliaSeeder extends Seeder
{
    public function run(): void
    {
        $path = realpath(base_path('../data/homilias.json'));
        if (! $path || ! File::exists($path)) {
            $this->command->warn("data/homilias.json não encontrado.");
            return;
        }

        $data = json_decode(File::get($path), true);
        $itens = $data['homilias'] ?? [];
        $count = 0;

        foreach ($itens as $item) {
            $leitura = $item['leitura_evangelho'] ?? [];
            $img     = $item['imagem_capa'] ?? [];
            Homilia::updateOrCreate(['slug' => $item['slug']], [
                'titulo'             => $item['titulo'],
                'data'               => $item['data'],
                'celebrante'         => $item['celebrante'],
                'ocasiao'            => $item['ocasiao'] ?? null,
                'leitura_referencia' => $leitura['referencia'] ?? null,
                'leitura_texto'      => $leitura['texto'] ?? null,
                'resumo'             => $item['resumo'],
                'transcricao'        => $item['transcricao'] ?? null,
                'audio_url'          => $item['audio_url'] ?? null,
                'video_url'          => $item['video_url'] ?? null,
                'imagem_capa_url'    => is_array($img) ? ($img['url'] ?? null) : $img,
                'imagem_capa_alt'    => is_array($img) ? ($img['alt'] ?? null) : null,
                'publicado'          => $item['publicado'] ?? false,
            ]);
            $count++;
        }

        $this->command->info("HomiliaSeeder: {$count} homilia(s) importada(s).");
    }
}
