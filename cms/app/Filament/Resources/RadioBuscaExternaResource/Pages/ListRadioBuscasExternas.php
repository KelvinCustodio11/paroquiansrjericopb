<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadioBuscaExternaResource\Pages;

use App\Filament\Resources\RadioBuscaExternaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRadioBuscasExternas extends ListRecords
{
    protected static string $resource = RadioBuscaExternaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
