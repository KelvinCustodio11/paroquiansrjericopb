<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paroco extends Model
{
    use HasFactory;

    protected $table = 'parocos';

    protected $fillable = [
        'nome', 'saudacao', 'data_ordenacao', 'data_inicio_paroquia',
        'biografia', 'foto',
        'contato_email', 'contato_telefone',
        'redes_facebook', 'redes_instagram',
        'ativo',
    ];

    protected $casts = [
        'data_ordenacao'        => 'date',
        'data_inicio_paroquia'  => 'date',
        'ativo'                 => 'boolean',
    ];

    public function toJsonExport(): array
    {
        $data = [
            'nome'                   => $this->nome,
            'saudacao'               => $this->saudacao,
            'data_ordenacao'         => $this->data_ordenacao?->format('Y-m-d'),
            'data_inicio_paroquia'   => $this->data_inicio_paroquia?->format('Y-m-d'),
            'biografia'              => $this->biografia,
            'foto'                   => $this->foto,
        ];

        if ($this->contato_email || $this->contato_telefone) {
            $data['contato'] = array_filter([
                'email'    => $this->contato_email,
                'telefone' => $this->contato_telefone,
            ]);
        }

        if ($this->redes_facebook || $this->redes_instagram) {
            $data['redes_sociais'] = array_filter([
                'facebook'  => $this->redes_facebook,
                'instagram' => $this->redes_instagram,
            ]);
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
