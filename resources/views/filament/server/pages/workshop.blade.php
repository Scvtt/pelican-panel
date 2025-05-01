<x-filament-panels::page>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="flex items-center space-x-4 mb-4">
            <div class="rounded-lg bg-primary-50 dark:bg-primary-950/50 p-3">
                <x-tabler-cube class="w-8 h-8 text-primary-500" />
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Workshop</h2>
        </div>
        
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Manage mods and configurations for your game server through this Workshop interface.
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800">
                <h3 class="text-lg font-medium mb-2 text-gray-900 dark:text-white">Available Mods</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Browse and install mods from the workshop.</p>
                <div class="flex justify-end">
                    <button class="bg-primary-500 text-white px-4 py-2 rounded hover:bg-primary-600 transition">
                        Browse Mods
                    </button>
                </div>
            </div>
            
            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800">
                <h3 class="text-lg font-medium mb-2 text-gray-900 dark:text-white">Installed Mods</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Manage your currently installed mods.</p>
                <div class="flex justify-end">
                    <button class="bg-primary-500 text-white px-4 py-2 rounded hover:bg-primary-600 transition">
                        Manage Mods
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page> 