<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Igreja;
use App\Models\HorarioMissa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class HorarioMissaSeeder extends Seeder
{
    public function run(): void
    {
        $path = realpath(base_path('../data/horarios-missa.json'));
        if (! $path || ! File::exists($path)) {
            $this->command->warn("data/horarios-missa.json não encontrado.");
            return;
        }

        $data = json_decode(File::get($path), true);
        $igrejas = $data['igrejas'] ?? [];
        $count = 0;

        foreach ($igrejas as $igData) {
            $igreja = Igreja::updateOrCreate(
                ['slug' => $igData['slug'] ?? \Illuminate\Support\Str::slug($igData['nome'])],
                [
                    'nome'     => $igData['nome'],
                    'endereco' => $igData['endereco'] ?? null,
                    'bairro'   => $igData['bairro'] ?? null,
                    'tipo'     => $igData['tipo'] ?? 'capela',
                    'ativa'    => 1,
                ]
            );

            // Recria horários para esta igreja
            $igreja->horarios()->delete();
            foreach ($igData['horarios'] ?? [] as $h) {
                HorarioMissa::create([
                    'igreja_id'        => $igreja->id,
                    'dia_semana'       => $h['dia_semana'],
                    'hora'             => $h['hora'],
                    'tipo_celebracao'  => $h['tipo_celebracao'] ?? 'missa',
                    'observacao'       => $h['observacao'] ?? null,
                ]);
                $count++;
            }
        }

        $this->command->info("HorarioMissaSeeder: {$count} horário(s) importado(s) em ".count($igrejas)." igreja(s).");
    }
}
