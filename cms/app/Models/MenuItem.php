<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;
    protected $table = 'menu_items';

    protected $fillable = [
        'titulo', 'link', 'icone', 'page_key',
        'pai_id', 'ordem', 'visivel', 'externo',
    ];

    protected $casts = [
        'visivel' => 'boolean',
        'externo' => 'boolean',
        'ordem'   => 'integer',
    ];

    public function pai(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'pai_id');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'pai_id')->orderBy('ordem');
    }

    public function toJsonExport(): array
    {
        return array_filter([
            'id'       => $this->id,
            'titulo'   => $this->titulo,
            'link'     => $this->link,
            'icone'    => $this->icone,
            'page_key' => $this->page_key,
            'pai_id'   => $this->pai_id,
            'ordem'    => $this->ordem,
            'visivel'  => $this->visivel,
            'externo'  => $this->externo,
            'filhos'   => $this->relationLoaded('filhos')
                ? $this->filhos->map(fn ($f) => $f->toJsonExport())->values()->all()
                : [],
        ], static fn ($v) => $v !== null && $v !== '');
    }
}
