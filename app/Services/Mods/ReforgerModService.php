<?php

namespace App\Services\Mods;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ReforgerModService
{
    protected string $apiUrl;
    
    public function __construct()
    {
        // If the URL is configured in .env, use that, otherwise use the default
        $this->apiUrl = env('REFORGER_MOD_API_URL', 'https://mod.reforger.link/api');
        
        // Force fallback data in development environment
        if (app()->environment('local')) {
            $this->useMockData = true;
        } else {
            $this->useMockData = false;
        }
    }
    
    /**
     * Get all available mods with optional filtering and sorting
     *
     * @param string|null $sort
     * @param array|null $tags
     * @param int $page
     * @return array
     */
    public function getMods(?string $sort = 'popular', ?array $tags = null, int $page = 1): array
    {
        // Cache key based on parameters
        $cacheKey = "reforger_mods_{$sort}_" . ($tags ? implode('_', $tags) : 'all') . "_{$page}";
        
        // Clear cache during development for testing
        if (app()->environment('local')) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($sort, $tags, $page) {
            try {
                $apiUrl = $this->apiUrl . '/mods';
                \Log::debug("Fetching mods from: {$apiUrl}");
                
                $params = [
                    'sort' => $sort,
                    'page' => $page,
                ];
                
                if (!empty($tags)) {
                    $params['tags'] = $tags;
                }
                
                \Log::debug("Request params: " . json_encode($params));
                
                // If we're forcing mock data in development, skip the HTTP request
                if (app()->environment('local')) {
                    return [
                        'exported' => now()->format('Y-m-d H:i:s'),
                        'data' => $this->getFallbackMods(),
                    ];
                }
                
                $response = Http::get($apiUrl, $params);
                
                \Log::debug("API Response status: " . $response->status());
                
                if ($response->successful()) {
                    $data = $response->json();
                    \Log::debug("Got " . count($data['data'] ?? []) . " mods from API");
                    return $data;
                } else {
                    \Log::error("API Error: " . $response->body());
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching mods: " . $e->getMessage());
            }
            
            // If we get here, something went wrong
            return [
                'exported' => now()->format('Y-m-d H:i:s'),
                'data' => $this->getFallbackMods(),
            ];
        });
    }
    
