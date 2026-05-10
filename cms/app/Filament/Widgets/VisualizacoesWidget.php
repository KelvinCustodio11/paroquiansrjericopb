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

    /** Alterna entre visitas únicas (por ip_hash) e totais */
    public bool $somentUnicos = false;

    public array $totais  = [];
    public array $paginas = [];

    public function mount(): void
    {
        $this->recarregar();
    }

    public function toggleUnicos(): void
    {
        $this->somentUnicos = ! $this->somentUnicos;
        $this->recarregar();
    }

    public function atualizar(): void
    {
        $this->recarregar();
    }

    private function recarregar(): void
    {
        $this->totais  = PageView::contagens(unicos: $this->somentUnicos);
        $this->paginas = PageView::porPagina(unicos: $this->somentUnicos)->toArray();
    }
}
