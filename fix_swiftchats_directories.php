<?php

/**
 * Swiftchats Directory Fix
 * 
 * Creates only the essential directories that Swiftchats installer needs
 * to prevent "mkdir(): File exists" errors during installation.
 */

echo "Creating Swiftchats installation directories...\n";

$projectRoot = __DIR__;

// Only the essential directories that Swiftchats installer typically creates
$directories = [
    // Core Swiftchats directories
    'Swiftchats',
    'Swiftchats/app',
    'Swiftchats/app/Http',
    'Swiftchats/app/Http/Controllers',
    'Swiftchats/app/Models',
    'Swiftchats/app/Services',
    'Swiftchats/resources',
    'Swiftchats/resources/views',
    'Swiftchats/resources/js',
    'Swiftchats/database',
    'Swiftchats/database/migrations',
    'Swiftchats/config',
    'Swiftchats/routes',
    'Swiftchats/storage',
    'Swiftchats/public',
    
    // Common Laravel directories that might conflict
    'app/Http/Controllers/Swiftchats',
    'app/Models/Swiftchats',
    'app/Services/Swiftchats',
    'resources/views/swiftchats',
    'resources/js/swiftchats',
    'database/migrations/swiftchats',
    'config/swiftchats',
    'routes/swiftchats',
];

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($directories as $dir) {
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . $dir;
    
    if (!file_exists($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "✓ Created: {$dir}\n";
            $created++;
        } else {
            echo "✗ Error creating: {$dir}\n";
            $errors++;
        }
    } else {
        echo "- Skipped (exists): {$dir}\n";
        $skipped++;
    }
}

echo "\n";
echo "Summary:\n";
echo "- Created: {$created} directories\n";
echo "- Skipped: {$skipped} directories (already exist)\n";
echo "- Errors: {$errors} directories\n";
echo "\n";
echo "Swiftchats directory preparation completed!\n";
echo "You can now run the Swiftchats installer without mkdir errors.\n"; 