<?php

namespace App\Services\Mods;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ReforgerModService
{
    protected string $apiUrl = 'https://mod.reforger.link/api';
    
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
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($sort, $tags, $page) {
            $response = Http::get($this->apiUrl . '/mods', [
                'sort' => $sort,
                'tags' => $tags,
                'page' => $page,
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [
                'exported' => now()->format('Y-m-d H:i:s'),
                'data' => [],
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
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($modId) {
            $response = Http::get($this->apiUrl . '/mods/' . $modId);
            
            if ($response->successful()) {
                return $response->json();
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
        
        return Cache::remember($cacheKey, now()->addDay(), function () {
            $response = Http::get($this->apiUrl . '/tags');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
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