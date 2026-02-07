<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiRequests\Pages;

use App\Filament\Resources\AiRequests\AiRequestResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAiRequests extends ManageRecords
{
    protected static string $resource = AiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
