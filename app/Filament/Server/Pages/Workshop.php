<?php

namespace App\Filament\Server\Pages;

use App\Models\EggVariable;
use App\Models\FeatureFlag;
use App\Models\Server;
use App\Models\ServerVariable;
use App\Services\Mods\ReforgerModService;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Filament\Resources\Components\Tab;

class Workshop extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'tabler-cube';
    
    protected static ?int $navigationSort = 8; // Place between Startup (9) and lower items
    
    protected static string $view = 'filament.server.pages.workshop';
    
    public array $availableMods = [];
    public array $installedMods = [];
    public array $availableTags = [];
    public string $exportedTime = '';
    public string $currentSort = 'popular';
    public array $selectedTags = [];
    public int $currentPage = 1;
    public int $totalPages = 1;
    public string $activeTab = 'available';
    
    public function mount(): void
    {
        // Check if this server's egg has the AR_WORKSHOP feature flag enabled
        if (!$this->canAccessWorkshop()) {
            redirect()->to(Filament::getUrl());
        }
        
        $this->activeTab = request()->query('tab', 'available');
        if (!in_array($this->activeTab, ['available', 'installed'])) {
            $this->activeTab = 'available';
        }
        
        $this->loadMods();
        $this->loadInstalledMods();
        $this->loadTags();
    }
    
    public function getTabUrl(string $tab): string
    {
        return url(request()->url() . '?tab=' . $tab);
    }
    
    public function getTabs(): array
    {
        $totalAvailableMods = 0;
        
        if (count($this->availableMods) > 0) {
            // If we have mods and pagination, calculate the total
            $totalAvailableMods = isset($this->totalPages) ? 
                min($this->totalPages * 12, 99) : // Cap at 99+ to avoid UI issues
                count($this->availableMods);
        }
        
        return [
            'available' => Tab::make('Available Mods')
                ->badge($totalAvailableMods),
                
            'installed' => Tab::make('Installed Mods')
                ->badge(count($this->installedMods)),
        ];
    }
    
    public function updatedActiveTab(): void
    {
        // Update the URL to reflect the current tab
        $this->dispatch('urlChanged', url: $this->getTabUrl($this->activeTab));
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
    
    public function loadMods(): void
    {
        try {
            $modService = app(ReforgerModService::class);
            $result = $modService->getMods($this->currentSort, $this->selectedTags, $this->currentPage);
            
            $this->availableMods = $result['data'] ?? [];
            $this->exportedTime = $result['exported'] ?? now()->format('Y-m-d H:i:s');
            $this->totalPages = $result['totalPages'] ?? 1;
            
            \Log::debug("Loaded " . count($this->availableMods) . " mods");
        } catch (\Exception $e) {
            \Log::error("Error loading mods: " . $e->getMessage());
            $this->availableMods = [];
            $this->exportedTime = now()->format('Y-m-d H:i:s');
            $this->totalPages = 1;
        }
    }
    
    public function loadInstalledMods(): void
    {
        $workshopVariable = $this->getWorkshopAddonsVariable();
        $modService = app(ReforgerModService::class);
        
        if (!$workshopVariable) {
            $this->installedMods = [];
            return;
        }
        
        $parsedMods = $modService->parseWorkshopAddons($workshopVariable->variable_value);
        $modDetails = [];
        
        foreach ($parsedMods as $mod) {
            $details = $modService->getModDetails($mod['id']);
            if (!empty($details)) {
                $details['version'] = $mod['version'] ?? $details['currentVersionNumber'] ?? null;
                $modDetails[] = $details;
            }
        }
        
        $this->installedMods = $modDetails;
    }
    
    public function loadTags(): void
    {
        $modService = app(ReforgerModService::class);
        $this->availableTags = $modService->getTags();
    }
    
    public function changePage(int $page): void
    {
        $this->currentPage = $page;
        $this->loadMods();
    }
    
    public function changeSort(string $sort): void
    {
        $this->currentSort = $sort;
        $this->loadMods();
    }
    
    public function toggleTag(string $tag): void
    {
        if (in_array($tag, $this->selectedTags)) {
            $this->selectedTags = array_filter($this->selectedTags, fn($t) => $t !== $tag);
        } else {
            $this->selectedTags[] = $tag;
        }
        
        $this->loadMods();
    }
    
    public function installMod(string $modId): void
    {
        // Find mod in available mods
        $mod = collect($this->availableMods)->firstWhere('id', $modId);
        
        if (!$mod) {
            return;
        }
        
        $workshopVariable = $this->getWorkshopAddonsVariable();
        if (!$workshopVariable) {
            return;
        }
        
        $modService = app(ReforgerModService::class);
        $existingMods = $modService->parseWorkshopAddons($workshopVariable->variable_value);
        
        // Check if mod is already installed
        if (collect($existingMods)->contains('id', $modId)) {
            return;
        }
        
        // Add new mod
        $existingMods[] = [
            'id' => $modId,
            'version' => $mod['currentVersionNumber'] ?? null
        ];
        
        $this->saveWorkshopAddons($existingMods);
        $this->loadInstalledMods();
    }
    
    public function uninstallMod(string $modId): void
    {
        $workshopVariable = $this->getWorkshopAddonsVariable();
        if (!$workshopVariable) {
            return;
        }
        
        $modService = app(ReforgerModService::class);
        $existingMods = $modService->parseWorkshopAddons($workshopVariable->variable_value);
        
        // Filter out the mod to uninstall
        $filteredMods = array_filter($existingMods, function ($mod) use ($modId) {
            return $mod['id'] !== $modId;
        });
        
        $this->saveWorkshopAddons($filteredMods);
        $this->loadInstalledMods();
    }
    
    public function updateModVersion(string $modId, string $version): void
    {
        $workshopVariable = $this->getWorkshopAddonsVariable();
        if (!$workshopVariable) {
            return;
        }
        
        $modService = app(ReforgerModService::class);
        $existingMods = $modService->parseWorkshopAddons($workshopVariable->variable_value);
        
        // Update version for the specified mod
        foreach ($existingMods as &$mod) {
            if ($mod['id'] === $modId) {
                $mod['version'] = $version;
                break;
            }
        }
        
        $this->saveWorkshopAddons($existingMods);
        $this->loadInstalledMods();
    }
    
    public function generateModList(): array
    {
        $workshopVariable = $this->getWorkshopAddonsVariable();
        if (!$workshopVariable) {
            return [
                'exported' => now()->format('Y-m-d H:i:s'),
                'data' => [],
            ];
        }
        
        $modService = app(ReforgerModService::class);
        $parsedMods = $modService->parseWorkshopAddons($workshopVariable->variable_value);
        $modIds = array_column($parsedMods, 'id');
        
        return $modService->generateModList($modIds);
    }
    
    /**
     * Get the WORKSHOP_ADDONS egg variable
     */
    protected function getWorkshopAddonsVariable(): ?ServerVariable
    {
        try {
            /** @var Server $server */
            $server = Filament::getTenant();
            
            $variable = EggVariable::where('egg_id', $server->egg_id)
                ->where('env_variable', 'WORKSHOP_ADDONS')
                ->first();
                
            if (!$variable) {
                \Log::warning("WORKSHOP_ADDONS egg variable not found for egg_id: " . $server->egg_id);
                return null;
            }
            
            $serverVar = ServerVariable::where('server_id', $server->id)
                ->where('variable_id', $variable->id)
                ->first();
                
            if (!$serverVar) {
                \Log::info("Creating WORKSHOP_ADDONS server variable for server: " . $server->id);
                
                // Create the server variable if it doesn't exist
                $serverVar = ServerVariable::create([
                    'server_id' => $server->id,
                    'variable_id' => $variable->id,
                    'variable_value' => '',
                ]);
            }
            
            return $serverVar;
        } catch (\Exception $e) {
            \Log::error("Error getting WORKSHOP_ADDONS variable: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save changes to the WORKSHOP_ADDONS egg variable
     */
    protected function saveWorkshopAddons(array $modData): void
    {
        $modService = app(ReforgerModService::class);
        $workshopVariable = $this->getWorkshopAddonsVariable();
        
        if (!$workshopVariable) {
            return;
        }
        
        $workshopVariable->update([
            'variable_value' => $modService->generateWorkshopAddons($modData)
        ]);
    }
} 