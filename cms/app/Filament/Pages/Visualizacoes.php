<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\PageView;
use Filament\Pages\Page;

class Visualizacoes extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Visualizações';
    protected static ?string $title           = 'Visualizações do Site';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int    $navigationSort  = 95;
    protected static string  $view            = 'filament.pages.visualizacoes';

    /** Dados calculados no mount e usados na view */
    public array $totais  = [];
    public array $paginas = [];

    public function mount(): void
    {
        $this->totais  = PageView::contagens();
        $this->paginas = PageView::porPagina()->toArray();
    }

    /** Recarregar dados (botão Atualizar) */
    public function atualizar(): void
    {
        $this->mount();
        $this->dispatch('$refresh');
    }
}
