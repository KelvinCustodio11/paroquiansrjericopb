<?php

namespace App\Filament\Resources\IgrejaResource\Pages;

use App\Filament\Resources\IgrejaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIgreja extends EditRecord
{
    protected static string $resource = IgrejaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
