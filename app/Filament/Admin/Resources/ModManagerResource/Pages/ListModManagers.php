<?php

namespace App\Filament\Admin\Resources\ModManagerResource\Pages;

use App\Filament\Admin\Resources\ModManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModManagers extends ListRecords
{
    protected static string $resource = ModManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
