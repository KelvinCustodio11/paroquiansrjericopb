<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Radios extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-radio';

    protected static ?string $navigationLabel = 'Rádios';

    protected static ?string $title = 'Rádios';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.radios';
}
