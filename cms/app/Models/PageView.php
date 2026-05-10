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
     * @param  bool $unicos  Se true, conta apenas ip_hash distintos (visitas únicas).
     * @return array{hoje: int, semana: int, mes: int, ano: int, geral: int}
     */
    public static function contagens(?string $pagina = null, bool $unicos = false): array
    {
        $base = $pagina
            ? static::where('pagina', $pagina)
            : static::query();

        $contar = $unicos
            ? fn (Builder $q) => $q->distinct('ip_hash')->count('ip_hash')
            : fn (Builder $q) => $q->count();

        return [
            'hoje'   => $contar((clone $base)->hoje()),
            'semana' => $contar((clone $base)->estaSemana()),
            'mes'    => $contar((clone $base)->esteMes()),
            'ano'    => $contar((clone $base)->esteAno()),
            'geral'  => $contar(clone $base),
        ];
    }

    /**
     * Lista todas as páginas com suas contagens.
     *
     * @param  bool $unicos  Se true, conta apenas ip_hash distintos (visitas únicas).
     * @return \Illuminate\Support\Collection<int, array{pagina: string, titulo: string, ...}>
     */
    public static function porPagina(bool $unicos = false): \Illuminate\Support\Collection
    {
        $paginas = static::selectRaw('pagina, MAX(titulo) as titulo')
            ->groupBy('pagina')
            ->orderBy('pagina')
            ->get()
            ->pluck('titulo', 'pagina');

        return $paginas->map(function (?string $titulo, string $pagina) use ($unicos) {
            return array_merge(['pagina' => $pagina, 'titulo' => $titulo ?: $pagina], static::contagens($pagina, $unicos));
        })->values();
    }

    /**
     * Contagens diárias dos últimos 30 dias para o gráfico de tendência.
     *
     * @return array<string, int>  chave: 'YYYY-MM-DD', valor: contagem
     */
    public static function ultimos30Dias(bool $unicos = false): array
    {
        $inicio      = now()->subDays(29)->startOfDay();
        $colContagem = $unicos ? 'COUNT(DISTINCT ip_hash)' : 'COUNT(*)';

        $dados = static::query()
            ->where('viewed_at', '>=', $inicio)
            ->selectRaw("DATE(viewed_at) as dia, {$colContagem} as total")
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->pluck('total', 'dia')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $resultado = [];
        for ($i = 29; $i >= 0; $i--) {
            $dia             = now()->subDays($i)->format('Y-m-d');
            $resultado[$dia] = $dados[$dia] ?? 0;
        }

        return $resultado;
    }

    /**
     * Total de visitas/visitantes em um intervalo de datas personalizado.
     */
    public static function contagensPersonalizado(string $inicio, string $fim, bool $unicos = false): int
    {
        $q = static::query()->whereBetween('viewed_at', [
            $inicio . ' 00:00:00',
            $fim . ' 23:59:59',
        ]);

        return $unicos
            ? $q->distinct('ip_hash')->count('ip_hash')
            : $q->count();
    }
}
