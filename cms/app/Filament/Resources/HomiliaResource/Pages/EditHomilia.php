<?php

namespace App\Filament\Resources\HomiliaResource\Pages;

use App\Filament\Resources\HomiliaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomilia extends EditRecord
{
    protected static string $resource = HomiliaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
