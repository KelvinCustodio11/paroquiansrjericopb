<?php

declare(strict_types=1);

namespace App\Filament\Resources\TestemunhoResource\Pages;

use App\Filament\Resources\TestemunhoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestemunho extends EditRecord
{
    protected static string $resource = TestemunhoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
