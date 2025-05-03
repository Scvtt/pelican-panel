<?php

namespace App\Filament\Server\Resources\ArModResource\Pages;

use App\Filament\Server\Resources\ArModResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArMod extends EditRecord
{
    protected static string $resource = ArModResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 