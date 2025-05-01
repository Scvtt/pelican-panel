<?php

namespace App\Filament\Server\Pages;

use App\Models\FeatureFlag;
use App\Models\Server;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class Workshop extends Page
{
    protected static ?string $navigationIcon = 'tabler-cube';
    
    protected static ?int $navigationSort = 8; // Place between Startup (9) and lower items
    
    protected static string $view = 'filament.server.pages.workshop';
    
    public function mount(): void
    {
        // Check if this server's egg has the AR_WORKSHOP feature flag enabled
        if (!$this->canAccessWorkshop()) {
            redirect()->to(Filament::getUrl());
        }
    }
    
    // This ensures the page appears in navigation only when the feature flag is enabled
    public static function canAccess(): bool
    {
        if (!parent::canAccess()) {
            return false;
        }
        
        /** @var Server $server */
        $server = Filament::getTenant();
        
        if ($server->isInConflictState()) {
            return false;
        }
        
        // Check if this server's egg has the AR_WORKSHOP feature flag enabled
        return static::hasWorkshopFeature($server);
    }
    
    protected function canAccessWorkshop(): bool
    {
        /** @var Server $server */
        $server = Filament::getTenant();
        
        return static::hasWorkshopFeature($server);
    }
    
    protected static function hasWorkshopFeature(Server $server): bool
    {
        // Get the egg_id for this server
        $eggId = $server->egg_id;
        
        // Check if there's an enabled feature flag with flag="AR_WORKSHOP" for this egg
        return FeatureFlag::where('flag', 'AR_WORKSHOP')
            ->where('enabled', true)
            ->whereHas('eggs', function ($query) use ($eggId) {
                $query->where('eggs.id', $eggId);
            })
            ->exists();
    }
} 