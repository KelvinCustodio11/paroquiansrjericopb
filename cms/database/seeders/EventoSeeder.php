<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Evento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class EventoSeeder extends Seeder
{
    public function run(): void
    {
        $path = realpath(base_path('../data/eventos.json'));
        if (! $path || ! File::exists($path)) {
            $this->command->warn("data/eventos.json não encontrado — pulando EventoSeeder.");
            return;
        }

        $data = json_decode(File::get($path), true);
        $eventos = $data['eventos'] ?? [];

        $count = 0;
        foreach ($eventos as $item) {
            Evento::updateOrCreate(['slug' => $item['slug']], [
                'titulo'       => $item['titulo'] ?? '',
                'subtitulo'    => $item['subtitulo'] ?? null,
                'data_inicio'  => $item['data_inicio'] ?? null,
                'data_fim'     => $item['data_fim'] ?? null,
                'hora_inicio'  => $item['horario_inicio'] ?? $item['hora_inicio'] ?? null,
                'local'        => is_array($item['local'] ?? null)
                    ? ($item['local']['nome'] ?? null)
                    : ($item['local'] ?? null),
                'categoria'    => $item['categoria'] ?? 'outro',
                'status'       => $item['status'] ?? 'agendado',
                'resumo'       => $item['resumo'] ?? null,
                'conteudo'     => $item['conteudo'] ?? null,
                'imagem_capa'  => is_array($item['imagem_capa'] ?? null)
                    ? ($item['imagem_capa']['url'] ?? null)
                    : ($item['imagem_capa'] ?? null),
                'publicado'    => $item['publicado'] ?? false,
                'destaque'     => $item['destaque'] ?? false,
            ]);
            $count++;
        }

        $this->command->info("EventoSeeder: {$count} evento(s) importado(s).");
    }
}
