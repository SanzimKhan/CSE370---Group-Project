<?php
/**
 * Database Bloat Executor
 * This file executes the bloat_database.php script and outputs results
 */

declare(strict_types=1);

// Output as plain text for the frontend to display
header('Content-Type: text/plain; charset=utf-8');

try {
    // Start output buffering to capture echoes
    ob_start();
    
    // Include the bloat database script
    require_once __DIR__ . '/database/bloat_database.php';
    
    // Get the output
    $output = ob_get_clean();
    
    // Output the captured content
    echo $output;
    
} catch (Exception $e) {
    echo "Error during database bloat:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
