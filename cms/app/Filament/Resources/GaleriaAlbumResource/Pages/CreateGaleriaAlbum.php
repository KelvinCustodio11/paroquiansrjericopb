<?php

declare(strict_types=1);

namespace App\Filament\Resources\GaleriaAlbumResource\Pages;

use App\Filament\Resources\GaleriaAlbumResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGaleriaAlbum extends CreateRecord
{
    protected static string $resource = GaleriaAlbumResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
