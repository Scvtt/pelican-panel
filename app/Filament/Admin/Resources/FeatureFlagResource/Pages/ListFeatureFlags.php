<?php

namespace App\Filament\Admin\Resources\FeatureFlagResource\Pages;

use App\Filament\Admin\Resources\FeatureFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeatureFlags extends ListRecords
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Feature Flag')
                ->hidden(fn () => $this->getTableQuery()->count() <= 0),
        ];
    }
    
    protected function isTableSearchable(): bool
    {
        return $this->getTableQuery()->count() > 0;
    }
} 