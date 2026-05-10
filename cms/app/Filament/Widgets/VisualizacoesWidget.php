<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\PageView;
use Filament\Widgets\Widget;

class VisualizacoesWidget extends Widget
{
    protected static string $view = 'filament.widgets.visualizacoes-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    // ── Filtros / estado ─────────────────────────────────────────────────────

    /** Alterna entre visitas únicas (por ip_hash) e totais */
    public bool $somentUnicos = false;

    /** Coluna ativa de ordenação */
    public string $ordenarPor = 'geral';

    /** Direção de ordenação: 'asc' | 'desc' */
    public string $ordenarDirecao = 'desc';

    /** Limite de linhas na tabela; 0 = todas */
    public int $topN = 0;

    /** Período personalizado — início */
    public string $periodoInicio = '';

    /** Período personalizado — fim */
    public string $periodoFim = '';

    // ── Dados calculados ─────────────────────────────────────────────────────

    public array $totais      = [];
    public array $paginas     = [];  // lista final (ordenada + limitada)
    public array $paginasFull = [];  // lista completa (cache para re-ordenar sem re-query)
    public array $chartData   = [];
    public int   $totalPeriodo = 0;

    // ── Ciclo de vida ────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->periodoInicio = now()->subDays(29)->format('Y-m-d');
        $this->periodoFim    = now()->format('Y-m-d');
        $this->recarregar();
    }

    // ── Ações ────────────────────────────────────────────────────────────────

    public function toggleUnicos(): void
    {
        $this->somentUnicos = ! $this->somentUnicos;
        $this->recarregar();
    }

    public function ordenar(string $coluna): void
    {
        $validas = ['titulo', 'hoje', 'semana', 'mes', 'ano', 'geral'];
        if (! in_array($coluna, $validas, true)) {
            return;
        }

        if ($this->ordenarPor === $coluna) {
            $this->ordenarDirecao = $this->ordenarDirecao === 'desc' ? 'asc' : 'desc';
        } else {
            $this->ordenarPor     = $coluna;
            $this->ordenarDirecao = 'desc';
        }

        $this->aplicarFiltros();
    }

    public function atualizar(): void
    {
        $this->recarregar();
    }

    // ── Hooks de propriedade ─────────────────────────────────────────────────

    public function updatedTopN(): void
    {
        if (! in_array($this->topN, [0, 5, 10, 20], true)) {
            $this->topN = 0;
        }
        $this->aplicarFiltros();
    }

    public function updatedPeriodoInicio(): void
    {
        $this->recalcularPeriodo();
    }

    public function updatedPeriodoFim(): void
    {
        $this->recalcularPeriodo();
    }

    // ── Internos ─────────────────────────────────────────────────────────────

    private function recarregar(): void
    {
        $this->totais      = PageView::contagens(unicos: $this->somentUnicos);
        $this->paginasFull = PageView::porPagina(unicos: $this->somentUnicos)->toArray();
        $this->chartData   = PageView::ultimos30Dias(unicos: $this->somentUnicos);

        $this->recalcularPeriodo();
        $this->aplicarFiltros();

        $this->dispatch('chart-updated', data: $this->chartData);
    }

    private function recalcularPeriodo(): void
    {
        if (
            $this->periodoInicio && $this->periodoFim
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->periodoInicio)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->periodoFim)
            && $this->periodoInicio <= $this->periodoFim
        ) {
            $this->totalPeriodo = PageView::contagensPersonalizado(
                $this->periodoInicio,
                $this->periodoFim,
                $this->somentUnicos,
            );
        }
    }

    private function aplicarFiltros(): void
    {
        $dados = $this->paginasFull;
        $col   = $this->ordenarPor;
        $dir   = $this->ordenarDirecao;

        usort($dados, static function (array $a, array $b) use ($col, $dir): int {
            $va = $a[$col] ?? 0;
            $vb = $b[$col] ?? 0;

            if (is_string($va)) {
                return $dir === 'asc' ? strcmp($va, $vb) : strcmp($vb, $va);
            }

            return $dir === 'asc' ? $va <=> $vb : $vb <=> $va;
        });

        $this->paginas = $this->topN > 0 ? array_slice($dados, 0, $this->topN) : $dados;
    }
}
