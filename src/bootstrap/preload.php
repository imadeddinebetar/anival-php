<?php

/**
 * OPcache Preload Script
 * 
 * This script preloads framework classes into OPcache for improved performance.
 * 
 * To enable:
 * 1. Add to php.ini: opcache.preload=/path/to/preload.php
 * 2. Or use: php -d opcache.preload=preload.php
 * 
 * Note: After modifying this file or any preloaded class, 
 * you must restart PHP-FPM/Apache to clear the preload cache.
 */

// Prevent direct execution in web context
if (php_sapi_name() !== 'cli' && !ini_get('opcache.enable')) {
    return;
}

// Get the base path
$basePath = dirname(__DIR__);

/**
 * Recursively include all PHP files in a directory
 */
function preloadDirectory(string $directory, array &$loaded = []): void
{
    if (!is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();
            
            // Avoid duplicate includes
            if (!isset($loaded[$path])) {
                $loaded[$path] = true;
                opcache_compile_file($path);
            }
        }
    }
}

// Core framework classes (highest priority)
$corePath = $basePath . '/core';
preloadDirectory($corePath);

// Application classes
$appPath = $basePath . '/app';
preloadDirectory($appPath);

// Bootstrap files
$bootstrapPath = $basePath . '/bootstrap';
if (is_dir($bootstrapPath)) {
    foreach (glob($bootstrapPath . '/*.php') as $file) {
        if (is_file($file)) {
            opcache_compile_file($file);
        }
    }
}

// Vendor dependencies that benefit from preloading
$vendorAutoload = $basePath . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    // Only preload commonly used vendor classes
    $vendorClasses = [
        // FastRoute - routing
        'vendor/nikic/fast-route/src/autoload.php' => false, // Skip, compiled at runtime
        
        // PHP-DI - dependency injection
        'vendor/php-di/php-di/src/Container.php' => true,
        
        // Symfony components
        'vendor/symfony/cache/Simple/AbstractCache.php' => false,
        'vendor/symfony/http-foundation/Response.php' => true,
        'vendor/symfony/http-foundation/Request.php' => true,
        
        // Monolog
        'vendor/monolog/monolog/src/Monolog/Logger.php' => false,
        
        // Illuminate
        'vendor/illuminate/view/Factory.php' => false,
        'vendor/illuminate/view/Compilers/BladeCompiler.php' => false,
        'vendor/illuminate/database/DatabaseManager.php' => false,
        
        // Nyholm PSR-7
        'vendor/nyholm/psr7/src/Response.php' => true,
        'vendor/nyholm/psr7/src/Request.php' => true,
    ];
    
    foreach ($vendorClasses as $classPath => $preload) {
        if ($preload) {
            $fullPath = $basePath . '/' . $classPath;
            if (file_exists($fullPath)) {
                opcache_compile_file($fullPath);
            }
        }
    }
}

// Preload commonly used helper functions
$helpersPath = $basePath . '/core/Support/helpers.php';
if (file_exists($helpersPath)) {
    opcache_compile_file($helpersPath);
}

// Return stats for debugging
if (function_exists('opcache_get_status') && ini_get('opcache.enable')) {
    $status = opcache_get_status(false);
    if ($status && isset($status['opcache_statistics'])) {
        error_log('OPcache Preload: ' . ($status['opcache_statistics']['num_cached_scripts'] ?? 0) . ' scripts cached');
    }
}
