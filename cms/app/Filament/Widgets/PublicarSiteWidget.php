<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\PublicarSite;
use Filament\Widgets\Widget;

class PublicarSiteWidget extends Widget
{
    protected static string $view = 'filament.widgets.publicar-site-widget';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';
}
