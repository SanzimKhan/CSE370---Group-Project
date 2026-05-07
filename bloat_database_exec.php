<?php





declare(strict_types=1);


header('Content-Type: text/plain; charset=utf-8');

try {
    
    ob_start();
    
    
    require_once __DIR__ . '/database/bloat_database.php';
    
    
    $output = ob_get_clean();
    
    
    echo $output;
    
} catch (Exception $e) {
    echo "Error during database bloat:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
