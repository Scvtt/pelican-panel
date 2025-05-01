<?php

namespace App\Services\Mods;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ReforgerModService
{
    protected string $apiUrl;
    protected array $cachedModsData = [];
    
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
        // Cache the entire workshop data for 5 minutes
        $workshopData = $this->getWorkshopData();
        $mods = $workshopData['data'] ?? [];
        
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
            // Re-index array after filtering
            $mods = array_values($mods);
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
        $pageSize = 12; // 3x4 grid layout
        $offset = ($page - 1) * $pageSize;
        $paginatedMods = array_slice($mods, $offset, $pageSize);
        
        return [
            'data' => $paginatedMods,
            'total' => count($mods),
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil(count($mods) / $pageSize)
        ];
    }
    
    /**
     * Get detailed information about a specific mod
     *
     * @param string $modId
     * @return array
     */
    public function getModDetails(string $modId): array
    {
        // Get from cached workshop data if available
        $workshopData = $this->getWorkshopData();
        $mods = $workshopData['data'] ?? [];
        
        foreach ($mods as $mod) {
            if (($mod['id'] ?? '') === $modId) {
                return $mod;
            }
        }
        
        // If not found in cached data, make a specific request
        return Cache::remember("reforger_mod_{$modId}", now()->addHours(1), function () use ($modId) {           
            try {
                $response = Http::get($this->apiUrl . '/workshop');
                
                if ($response->successful()) {
                    $data = $response->json();
                    $mods = $data['data'] ?? [];
                    
                    foreach ($mods as $mod) {
                        if (($mod['id'] ?? '') === $modId) {
                            return $mod;
                        }
                    }
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
        return Cache::remember("reforger_mod_tags", now()->addDay(), function () {
            try {
                // Extract unique tags from all mods
                $workshopData = $this->getWorkshopData();
                $mods = $workshopData['data'] ?? [];
                
                $allTags = [];
                foreach ($mods as $mod) {
                    if (isset($mod['tags']) && is_array($mod['tags'])) {
                        foreach ($mod['tags'] as $tag) {
                            $allTags[$tag] = true;
                        }
                    }
                }
                
                $uniqueTags = array_keys($allTags);
                sort($uniqueTags);
                
                return $uniqueTags;
            } catch (\Exception $e) {
                \Log::error("Error extracting tags: " . $e->getMessage());
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
        
        // If no addons are left, return an empty string
        if (empty($addons)) {
            return '';
        }
        
        return implode(';', $addons) . ';';
    }
    
    /**
     * Get the complete workshop data
     * 
     * @return array
     */
    protected function getWorkshopData(): array
    {
        return Cache::remember("reforger_workshop_data", now()->addMinutes(5), function () {
            try {
                \Log::debug("Fetching workshop data from: {$this->apiUrl}/workshop");
                $response = Http::get($this->apiUrl . '/workshop');
                
                if ($response->successful()) {
                    $data = $response->json();
                    \Log::debug("Got " . count($data['data'] ?? []) . " mods from workshop API");
                    return $data;
                } else {
                    \Log::error("Workshop API Error: " . $response->body());
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching workshop data: " . $e->getMessage());
            }
            
            return [
                'exported' => now()->format('Y-m-d H:i:s'),
                'data' => []
            ];
        });
    }
} 