<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class LogoService
{
    private const LOGO_DIRECTORY = 'images/Logo Mitra';
    private const PLACEHOLDER_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56"><rect width="56" height="56" fill="#f8f9fa"/><text x="50%" y="50%" font-family="Arial" font-size="12" fill="#6c757d" text-anchor="middle" dominant-baseline="middle">No Logo</text></svg>';
    private const SUPPORTED_EXTENSIONS = ['avif', 'webp', 'png', 'jpeg', 'jpg'];
    
    private static array $cache = [];
    private static array $fileCache = [];

    /**
     * Find logo path for a company by searching the directory
     * Handles prefix variations (with/without PT)
     */
    public static function find(?string $companyName): string
    {
        if (!$companyName) {
            return self::getPlaceholder();
        }

        // Check cache first
        if (isset(self::$cache[$companyName])) {
            return self::$cache[$companyName];
        }

        $basePath = public_path(self::LOGO_DIRECTORY);
        
        // Ensure directory exists
        if (!is_dir($basePath)) {
            self::$cache[$companyName] = self::getPlaceholder();
            return self::getPlaceholder();
        }

        // Build list of variations to try (with different prefixes)
        $namesToTry = self::generateNameVariations(trim($companyName));
        
        // Load file list once
        if (empty(self::$fileCache)) {
            try {
                $files = scandir($basePath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        self::$fileCache[strtolower($file)] = $file;
                    }
                }
            } catch (\Exception $e) {
                self::$cache[$companyName] = self::getPlaceholder();
                return self::getPlaceholder();
            }
        }

        // Try to find matching file
        foreach ($namesToTry as $nameVariation) {
            foreach (self::SUPPORTED_EXTENSIONS as $ext) {
                $searchKey = strtolower($nameVariation . '.' . $ext);
                
                if (isset(self::$fileCache[$searchKey])) {
                    $actualFile = self::$fileCache[$searchKey];
                    $url = asset(self::LOGO_DIRECTORY . '/' . $actualFile);
                    self::$cache[$companyName] = $url;
                    return $url;
                }
            }
        }

        // Cache the placeholder for this company too
        $placeholder = self::getPlaceholder();
        self::$cache[$companyName] = $placeholder;
        
        return $placeholder;
    }

    /**
     * Generate variations of company name to try matching
     * E.g., "COMMTECH AGUNG" becomes ["COMMTECH AGUNG", "PT COMMTECH AGUNG"]
     */
    private static function generateNameVariations(string $name): array
    {
        $variations = [];
        $lower = strtolower($name);
        
        // Original name
        $variations[] = $name;
        
        // With PT prefix
        if (!str_starts_with($lower, 'pt ')) {
            $variations[] = 'PT ' . $name;
        }
        
        // Case-insensitive variations
        $variations[] = strtoupper($name);
        $variations[] = strtolower($name);
        
        return $variations;
    }

    /**
     * Get base64-encoded placeholder SVG
     */
    private static function getPlaceholder(): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::PLACEHOLDER_SVG);
    }

    /**
     * Clear cache (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$cache = [];
        self::$fileCache = [];
    }
}
