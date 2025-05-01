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
                // Use the correct /workshop endpoint that returns the full mod list
                $apiUrl = $this->apiUrl . '/workshop';
                \Log::debug("Fetching mods from: {$apiUrl}");
                
                $response = Http::get($apiUrl);
                
                \Log::debug("API Response status: " . $response->status());
                
                if ($response->successful()) {
                    $responseData = $response->json();
                    $mods = $responseData['data'] ?? [];
                    \Log::debug("Got " . count($mods) . " mods from API");
                    
                    // Filter by tags if provided
                    if (!empty($tags)) {
                        $mods = array_filter($mods, function($mod) use ($tags) {
                            $modTags = $mod['tags'] ?? [];
                            foreach ($tags as $tag) {
                                if (in_array($tag, $modTags)) {
                                    return true;
                                }
                            }
                            return false;
                        });
                    }
                    
                    // Sort the mods
                    if ($sort === 'popular') {
                        usort($mods, function($a, $b) {
                            return ($b['subscriberCount'] ?? 0) <=> ($a['subscriberCount'] ?? 0);
                        });
                    } elseif ($sort === 'rating') {
                        usort($mods, function($a, $b) {
                            return ($b['averageRating'] ?? 0) <=> ($a['averageRating'] ?? 0);
                        });
                    } elseif ($sort === 'newest') {
                        usort($mods, function($a, $b) {
                            return strtotime($b['createdAt'] ?? 0) <=> strtotime($a['createdAt'] ?? 0);
                        });
                    } elseif ($sort === 'updated') {
                        usort($mods, function($a, $b) {
                            return strtotime($b['updatedAt'] ?? 0) <=> strtotime($a['updatedAt'] ?? 0);
                        });
                    }
                    
                    // Handle pagination
                    $pageSize = 20; // You can adjust this or make it configurable
                    $offset = ($page - 1) * $pageSize;
                    $paginatedMods = array_slice($mods, $offset, $pageSize);
                    
                    return [
                        'data' => $paginatedMods,
                        'total' => count($mods),
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'totalPages' => ceil(count($mods) / $pageSize)
                    ];
                } else {
                    \Log::error("API Error: " . $response->body());
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching mods: " . $e->getMessage());
            }
            
            // Return empty array structure instead of null when no data is available
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'pageSize' => 20,
                'totalPages' => 0
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