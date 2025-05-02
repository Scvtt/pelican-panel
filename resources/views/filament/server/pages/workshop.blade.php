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
                            Mods are stored in the WORKSHOP_ADDONS egg variable
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
                                                <button 
                                                    type="button" 
                                                    wire:click="uninstallMod('{{ $mod['id'] }}')"
                                                    class="px-3 py-1 bg-orange-500 text-white text-sm rounded hover:bg-orange-600"
                                                >
                                                    Remove
                                                </button>
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
                            <select 
                                wire:model.live="currentSort"
                                class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                            >
                                <option value="popular">Popular</option>
                                <option value="newest">Newest</option>
                                <option value="updated">Recently Updated</option>
                                <option value="alphabetical">Alphabetical</option>
                            </select>
                        </div>
                    </div>
                    
                    @if (!empty($availableTags))
                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach ($availableTags as $tag)
                                <button 
                                    type="button"
                                    wire:click="toggleTag('{{ $tag }}')"
                                    class="px-2 py-1 text-xs rounded-full {{ in_array($tag, $selectedTags) ? 'bg-primary-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                                >
                                    {{ $tag }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    
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
                                            @foreach ($mod['tags'] as $tag)
                                                <span class="text-xs px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                                                    {{ $tag }}
                                                </span>
                                            @endforeach
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
                                            <button 
                                                type="button"
                                                wire:click="uninstallMod('{{ $mod['id'] }}')"
                                                class="px-3 py-1 bg-orange-500 text-white text-sm rounded hover:bg-orange-600"
                                            >
                                                Uninstall
                                            </button>
                                        @else
                                            <button 
                                                type="button"
                                                wire:click="installMod('{{ $mod['id'] }}')"
                                                class="px-3 py-1 bg-primary-500 text-white text-sm rounded hover:bg-primary-600"
                                            >
                                                Install
                                            </button>
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
                                <button 
                                    type="button"
                                    wire:click="changePage({{ $currentPage - 1 }})"
                                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded"
                                >
                                    Previous
                                </button>
                            @endif
                            
                            @if ($currentPage < $totalPages)
                                <button 
                                    type="button"
                                    wire:click="changePage({{ $currentPage + 1 }})"
                                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded"
                                >
                                    Next
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page> 