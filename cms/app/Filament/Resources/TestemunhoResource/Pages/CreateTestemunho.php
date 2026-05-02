<?php

declare(strict_types=1);

namespace App\Filament\Resources\TestemunhoResource\Pages;

use App\Filament\Resources\TestemunhoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestemunho extends CreateRecord
{
    protected static string $resource = TestemunhoResource::class;
}
