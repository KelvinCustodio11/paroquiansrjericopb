<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Igreja extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'nome', 'endereco', 'bairro', 'tipo', 'ativa'];

    protected $casts = ['ativa' => 'boolean'];

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioMissa::class);
    }
}
