<?php

namespace App\Filament\Resources\HomiliaResource\Pages;

use App\Filament\Resources\HomiliaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomilias extends ListRecords
{
    protected static string $resource = HomiliaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
