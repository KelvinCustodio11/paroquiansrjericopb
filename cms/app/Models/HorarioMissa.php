<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioMissa extends Model
{
    use HasFactory;

    protected $table = 'horarios_missa';

    protected $fillable = [
        'igreja_id', 'dia_semana', 'hora', 'tipo_celebracao', 'observacao',
    ];

    public function igreja(): BelongsTo
    {
        return $this->belongsTo(Igreja::class);
    }
}
