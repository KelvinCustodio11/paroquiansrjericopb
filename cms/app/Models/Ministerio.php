<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ministerio extends Model
{
    use HasFactory;

    protected $table = 'ministerios';

    protected $fillable = [
        'slug', 'nome', 'categoria', 'descricao',
        'coordenador_nome', 'coordenador_telefone', 'coordenador_email',
        'encontros_dia_semana', 'encontros_horario', 'encontros_local',
        'imagem', 'icone', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function toJsonExport(): array
    {
        $data = [
            'slug'      => $this->slug,
            'nome'      => $this->nome,
            'categoria' => $this->categoria ?? 'ministerio',
            'descricao' => $this->descricao,
            'imagem'    => $this->imagem,
            'icone'     => $this->icone,
            'ativo'     => $this->ativo,
        ];

        if ($this->coordenador_nome) {
            $data['coordenador'] = array_filter([
                'nome'     => $this->coordenador_nome,
                'telefone' => $this->coordenador_telefone,
                'email'    => $this->coordenador_email,
            ]);
        }

        if ($this->encontros_dia_semana) {
            $data['encontros'] = array_filter([
                'dia_semana' => $this->encontros_dia_semana,
                'horario'    => $this->encontros_horario,
                'local'      => $this->encontros_local,
            ]);
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
