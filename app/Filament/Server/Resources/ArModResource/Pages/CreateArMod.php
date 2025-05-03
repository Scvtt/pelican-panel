<?php

namespace App\Filament\Server\Resources\ArModResource\Pages;

use App\Filament\Server\Resources\ArModResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateArMod extends CreateRecord
{
    protected static string $resource = ArModResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['server_id'] = Filament::getTenant()->id;
    
        return $data;
    }
} 