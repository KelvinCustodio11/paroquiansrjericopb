<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Configuracao;
use Illuminate\Database\Seeder;

class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        Configuracao::updateOrCreate(['id' => 1], Configuracao::defaults());
    }
}
