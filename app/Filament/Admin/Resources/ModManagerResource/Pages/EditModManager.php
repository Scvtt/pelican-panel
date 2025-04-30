<?php

namespace App\Filament\Admin\Resources\ModManagerResource\Pages;

use App\Filament\Admin\Resources\ModManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModManager extends EditRecord
{
    protected static string $resource = ModManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