    /**
     * Get detailed information about a specific mod
     *
     * @param string $modId
     * @return array
     */
    public function getModDetails(string $modId): array
    {
        $cacheKey = "reforger_mod_{$modId}";
        
        // For development, clear cache
        if (app()->environment('local')) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($modId) {
            // In development, return mock data
            if (app()->environment('local')) {
                foreach ($this->getFallbackMods() as $mod) {
                    if ($mod['id'] === $modId) {
                        return $mod;
                    }
                }
                return [];
            }
            
            try {
                $response = Http::get($this->apiUrl . '/mods/' . $modId);
                
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching mod details: " . $e->getMessage());
            }
            
            return [];
        });
    }
    
    /**
     * Get all available tags for filtering mods
     *
     * @return array
     */
    public function getTags(): array
    {
        $cacheKey = "reforger_mod_tags";
        
        // For development, clear cache
        if (app()->environment('local')) {
            Cache::forget($cacheKey);
            return ['WEAPONS', 'VEHICLES', 'MISC', 'CHARACTERS', 'EFFECTS'];
        }
        
        return Cache::remember($cacheKey, now()->addDay(), function () {
            try {
                $response = Http::get($this->apiUrl . '/tags');
                
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching tags: " . $e->getMessage());
            }
            
            return ['WEAPONS', 'VEHICLES', 'MISC', 'CHARACTERS', 'EFFECTS'];
        });
    }
    
    /**
     * Generate a mod list configuration for a server
     *
     * @param array $modIds
     * @return array
     */
    public function generateModList(array $modIds): array
    {
        $mods = [];
        
        foreach ($modIds as $modId) {
            $mod = $this->getModDetails($modId);
            if (!empty($mod)) {
                $mods[] = $mod;
            }
        }
        
        return [
            'exported' => now()->format('Y-m-d H:i:s'),
            'data' => $mods,
        ];
    }
    
    /**
     * Provides fallback mod data in case the API is unavailable
     */
    protected function getFallbackMods(): array
    {
        return [
            [
                'id' => '5965550F24A0C152',
                'name' => 'Where Am I',
                'summary' => 'Shows where you are on the map',
                'averageRating' => 0.94,
                'ratingCount' => 3663,
                'subscriberCount' => 66677,
                'currentVersionNumber' => '1.2.0',
                'createdAt' => '2022-05-19T22:34:50.000Z',
                'updatedAt' => '2025-03-03T05:33:29.000Z',
                'tags' => ['MISC'],
                'author' => 'ValterB',
                'preview_url' => 'https://ar-gcp-cdn.bistudio.com/image/b1dc/dc34a0ebdede5c38b9875012cce0e2a16edda7c3772b1ad6f37073e4ecaa/73057.jpg'
            ],
            [
                'id' => '595F2BF2F44836FB',
                'name' => 'RHS - Status Quo',
                'summary' => 'RHS on Arma Reforger',
                'averageRating' => 0.94,
                'ratingCount' => 3200,
                'subscriberCount' => 32002,
                'currentVersionNumber' => '0.10.4075',
                'createdAt' => '2022-05-18T22:02:44.000Z',
                'updatedAt' => '2025-01-21T08:09:37.000Z',
                'tags' => ['WEAPONS', 'VEHICLES'],
                'author' => 'Red Hammer Studios',
                'preview_url' => 'https://ar-gcp-cdn.bistudio.com/image/7c58/a2ea07905fad8abb0e6eb56ab4bb01b003b81ea1b14b86024bd16a6323a1/14490.jpg'
            ],
            [
                'id' => '59D64ADD6FC59CBF',
                'name' => 'Project Redline - UH-60',
                'summary' => 'Project Redline: UH60 Black Hawk Helicopter.',
                'averageRating' => 0.93,
                'ratingCount' => 2760,
                'subscriberCount' => 64518,
                'currentVersionNumber' => '1.4.1',
                'createdAt' => '2022-07-11T04:52:59.000Z',
                'updatedAt' => '2024-02-19T05:23:44.000Z',
                'tags' => ['VEHICLES'],
                'author' => 'Ralian',
                'preview_url' => 'https://ar-gcp-cdn.bistudio.com/image/mL2X/AcrMkLvj3p9CHnhLFEdt8zRUmFeqxhOMUZ1Bd04/16547.jpg'
            ],
        ];
    }
    
    /**
     * Parse the WORKSHOP_ADDONS egg variable format to extract mod IDs and versions
     * 
     * @param string $workshopAddons
     * @return array Array of ['id' => string, 'version' => string|null]
     */
    public function parseWorkshopAddons(string $workshopAddons): array
    {
        if (empty($workshopAddons)) {
            return [];
        }
        
        $mods = [];
        $addonsList = explode(';', $workshopAddons);
        
        foreach ($addonsList as $addon) {
            $addon = trim($addon);
            if (empty($addon)) {
                continue;
            }
            
            // Check if there's a version specified using @VERSION format
            if (str_contains($addon, '@')) {
                [$modId, $version] = explode('@', $addon, 2);
                $mods[] = [
                    'id' => $modId,
                    'version' => $version,
                ];
            } else {
                $mods[] = [
                    'id' => $addon,
                    'version' => null,
                ];
            }
        }
        
        return $mods;
    }
    
    /**
     * Generate the WORKSHOP_ADDONS egg variable format from mod IDs and versions
     * 
     * @param array $mods Array of ['id' => string, 'version' => string|null]
     * @return string
     */
    public function generateWorkshopAddons(array $mods): string
    {
        $addons = [];
        
        foreach ($mods as $mod) {
            if (!isset($mod['id']) || empty($mod['id'])) {
                continue;
            }
            
            if (isset($mod['version']) && !empty($mod['version'])) {
                $addons[] = $mod['id'] . '@' . $mod['version'];
            } else {
                $addons[] = $mod['id'];
            }
        }
        
        return implode(';', $addons);
    }
} 