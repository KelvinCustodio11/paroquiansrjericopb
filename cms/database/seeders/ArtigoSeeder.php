<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Artigo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ArtigoSeeder extends Seeder
{
    public function run(): void
    {
        $path = realpath(base_path('../data/artigos.json'));
        if (! $path || ! File::exists($path)) {
            $this->command->warn("data/artigos.json não encontrado.");
            return;
        }

        $data = json_decode(File::get($path), true);
        $itens = $data['artigos'] ?? [];
        $count = 0;

        foreach ($itens as $item) {
            $autor = $item['autor'] ?? [];
            $img   = $item['imagem_capa'] ?? [];
            Artigo::updateOrCreate(['slug' => $item['slug']], [
                'titulo'             => $item['titulo'],
                'data_publicacao'    => $item['data_publicacao'],
                'data_atualizacao'   => $item['data_atualizacao'] ?? null,
                'autor_nome'         => $autor['nome'] ?? 'PASCOM',
                'autor_papel'        => $autor['papel'] ?? null,
                'autor_foto'         => $autor['foto'] ?? null,
                'categoria'          => $item['categoria'] ?? 'outro',
                'tags'               => $item['tags'] ?? [],
                'resumo'             => $item['resumo'],
                'imagem_capa_url'    => is_array($img) ? ($img['url'] ?? null) : $img,
                'imagem_capa_alt'    => is_array($img) ? ($img['alt'] ?? null) : null,
                'imagem_capa_largura' => is_array($img) ? ($img['largura'] ?? null) : null,
                'imagem_capa_altura'  => is_array($img) ? ($img['altura'] ?? null) : null,
                'conteudo'           => $item['conteudo'],
                'destaque'           => $item['destaque'] ?? false,
                'publicado'          => $item['publicado'] ?? false,
            ]);
            $count++;
        }

        $this->command->info("ArtigoSeeder: {$count} artigo(s) importado(s).");
    }
}
