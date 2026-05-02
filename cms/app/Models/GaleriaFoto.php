<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaleriaFoto extends Model
{
    protected $table = 'galeria_fotos';

    protected $fillable = [
        'album_id',
        'arquivo',
        'legenda',
        'alt',
        'ordem',
        'ativa',
    ];

    protected $casts = [
        'ativa' => 'boolean',
        'ordem' => 'integer',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(GaleriaAlbum::class, 'album_id');
    }
}
