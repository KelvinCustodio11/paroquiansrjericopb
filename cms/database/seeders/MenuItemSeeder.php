<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        // Limpar tabela antes de semear para evitar duplicatas
        MenuItem::truncate();

        $items = [
            ['titulo' => 'Início',            'link' => 'index.html',            'page_key' => 'home',      'ordem' => 1],
            ['titulo' => 'Nossa História',     'link' => 'historia.html',         'page_key' => 'historia',  'ordem' => 2],
            ['titulo' => 'Pastoral',           'link' => 'ministerios.html',      'page_key' => 'pastoral',  'ordem' => 3],
            ['titulo' => 'Eventos',            'link' => 'eventos.html',          'page_key' => 'eventos',   'ordem' => 4],
            ['titulo' => 'Agenda Litúrgica',   'link' => 'agenda-liturgica.html', 'page_key' => 'agenda',    'ordem' => 5],
            ['titulo' => 'Homilias',           'link' => 'homilias.html',         'page_key' => 'liturgia',  'ordem' => 6],
            ['titulo' => 'Contato',            'link' => 'contato.html',          'page_key' => 'contato',   'ordem' => 7],
        ];

        foreach ($items as $item) {
            MenuItem::create(array_merge($item, [
                'visivel'  => true,
                'externo'  => false,
                'icone'    => null,
                'pai_id'   => null,
            ]));
        }
    }
}
