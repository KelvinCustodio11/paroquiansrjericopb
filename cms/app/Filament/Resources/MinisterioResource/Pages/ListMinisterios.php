<?php

namespace App\Filament\Resources\MinisterioResource\Pages;

use App\Filament\Resources\MinisterioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMinisterios extends ListRecords
{
    protected static string $resource = MinisterioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
