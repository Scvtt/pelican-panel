<?php

namespace App\Filament\Server\Resources\ArModResource\Pages;

use App\Filament\Server\Resources\ArModResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArMods extends ListRecords
{
    protected static string $resource = ArModResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 