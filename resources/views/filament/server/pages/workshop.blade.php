<x-filament-panels::page>
    <div class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow">
        <div class="flex justify-center mb-6">
            <x-filament::tabs>
                @foreach ($this->getTabs() as $tabKey => $tab)
                    <x-filament::tabs.item
                        :active="$activeTab === $tabKey"
                        :badge="$tab->getBadge()"
                        :icon="$tab->getIcon()"
                        :href="$this->getTabUrl($tabKey)"
                        :id="$tabKey"
                        wire:click="$set('activeTab', '{{ $tabKey }}')"
                    >
                        {{ $tab->getLabel() }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>

        <div class="grid grid-cols-1 gap-6 mt-6">
            @if ($activeTab === 'installed')
                <!-- Installed Mods Tab -->
                <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Installed Mods</h3>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            This feature is experimental, if you remove all mods you will need to clear your config.json file.
                        </div>
                    </div>
                    
                    @if (count($installedMods) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-2 px-4 text-left text-gray-900 dark:text-white">Name</th>
                                        <th class="py-2 px-4 text-left text-gray-900 dark:text-white">Author</th>
                                        <th class="py-2 px-4 text-left text-gray-900 dark:text-white">Version</th>
                                        <th class="py-2 px-4 text-left text-gray-900 dark:text-white">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($installedMods as $mod)
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <td class="py-2 px-4 text-gray-700 dark:text-gray-300">
                                                {{ $mod['name'] ?? 'Unknown Mod' }}
                                            </td>
                                            <td class="py-2 px-4 text-gray-700 dark:text-gray-300">
                                                {{ $mod['author'] ?? 'Unknown Author' }}
                                            </td>
                                            <td class="py-2 px-4 text-gray-700 dark:text-gray-300">
                                                {{ $mod['version'] ?? $mod['currentVersionNumber'] ?? 'Latest' }}
                                            </td>
                                            <td class="py-2 px-4">
                                                <div class="flex space-x-2">
                                                    <x-filament::button
                                                        type="button"
                                                        wire:click="showVersionSelect('{{ $mod['id'] }}')"
                                                        color="gray"
                                                        size="sm"
                                                        icon="tabler-versions"
                                                    >
                                                        Version
                                                    </x-filament::button>
                                                    
                                                    <x-filament::button
                                                        type="button" 
                                                        wire:click="uninstallMod('{{ $mod['id'] }}')"
                                                        color="danger"
                                                        size="sm"
                                                        icon="tabler-trash"
                                                    >
                                                        Remove
                                                    </x-filament::button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="py-4 text-center text-gray-500 dark:text-gray-400">
                            No mods are currently installed. Browse available mods below to add them.
                        </div>
                    @endif
                </div>
            @elseif ($activeTab === 'available')
                <!-- Available Mods Tab -->
                <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Available Mods</h3>
                        <div class="flex space-x-2 items-center">
                            <div class="w-60">
                                <x-filament::input.affixes
                                    :prefix-icon="fn () => 'tabler-search'"
                                    prefix-icon-color="gray"
                                    wire:target="searchTerm"
                                >
                                    <x-filament::input
                                        type="search"
                                        wire:model.live.debounce.300ms="searchTerm"
                                        placeholder="Search mods..."
                                    />
                                </x-filament::input.affixes>
                            </div>
                            
                            <select 
                                wire:model.live="currentSort"
                                class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                            >
                                <option value="popular">Popular</option>
                                <option value="newest">Newest</option>
                                <option value="updated">Recently Updated</option>
                                <option value="alphabetical">Alphabetical</option>
                            </select>
                            
                            <button
                                type="button"
                                wire:click="toggleSortDirection"
                                class="flex items-center justify-center p-2 h-11 w-11 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                                title="{{ $sortAscending ? 'Sort Descending' : 'Sort Ascending' }}"
                            >
                                @if($sortAscending)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path>
                                    </svg>
                                @endif
                            </button>
                            
                            @if (!empty($availableTags))
                                <div class="relative" x-data="{ open: false }">
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="flex items-center justify-between px-3 py-2 h-10.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                                        style="min-width: 110px;"
                                    >
                                        <span>Tags ({{ count($selectedTags) }})</span>
                                        <svg class="w-4 h-4 ml-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    
                                    <div 
                                        x-show="open" 
                                        @click.away="open = false"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute right-0 mt-2 w-64 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg z-50 overflow-auto max-h-60"
                                    >
                                        <div class="p-2">
                                            @foreach ($availableTags as $tag)
                                                <label class="flex items-center space-x-2 p-2 hover:bg-gray-200 dark:hover:bg-gray-900 rounded">
                                                    <input 
                                                        type="checkbox" 
                                                        wire:model.live="selectedTags" 
                                                        value="{{ $tag }}"
                                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:focus:border-primary-600 dark:focus:ring-primary-600"
                                                    >
                                                    <span class="text-sm text-gray-900 dark:text-white">{{ $tag }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4 mb-4">
                        @foreach ($availableMods as $mod)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-800">
                                @if (!empty($mod['preview_url']))
                                    <img src="{{ $mod['preview_url'] }}" alt="{{ $mod['name'] }}" class="w-full h-40 object-cover" style="max-height: 160px;"/>
                                @else
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center" style="height: 160px;">
                                        <x-tabler-cube class="w-12 h-12 text-gray-400" />
                                    </div>
                                @endif
                                
                                <div class="p-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-1">{{ $mod['name'] }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">By {{ $mod['author'] }}</p>
                                    
                                    @if (!empty($mod['tags']))
                                        <div class="flex flex-wrap gap-1 mb-3">
                                            @php
                                                $displayTags = array_slice($mod['tags'], 0, 3);
                                                $hasMoreTags = count($mod['tags']) > 3;
                                            @endphp

                                            @foreach ($displayTags as $tag)
                                                <span class="text-xs px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                                                    {{ $tag }}
                                                </span>
                                            @endforeach

                                            @if ($hasMoreTags)
                                                <span class="text-xs px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                                                    +{{ count($mod['tags']) - 3 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 {{ $mod['averageRating'] >= 0.8 ? 'text-yellow-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                            <span class="text-xs text-gray-600 dark:text-gray-400 ml-1">
                                                {{ number_format($mod['averageRating'] * 5, 1) }} ({{ number_format($mod['ratingCount']) }})
                                            </span>
                                        </div>
                                        
                                        @php
                                            $isInstalled = collect($installedMods)->contains('id', $mod['id']);
                                        @endphp
                                        @if ($isInstalled)
                                            <div class="flex space-x-2">
                                                <x-filament::button
                                                    type="button"
                                                    wire:click="showVersionSelect('{{ $mod['id'] }}')"
                                                    color="gray"
                                                    size="sm"
                                                    icon="tabler-versions"
                                                >
                                                    Version
                                                </x-filament::button>
                                                
                                                <x-filament::button
                                                    type="button"
                                                    wire:click="uninstallMod('{{ $mod['id'] }}')"
                                                    color="danger"
                                                    size="sm"
                                                    icon="tabler-trash"
                                                >
                                                    Uninstall
                                                </x-filament::button>
                                            </div>
                                        @else
                                            <x-filament::button
                                                type="button"
                                                wire:click="installMod('{{ $mod['id'] }}')"
                                                color="primary"
                                                size="sm"
                                                icon="tabler-download"
                                            >
                                                Install
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if (count($availableMods) === 0)
                        <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                            No mods found matching your criteria.
                        </div>
                    @endif
                    
                    <div class="mt-4 flex justify-between items-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Data exported: {{ $exportedTime }}
                        </div>
                        
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400 mr-2">
                                Page {{ $currentPage }} of {{ $totalPages }}
                            </span>
                            
                            @if ($currentPage > 1)
                                <x-filament::button
                                    type="button"
                                    wire:click="changePage({{ $currentPage - 1 }})"
                                    color="gray"
                                    size="sm"
                                >
                                    Previous
                                </x-filament::button>
                            @endif
                            
                            @if ($currentPage < $totalPages)
                                <x-filament::button
                                    type="button"
                                    wire:click="changePage({{ $currentPage + 1 }})"
                                    color="gray"
                                    size="sm"
                                >
                                    Next
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Version Selection Modal -->
        <x-filament::modal id="version-selector" width="md">
            <x-slot name="heading">
                Select Version
            </x-slot>
            
            <x-slot name="description">
                Enter the version number you want to install (e.g., 1.0.0)
            </x-slot>
            
            <div class="space-y-4">
                <div class="grid gap-2">
                    <label for="selectedVersion" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Version Number
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            wire:model="selectedVersion"
                            type="text"
                            placeholder="Enter version (e.g., 1.0.0)"
                        />
                    </x-filament::input.wrapper>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Specify the exact version number you want to install
                    </p>
                </div>
            </div>
            
            <x-slot name="footerActions">
                <x-filament::button
                    color="primary"
                    wire:click="updateModVersion"
                >
                    Update Version
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'version-selector' })"
                >
                    Cancel
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    </div>
</x-filament-panels::page> 