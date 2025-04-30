<?php

namespace App\Filament\Admin\Resources\FeatureFlagResource\Pages;

use App\Filament\Admin\Resources\FeatureFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFeatureFlag extends CreateRecord
{
    protected static string $resource = FeatureFlagResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 