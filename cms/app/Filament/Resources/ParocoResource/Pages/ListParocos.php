<?php

namespace App\Filament\Resources\ParocoResource\Pages;

use App\Filament\Resources\ParocoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParocos extends ListRecords
{
    protected static string $resource = ParocoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
