<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadioBuscaExternaResource\Pages;

use App\Filament\Resources\RadioBuscaExternaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRadioBuscaExterna extends EditRecord
{
    protected static string $resource = RadioBuscaExternaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
