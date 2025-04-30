<?php

namespace App\Filament\Admin\Resources\ModManagerResource\Pages;

use App\Filament\Admin\Resources\ModManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewModManager extends ViewRecord
{
    protected static string $resource = ModManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
