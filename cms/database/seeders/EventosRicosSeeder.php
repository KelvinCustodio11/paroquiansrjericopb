<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventosRicosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('eventos')->truncate();

        $jsonPath = base_path('../data/eventos.json');
        if (!file_exists($jsonPath)) {
            $jsonPath = '/root/dev/paroquiansrjericopb/data/eventos.json';
        }

        $json = json_decode(file_get_contents($jsonPath), true);

        foreach ($json['eventos'] as $e) {
            $galeria = $e['galeria'] ?? [];

            DB::table('eventos')->insert([
                'slug'                        => $e['slug'],
                'titulo'                      => $e['titulo'],
                'titulo_destaque'             => $e['titulo_destaque'] ?? null,
                'subtitulo'                   => $e['subtitulo'] ?? null,
                'data_inicio'                 => $e['data_inicio'],
                'data_fim'                    => $e['data_fim'] ?? $e['data_inicio'],
                'hora_inicio'                 => $e['hora_inicio'] ?? null,
                'local'                       => $e['local'] ?? null,
                'local_maps'                  => $e['local_maps'] ?? null,
                'categoria'                   => $e['categoria'],
                'status'                      => $e['status'],
                'imagem_capa'                 => $e['imagem_capa'] ?? null,
                'descricao_curta'             => $e['descricaoCurta'] ?? null,
                'descricao_completa'          => $e['descricao_completa'] ?? null,
                'stats_bar'                   => json_encode($e['stats_bar'] ?? []),
                'topicos_destaque'            => json_encode($e['topicos_destaque'] ?? []),
                'texto_pos_topicos'           => $e['texto_pos_topicos'] ?? null,
                'galeria_titulo'              => $galeria['titulo'] ?? null,
                'galeria_titulo_destaque'     => $galeria['titulo_destaque'] ?? null,
                'galeria_subtitulo'           => $galeria['subtitulo'] ?? null,
                'galeria_imagens'             => json_encode($galeria['imagens'] ?? []),
                'programacao_titulo'          => $e['programacao_titulo'] ?? null,
                'programacao_titulo_destaque' => $e['programacao_titulo_destaque'] ?? null,
                'programacao_subtitulo'       => $e['programacao_subtitulo'] ?? null,
                'programacao'                 => json_encode($e['programacao'] ?? []),
                'sidebar_descricao'           => $e['sidebar_descricao'] ?? null,
                'sidebar_items'               => json_encode($e['sidebar_items'] ?? []),
                'sidebar_milestones'          => json_encode($e['sidebar_milestones'] ?? []),
                'publicado'                   => $e['publicado'] ? 1 : 0,
                'destaque'                    => $e['destaque'] ? 1 : 0,
                'created_at'                  => now(),
                'updated_at'                  => now(),
            ]);
        }
    }
}
