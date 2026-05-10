<?php

declare(strict_types=1);

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Radio extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-radio';

    protected static ?string $navigationLabel = 'Rádios';

    protected static ?string $slug = 'radios';

    protected static ?string $clusterBreadcrumb = 'Rádios';
}
