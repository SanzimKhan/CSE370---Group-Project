<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/analytics.php';

echo "=== Analytics System Test ===\n\n";

try {
    $pdo = db();
    echo "✓ Database connection successful\n\n";

    
    echo "Checking analytics tables...\n";
    
    $tables = ['Analytics_Activity', 'Gig_Views', 'User_Earnings'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table $table exists\n";
            
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  - Records: {$result['count']}\n";
        } else {
            echo "✗ Table $table does NOT exist\n";
        }
    }
    
    echo "\n";

    
    echo "Testing Analytics class...\n";
    $analytics = new Analytics($pdo);
    echo "✓ Analytics class instantiated\n\n";

    
    $stmt = $pdo->query("SELECT BRACU_ID FROM User LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $test_bracu_id = $user['BRACU_ID'];
        echo "Testing with user: $test_bracu_id\n\n";

        
        echo "Testing logActivity()...\n";
        $result = $analytics->logActivity($test_bracu_id, 'login');
        if ($result) {
            echo "✓ Activity logged successfully\n";
        } else {
            echo "✗ Failed to log activity\n";
        }

        
        echo "\nTesting getUserAnalytics()...\n";
        $user_analytics = $analytics->getUserAnalytics($test_bracu_id);
        echo "✓ User analytics retrieved\n";
        echo "  - Total logins: {$user_analytics['total_logins']}\n";
        echo "  - Total gig views: {$user_analytics['total_gig_views']}\n";
        echo "  - Gigs created: {$user_analytics['gigs_created']}\n";
        echo "  - Gigs applied: {$user_analytics['gigs_applied']}\n";
        echo "  - Total earnings: ৳{$user_analytics['total_earnings']}\n";
        echo "  - Pending earnings: ৳{$user_analytics['pending_earnings']}\n";
        echo "  - Completion rate: {$user_analytics['completion_rate']}%\n";
        echo "  - Last activity: " . ($user_analytics['last_activity'] ?? 'None') . "\n";

        if (!empty($user_analytics['activity_breakdown'])) {
            echo "  - Activity breakdown:\n";
            foreach ($user_analytics['activity_breakdown'] as $type => $count) {
                echo "    * $type: $count\n";
            }
        }

        
        $stmt = $pdo->query("SELECT GID FROM Gigs LIMIT 1");
        $gig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($gig) {
            $test_gig_id = $gig['GID'];
            echo "\nTesting with gig: $test_gig_id\n";

            
            echo "\nTesting logGigView()...\n";
            $result = $analytics->logGigView($test_gig_id, $test_bracu_id);
            if ($result) {
                echo "✓ Gig view logged successfully\n";
            } else {
                echo "✗ Failed to log gig view\n";
            }

            
            echo "\nTesting getGigViewsCount()...\n";
            $views = $analytics->getGigViewsCount($test_gig_id);
            echo "✓ Gig views count: $views\n";

            
            echo "\nTesting getGigUniqueViewersCount()...\n";
            $unique_viewers = $analytics->getGigUniqueViewersCount($test_gig_id);
            echo "✓ Unique viewers count: $unique_viewers\n";

            
            echo "\nTesting getGigAnalytics()...\n";
            $gig_analytics = $analytics->getGigAnalytics($test_gig_id);
            echo "✓ Gig analytics retrieved\n";
            echo "  - Views: {$gig_analytics['views']}\n";
            echo "  - Unique viewers: {$gig_analytics['unique_viewers']}\n";
            echo "  - Applications: {$gig_analytics['applications']}\n";
            echo "  - Status: " . ($gig_analytics['completion_status'] ?? 'N/A') . "\n";
            echo "  - Earned amount: ৳{$gig_analytics['earned_amount']}\n";
        } else {
            echo "\n⚠ No gigs found in database for testing\n";
        }

        
        echo "\nTesting getTrendingGigs()...\n";
        $trending = $analytics->getTrendingGigs(5, 30);
        echo "✓ Trending gigs retrieved: " . count($trending) . " gigs\n";
        if (!empty($trending)) {
            foreach ($trending as $gig) {
                echo "  - Gig #{$gig['GID']}: {$gig['TITLE']} ({$gig['view_count']} views)\n";
            }
        }

        
        echo "\nTesting getUserActivityHistory()...\n";
        $history = $analytics->getUserActivityHistory($test_bracu_id, 10);
        echo "✓ Activity history retrieved: " . count($history) . " activities\n";
        if (!empty($history)) {
            foreach (array_slice($history, 0, 5) as $activity) {
                echo "  - {$activity['activity_type']} at {$activity['created_at']}\n";
            }
        }

    } else {
        echo "⚠ No users found in database for testing\n";
    }

    echo "\n=== All Tests Completed ===\n";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
