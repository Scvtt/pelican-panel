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
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Support\Exceptions\Halt;
use App\Models\Variable;
use Illuminate\Support\Str;
use Livewire\Component;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Filament\Actions\Action as PageAction;
use App\Models\ArMod;

class Workshop extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'tabler-cube';
    
    protected static ?int $navigationSort = 8; // Place between Startup (9) and lower items
    
    protected static string $view = 'filament.server.pages.workshop';
    
    public ?string $activeTab = 'installed';
    
    /**
     * Available mods from the Reforger Workshop
     */
    public array $availableMods = [];
    
    /**
     * Installed mods on the server
     */
    public array $installedMods = [];
    
    /**
     * Available tags for filtering
     */
    public array $availableTags = [];
    
    /**
     * Selected tags for filtering
     */
    public array $selectedTags = [];
    
    /**
     * Current page for pagination
     */
    public int $currentPage = 1;
    
    /**
     * Current sort method
     */
    public string $currentSort = 'popular';
    
    /**
     * Current sort direction (true = ascending, false = descending)
     */
    public bool $sortAscending = false;
    
    /**
     * Total pages for pagination
     */
    public int $totalPages = 1;
    
    /**
     * Last data export time
     */
    public string $exportedTime = '';
    
    /**
     * Current mod ID selected for version updates
     */
    public ?string $selectedModId = null;
    
    /**
     * The user input version
     */
    public ?string $selectedVersion = null;
    
    /**
     * Search term for filtering mods
     */
    public string $searchTerm = '';
    
    /**
     * IDs of selected mods for bulk actions
     */
    public array $selectedModIds = [];
    
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
        return [
            'available' => Tab::make('Available Mods'),
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
            $result = $modService->getMods($this->currentSort, $this->selectedTags, $this->currentPage, $this->sortAscending, $this->searchTerm);
            
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
        
        // Sync mods with database after loading
        $this->syncModsWithDatabase();
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
    
    public function updatedSelectedTags(): void
    {
        $this->currentPage = 1; // Reset to first page when filters change
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
        // Get existing mods
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
        
        // Save the filtered list
        $this->saveWorkshopAddons($filteredMods);
        $this->loadInstalledMods();
        
        Notification::make()
            ->success()
            ->title('Mod Removed')
            ->body('Selected mod has been successfully uninstalled')
            ->send();
    }
    
    public function showVersionSelect(string $modId): void
    {
        $this->selectedModId = $modId;
        $this->selectedVersion = null;
        
        // Find the current version for this mod
        $mod = collect($this->installedMods)->firstWhere('id', $modId);
        if ($mod && isset($mod['version'])) {
            $this->selectedVersion = $mod['version'];
        }
        
        $this->dispatch('open-modal', id: 'version-selector');
    }
    
    public function updateModVersion(): void
    {
        if (empty($this->selectedModId)) {
            return;
        }
        
        // Validate the version format
        if (empty($this->selectedVersion) || !preg_match('/^\d+(\.\d+)*$/', $this->selectedVersion)) {
            // Show an error notification for invalid version format
            Notification::make()
                ->danger()
                ->title('Invalid Version Format')
                ->body('Please enter a valid version number (e.g., 1.0.0)')
                ->send();
            return;
        }
        
        $workshopVariable = $this->getWorkshopAddonsVariable();
        if (!$workshopVariable) {
            return;
        }
        
        $modService = app(ReforgerModService::class);
        $existingMods = $modService->parseWorkshopAddons($workshopVariable->variable_value);
        
        // Update version for the specified mod
        foreach ($existingMods as &$mod) {
            if ($mod['id'] === $this->selectedModId) {
                $mod['version'] = $this->selectedVersion;
                break;
            }
        }
        
        $this->saveWorkshopAddons($existingMods);
        $this->loadInstalledMods();
        
        // Show success notification
        Notification::make()
            ->success()
            ->title('Version Updated')
            ->body("Mod version has been updated to {$this->selectedVersion}")
            ->send();
        
        // Close the modal
        $this->dispatch('close-modal', id: 'version-selector');
        $this->selectedModId = null;
        $this->selectedVersion = null;
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
    
    public function updatedCurrentSort(): void
    {
        $this->currentPage = 1; // Reset to first page when sort changes
        $this->loadMods();
    }
    
    public function toggleSortDirection(): void
    {
        $this->sortAscending = !$this->sortAscending;
        $this->currentPage = 1; // Reset to first page when sort direction changes
        $this->loadMods();
    }
    
    public function updatedSearchTerm(): void
    {
        $this->currentPage = 1; // Reset to first page when search changes
        $this->loadMods();
    }
    
    public function bulkUninstallConfirm(): void
    {
        if (empty($this->selectedModIds)) {
            // If no mods are explicitly selected, prepare to remove all
            $this->selectedModIds = collect($this->installedMods)->pluck('id')->toArray();
        }
        
        $this->dispatch('open-modal', id: 'confirm-bulk-uninstall');
    }
    
    public function bulkUninstallMods(): void
    {
        if (empty($this->selectedModIds)) {
            return;
        }
        
        $workshopVariable = $this->getWorkshopAddonsVariable();
        if (!$workshopVariable) {
            return;
        }
        
        $modService = app(ReforgerModService::class);
        $existingMods = $modService->parseWorkshopAddons($workshopVariable->variable_value);
        
        // Filter out the mods to uninstall
        $filteredMods = array_filter($existingMods, function ($mod) {
            return !in_array($mod['id'], $this->selectedModIds);
        });
        
        // Save the filtered list
        $this->saveWorkshopAddons($filteredMods);
        $this->loadInstalledMods();
        
        // Clear selected mods
        $this->selectedModIds = [];
        
        // Show success notification
        $count = count($this->selectedModIds);
        Notification::make()
            ->success()
            ->title($count > 1 ? "$count Mods Removed" : "Mod Removed")
            ->body($count > 1 ? "Selected mods have been successfully uninstalled" : "Selected mod has been successfully uninstalled")
            ->send();
            
        // Close the modal
        $this->dispatch('close-modal', id: 'confirm-bulk-uninstall');
    }
    
    /**
     * Sync mods for this server with the database
     */
    protected function syncModsWithDatabase(): void
    {
        /** @var Server $server */
        $server = Filament::getTenant();
        
        // First, update all existing mods to not installed
        ArMod::where('server_id', $server->id)
            ->update(['is_installed' => false]);
        
        foreach ($this->installedMods as $mod) {
            // Create or update the mod record
            ArMod::updateOrCreate(
                [
                    'uuid' => $mod['id'],
                    'server_id' => $server->id,
                ],
                [
                    'name' => $mod['name'] ?? 'Unknown',
                    'author' => $mod['author'] ?? 'Unknown',
                    'version' => $mod['version'] ?? null,
                    'preview_url' => $mod['preview_url'] ?? null,
                    'tags' => $mod['tags'] ?? [],
                    'description' => $mod['description'] ?? null,
                    'is_installed' => true,
                    'meta' => [
                        'averageRating' => $mod['averageRating'] ?? 0,
                        'ratingCount' => $mod['ratingCount'] ?? 0,
                        'currentVersionNumber' => $mod['currentVersionNumber'] ?? null,
                    ],
                ]
            );
        }
    }

    /**
     * After mods are loaded or modified, sync them with the database
     */
    public function updatedInstalledMods(): void
    {
        $this->syncModsWithDatabase();
    }

    /**
     * Get table records
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
     */
    public function getTableRecords(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
    {
        /** @var Server $server */
        $server = Filament::getTenant();
        
        return ArMod::where('server_id', $server->id)
            ->where('is_installed', true)
            ->latest()
            ->get();
    }

    /**
     * Get a unique record key for a table record.
     * 
     * @param \Illuminate\Database\Eloquent\Model $record
     * @return string
     */
    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model $record): string
    {
        // For ArMod models, return the UUID
        if ($record instanceof ArMod) {
            return $record->uuid;
        }
        
        // Fallback for any other type of model
        return (string)($record->getKey() ?? uniqid());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ArMod::query()->where('server_id', Filament::getTenant()->id)->where('is_installed', true))
            ->columns([
                TextColumn::make('name')
                    ->label('Mod Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('author')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version')
                    ->label('Version')
                    ->sortable(),
            ])
            ->headerActions([
                // Moving the refresh action to be in the global search toolbar instead
            ])
            ->actions([
                Action::make('version')
                    ->label('Version')
                    ->color('gray')
                    ->icon('tabler-versions')
                    ->action(fn (ArMod $record) => $this->showVersionSelect($record->uuid)),
                Action::make('uninstall')
                    ->label('Remove')
                    ->color('danger')
                    ->icon('tabler-trash')
                    ->requiresConfirmation()
                    ->action(fn (ArMod $record) => $this->uninstallMod($record->uuid)),
            ])
            ->bulkActions([
                BulkAction::make('remove')
                    ->label('Remove selected')
                    ->color('danger')
                    ->icon('tabler-trash')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        // Get all selected mod UUIDs
                        $modIds = $records->pluck('uuid')->all();
                        
                        // Get existing mods
                        $workshopVariable = $this->getWorkshopAddonsVariable();
                        if (!$workshopVariable) {
                            return;
                        }
                        
                        $modService = app(ReforgerModService::class);
                        $existingMods = $modService->parseWorkshopAddons($workshopVariable->variable_value);
                        
                        // Filter out the mods to uninstall
                        $filteredMods = array_filter($existingMods, function ($mod) use ($modIds) {
                            return !in_array($mod['id'], $modIds);
                        });
                        
                        // Save the filtered list
                        $this->saveWorkshopAddons($filteredMods);
                        $this->loadInstalledMods();
                        
                        Notification::make()
                            ->success()
                            ->title(count($modIds) > 1 ? count($modIds) . ' Mods Removed' : 'Mod Removed')
                            ->body(count($modIds) > 1 ? 'Selected mods have been successfully uninstalled' : 'Selected mod has been successfully uninstalled')
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No mods installed')
            ->emptyStateDescription('No mods are currently installed. Browse available mods to add them.')
            ->defaultSort('name')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('author')
                    ->label('Author')
                    ->options(function() {
                        return ArMod::where('server_id', Filament::getTenant()->id)
                            ->where('is_installed', true)
                            ->distinct()
                            ->pluck('author', 'author')
                            ->toArray();
                    })
            ])
            ->filtersFormColumns(3)
            ->searchable(true, ['name', 'author'])
            ->searchDebounce(500)
            ->paginated(false)
            ->contentGrid([
                'md' => 1,
            ]);
    }
    
    /**
     * Process data updates from Livewire
     */
    protected function afterUpdated($name, $value): void
    {
        // If the installed mods changed, make sure the table data is fresh
        if ($name === 'installedMods') {
            $this->resetTable();
        }
    }
} 