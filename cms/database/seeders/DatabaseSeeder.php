<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EventoSeeder::class,
            ArtigoSeeder::class,
            HomiliaSeeder::class,
            HorarioMissaSeeder::class,
            CompromissoSeeder::class,
            MinisterioSeeder::class,
            ParocoSeeder::class,
            ConfiguracaoSeeder::class,
        ]);
    }
}
