<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pagina',
        'titulo',
        'ip_hash',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    // ── Scopes de período ────────────────────────────────────────────────────

    public function scopeHoje(Builder $q): Builder
    {
        return $q->whereDate('viewed_at', today());
    }

    public function scopeEstaSemana(Builder $q): Builder
    {
        return $q->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeEsteMes(Builder $q): Builder
    {
        return $q->whereBetween('viewed_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeEsteAno(Builder $q): Builder
    {
        return $q->whereBetween('viewed_at', [now()->startOfYear(), now()->endOfYear()]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Retorna contagens agrupadas por período para uma ou todas as páginas.
     *
     * @return array{hoje: int, semana: int, mes: int, ano: int, geral: int}
     */
    public static function contagens(?string $pagina = null): array
    {
        $base = $pagina
            ? static::where('pagina', $pagina)
            : static::query();

        return [
            'hoje'   => (clone $base)->hoje()->count(),
            'semana' => (clone $base)->estaSemana()->count(),
            'mes'    => (clone $base)->esteMes()->count(),
            'ano'    => (clone $base)->esteAno()->count(),
            'geral'  => (clone $base)->count(),
        ];
    }

    /**
     * Lista todas as páginas com suas contagens.
     *
     * @return \Illuminate\Support\Collection<int, array{pagina: string, titulo: string, ...}>
     */
    public static function porPagina(): \Illuminate\Support\Collection
    {
        $paginas = static::selectRaw('pagina, MAX(titulo) as titulo')
            ->groupBy('pagina')
            ->orderBy('pagina')
            ->get()
            ->pluck('titulo', 'pagina');

        return $paginas->map(function (string $titulo, string $pagina) {
            return array_merge(['pagina' => $pagina, 'titulo' => $titulo ?: $pagina], static::contagens($pagina));
        })->values();
    }
}
