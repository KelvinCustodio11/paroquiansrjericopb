<?php

declare(strict_types=1);

namespace App\Filament\Resources\GaleriaAlbumResource\Pages;

use App\Filament\Resources\GaleriaAlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGaleriaAlbum extends EditRecord
{
    protected static string $resource = GaleriaAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
