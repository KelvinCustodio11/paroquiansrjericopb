<?php

namespace App\Filament\Resources\CompromissoResource\Pages;

use App\Filament\Resources\CompromissoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompromissos extends ListRecords
{
    protected static string $resource = CompromissoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
