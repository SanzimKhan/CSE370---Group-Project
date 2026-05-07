<?php





























declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

set_time_limit(300); 

$password_hash = '$2y$10$3UiOi2izYE1XFr1ufoMe4eV2rYqnmFV9q2KA0NA98eqBEQNtAulQK'; 

try {
    echo "Starting database bloat process...\n";
    
    
    
    
    
    echo "Creating users...\n";
    $userIds = createUsers($password_hash);
    
    
    echo "Creating gigs...\n";
    $gigIds = createGigs($userIds);
    
    
    echo "Creating working_on assignments...\n";
    createWorkingOn($gigIds, $userIds);
    
    
    echo "Creating analytics activities...\n";
    createAnalyticsActivities($userIds, $gigIds);
    
    
    echo "Creating gig views...\n";
    createGigViews($gigIds, $userIds);
    
    
    echo "Creating user earnings...\n";
    createUserEarnings($gigIds, $userIds);
    
    
    echo "Creating ratings...\n";
    createRatings($userIds, $gigIds);
    
    
    echo "Creating badges...\n";
    createBadges($userIds);
    
    
    echo "Creating messages...\n";
    createMessages($userIds, $gigIds);
    
    
    echo "Creating forum threads...\n";
    $threadIds = createForumThreads($userIds);
    
    
    echo "Creating forum replies...\n";
    createForumReplies($threadIds, $userIds);
    
    
    echo "Creating gig search index...\n";
    createGigSearchIndex($gigIds);
    
    
    echo "Creating transaction ledger entries...\n";
    createTransactionLedger($userIds, $gigIds);
    
    
    echo "Creating user points...\n";
    createUserPoints($userIds);
    
    
    echo "Creating points activities...\n";
    createPointsActivities($userIds, $gigIds);
    
    
    echo "Creating transaction batches...\n";
    createTransactionBatches($userIds);
    
    
    echo "Creating transaction disputes...\n";
    createTransactionDisputes($userIds, $gigIds);
    
    echo "\n✓ Database bloat process completed successfully!\n";
    echo "\n" . getCredentialsInfo() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}




function getCredentialsInfo(): string
{
    $info = <<<EOT
===============================================
DUMMY USER CREDENTIALS
===============================================

ADMIN USER:
  BRACU ID: 20101000
  Email: 20101000@g.bracu.ac.bd
  Password: password123

FREELANCER USERS:
  BRACU IDs: 20101001 - 20101050
  Email: 201010XX@g.bracu.ac.bd (replace XX with your ID)
  Password: password123 (for all)

CLIENT USERS:
  BRACU IDs: 20101051 - 20101100
  Email: 201010XX@g.bracu.ac.bd (replace XX with your ID)
  Password: password123 (for all)

MIXED ROLE USERS:
  BRACU IDs: 20101101 - 20101150
  Email: 201010XX@g.bracu.ac.bd (replace XX with your ID)
  Password: password123 (for all)

===============================================
ALL DUMMY USERS USE PASSWORD: password123
===============================================
EOT;

    return $info;
}




function clearAllData(): void
{
    $tables = [
        'Transaction_Disputes',
        'Transaction_Batch',
        'Points_Activity',
        'User_Points',
        'Transaction_Ledger',
        'Gig_Search_Index',
        'Forum_Replies',
        'Forum_Threads',
        'Messages',
        'User_Badges',
        'Ratings',
        'User_Earnings',
        'Gig_Views',
        'Analytics_Activity',
        'Working_on',
        'Gigs',
        'User'
    ];
    
    $conn = db();
    foreach ($tables as $table) {
        try {
            $conn->exec("TRUNCATE TABLE `$table`");
            echo "  Cleared $table\n";
        } catch (Exception $e) {
            echo "  Could not clear $table: " . $e->getMessage() . "\n";
        }
    }
}






function createUsers(string $password_hash): array
{
    $conn = db();
    $userIds = [];
    
    
    $bracu_id = '20101000';
    $email = '20101000@g.bracu.ac.bd';
    $conn->prepare('
        INSERT INTO `User` (BRACU_ID, Bracu_mail, full_name, client, mobile_number, password, freelancer, preferred_mode, is_admin, credit_balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ')->execute([
        $bracu_id, $email, 'Admin User', 1, '01700000000', $password_hash, 1, 'hiring', 1, 10000.00
    ]);
    $userIds[] = $bracu_id;
    
    
    for ($i = 1; $i <= 50; $i++) {
        $bracu_id = sprintf('2010100%d', $i);
        $email = sprintf('2010100%d@g.bracu.ac.bd', $i);
        $full_name = "Freelancer User $i";
        $mobile = sprintf('0170000000%d', $i % 10);
        
        try {
            $conn->prepare('
                INSERT INTO `User` (BRACU_ID, Bracu_mail, full_name, client, mobile_number, password, freelancer, preferred_mode, is_admin, credit_balance)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $bracu_id, $email, $full_name, 0, $mobile, $password_hash, 1, 'working', 0, rand(100, 5000)
            ]);
            $userIds[] = $bracu_id;
        } catch (Exception $e) {
            
        }
    }
    
    
    for ($i = 51; $i <= 100; $i++) {
        $bracu_id = sprintf('2010100%d', $i);
        $email = sprintf('2010100%d@g.bracu.ac.bd', $i);
        $full_name = "Client User " . ($i - 50);
        $mobile = sprintf('0170000000%d', ($i - 50) % 10);
        
        try {
            $conn->prepare('
                INSERT INTO `User` (BRACU_ID, Bracu_mail, full_name, client, mobile_number, password, freelancer, preferred_mode, is_admin, credit_balance)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $bracu_id, $email, $full_name, 1, $mobile, $password_hash, 0, 'hiring', 0, rand(500, 10000)
            ]);
            $userIds[] = $bracu_id;
        } catch (Exception $e) {
            
        }
    }
    
    
    for ($i = 101; $i <= 150; $i++) {
        $bracu_id = sprintf('2010100%d', $i);
        $email = sprintf('2010100%d@g.bracu.ac.bd', $i);
        $full_name = "Mixed Role User " . ($i - 100);
        $mobile = sprintf('0170000000%d', ($i - 100) % 10);
        
        try {
            $conn->prepare('
                INSERT INTO `User` (BRACU_ID, Bracu_mail, full_name, client, mobile_number, password, freelancer, preferred_mode, is_admin, credit_balance)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $bracu_id, $email, $full_name, 1, $mobile, $password_hash, 1, (rand(0, 1) ? 'hiring' : 'working'), 0, rand(1000, 8000)
            ]);
            $userIds[] = $bracu_id;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created " . count($userIds) . " users\n";
    return $userIds;
}







function createGigs(array $userIds): array
{
    $conn = db();
    $gigIds = [];
    
    $categories = ['IT', 'Writing', 'Others'];
    $statuses = ['listed', 'pending', 'done'];
    
    $gig_descriptions = [
        'Build a website for my startup',
        'Write SEO-optimized blog posts',
        'Design a logo and branding',
        'Develop mobile app',
        'Create marketing strategy',
        'Edit and proofread documents',
        'Perform data entry',
        'Create social media content',
        'Develop REST API',
        'Database optimization',
        'Code review and testing',
        'UI/UX design improvements',
        'Technical documentation',
        'Video editing and production',
        'Graphic design for print',
    ];
    
    
    for ($i = 0; $i < 300; $i++) {
        $creator_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        $category = $categories[rand(0, count($categories) - 1)];
        $status = $statuses[rand(0, count($statuses) - 1)];
        $credit_amount = rand(50, 5000) / 2; 
        $description = $gig_descriptions[rand(0, count($gig_descriptions) - 1)] . " #" . ($i + 1);
        $deadline = date('Y-m-d', strtotime('+' . rand(7, 90) . ' days'));
        
        try {
            $stmt = $conn->prepare('
                INSERT INTO `Gigs` (BRACU_ID, CREDIT_AMOUNT, LIST_OF_GIGS, CATAGORY, DEADLINE, STATUS)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$creator_bracu_id, $credit_amount, $description, $category, $deadline, $status]);
            $gigIds[] = $conn->lastInsertId();
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created " . count($gigIds) . " gigs\n";
    return $gigIds;
}







function createWorkingOn(array $gigIds, array $userIds): void
{
    $conn = db();
    $count = 0;
    
    
    $assignment_count = (int) (count($gigIds) * 0.3);
    
    for ($i = 0; $i < $assignment_count; $i++) {
        $gigIndex = rand(0, count($gigIds) - 1);
        $gig_id = $gigIds[$gigIndex];
        
        
        $gig = $conn->query("SELECT CREDIT_AMOUNT FROM `Gigs` WHERE GID = $gig_id")->fetch();
        if (!$gig) continue;
        
        $freelancer_bracu_id = $userIds[rand(1, count($userIds) - 1)]; 
        $credit = $gig['CREDIT_AMOUNT'];
        $done_at = (rand(0, 1) ? date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')) : null);
        $payment_released = ($done_at ? rand(0, 1) : 0);
        
        try {
            $conn->prepare('
                INSERT INTO `Working_on` (BRACU_ID, GID, credit, done_at, payment_released)
                VALUES (?, ?, ?, ?, ?)
            ')->execute([$freelancer_bracu_id, $gig_id, $credit, $done_at, $payment_released]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count working_on assignments\n";
}







function createAnalyticsActivities(array $userIds, array $gigIds): void
{
    $conn = db();
    $count = 0;
    
    $activity_types = ['login', 'gig_view', 'gig_create', 'gig_apply', 'profile_view', 'message_send'];
    $ip_addresses = ['192.168.1.1', '192.168.1.2', '10.0.0.1', '172.16.0.1', '127.0.0.1'];
    
    
    for ($i = 0; $i < 1000; $i++) {
        $user_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        $activity_type = $activity_types[rand(0, count($activity_types) - 1)];
        $gig_id = ($activity_type !== 'login' && $activity_type !== 'profile_view') ? $gigIds[rand(0, count($gigIds) - 1)] : null;
        $target_user = (in_array($activity_type, ['profile_view', 'message_send'])) ? $userIds[rand(0, count($userIds) - 1)] : null;
        $ip_address = $ip_addresses[rand(0, count($ip_addresses) - 1)];
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
        
        try {
            $conn->prepare('
                INSERT INTO `Analytics_Activity` (BRACU_ID, activity_type, gig_id, target_user, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $user_bracu_id,
                $activity_type,
                $gig_id,
                $target_user,
                $ip_address,
                'Mozilla/5.0 (Test User Agent)',
                $created_at
            ]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count analytics activities\n";
}







function createGigViews(array $gigIds, array $userIds): void
{
    $conn = db();
    $count = 0;
    
    $ip_addresses = ['192.168.1.1', '192.168.1.2', '10.0.0.1', '172.16.0.1', '127.0.0.1'];
    
    
    for ($i = 0; $i < 2000; $i++) {
        $gig_id = $gigIds[rand(0, count($gigIds) - 1)];
        $user_bracu_id = (rand(0, 1) ? $userIds[rand(0, count($userIds) - 1)] : null);
        $ip_address = $ip_addresses[rand(0, count($ip_addresses) - 1)];
        $viewed_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
        
        try {
            $conn->prepare('
                INSERT INTO `Gig_Views` (GID, BRACU_ID, viewer_ip, viewed_at)
                VALUES (?, ?, ?, ?)
            ')->execute([$gig_id, $user_bracu_id, $ip_address, $viewed_at]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count gig views\n";
}







function createUserEarnings(array $gigIds, array $userIds): void
{
    $conn = db();
    $count = 0;
    
    $statuses = ['pending', 'released', 'refunded'];
    
    
    $earnings_count = (int) (count($gigIds) * 0.3);
    
    for ($i = 0; $i < $earnings_count; $i++) {
        $gig_index = rand(0, count($gigIds) - 1);
        $gig_id = $gigIds[$gig_index];
        
        
        $gig = $conn->query("SELECT CREDIT_AMOUNT FROM `Gigs` WHERE GID = $gig_id")->fetch();
        if (!$gig) continue;
        
        $freelancer_bracu_id = $userIds[rand(1, count($userIds) - 1)];
        $amount = $gig['CREDIT_AMOUNT'];
        $status = $statuses[rand(0, count($statuses) - 1)];
        $earned_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'));
        $released_at = ($status === 'released') ? date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days')) : null;
        
        try {
            $conn->prepare('
                INSERT INTO `User_Earnings` (BRACU_ID, gig_id, amount, status, earned_at, released_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ')->execute([$freelancer_bracu_id, $gig_id, $amount, $status, $earned_at, $released_at]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count user earnings\n";
}







function createRatings(array $userIds, array $gigIds): void
{
    $conn = db();
    $count = 0;
    
    $reviews = [
        'Excellent work! Very professional.',
        'Great job. Will hire again.',
        'Good quality, fast delivery.',
        'Satisfied with the results.',
        'Perfect work as described.',
        'Good communication.',
        'Well done!',
        'Highly recommended.',
        'Fast and reliable.',
        'Great experience overall.',
    ];
    
    
    for ($i = 0; $i < 300; $i++) {
        $rater_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        $ratee_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        
        
        if ($rater_bracu_id === $ratee_bracu_id) continue;
        
        $gig_id = (rand(0, 1) ? $gigIds[rand(0, count($gigIds) - 1)] : null);
        $rating = rand(1, 5);
        $review_text = $reviews[rand(0, count($reviews) - 1)];
        $is_client_rating = rand(0, 1);
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
        
        try {
            $conn->prepare('
                INSERT INTO `Ratings` (rater_id, ratee_id, gig_id, rating, review_text, is_client_rating, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $rater_bracu_id,
                $ratee_bracu_id,
                $gig_id,
                $rating,
                $review_text,
                $is_client_rating,
                $created_at
            ]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count ratings\n";
}






function createBadges(array $userIds): void
{
    $conn = db();
    $count = 0;
    
    $badge_types = ['verified', 'top_rated', 'responsive', 'trusted', 'new_member'];
    $badge_names = [
        'verified' => 'Verified User',
        'top_rated' => 'Top Rated Seller',
        'responsive' => 'Responsive',
        'trusted' => 'Trusted',
        'new_member' => 'New Member'
    ];
    $badge_descriptions = [
        'verified' => 'User has verified their identity',
        'top_rated' => 'User has excellent ratings from clients',
        'responsive' => 'User responds quickly to messages',
        'trusted' => 'User has completed many successful transactions',
        'new_member' => 'Recently joined the platform'
    ];
    
    
    foreach ($userIds as $user_bracu_id) {
        $num_badges = rand(1, 3);
        $selected_badges = array_rand($badge_types, min($num_badges, count($badge_types)));
        
        if (!is_array($selected_badges)) {
            $selected_badges = [$selected_badges];
        }
        
        foreach ($selected_badges as $badge_type_index) {
            $badge_type = $badge_types[$badge_type_index];
            $earned_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 180) . ' days'));
            
            try {
                $conn->prepare('
                    INSERT INTO `User_Badges` (BRACU_ID, badge_type, badge_name, badge_description, earned_at)
                    VALUES (?, ?, ?, ?, ?)
                ')->execute([
                    $user_bracu_id,
                    $badge_type,
                    $badge_names[$badge_type],
                    $badge_descriptions[$badge_type],
                    $earned_at
                ]);
                $count++;
            } catch (Exception $e) {
                
            }
        }
    }
    
    echo "  Created $count badges\n";
}







function createMessages(array $userIds, array $gigIds): void
{
    $conn = db();
    $count = 0;
    
    $message_templates = [
        'Hi! Are you still available for this gig?',
        'Great work on the last project!',
        'Can we discuss the project requirements?',
        'When can you start?',
        'Thank you for completing the work.',
        'I have some questions about the deadline.',
        'The quality looks great!',
        'Need any clarification on the requirements?',
        'Looking forward to working with you!',
        'Please provide an update on progress.',
    ];
    
    
    for ($i = 0; $i < 500; $i++) {
        $sender_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        $recipient_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        
        
        if ($sender_bracu_id === $recipient_bracu_id) continue;
        
        $gig_id = (rand(0, 1) ? $gigIds[rand(0, count($gigIds) - 1)] : null);
        $message_text = $message_templates[rand(0, count($message_templates) - 1)];
        $is_read = rand(0, 1);
        $read_at = ($is_read ? date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')) : null);
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'));
        
        try {
            $conn->prepare('
                INSERT INTO `Messages` (sender_id, recipient_id, gig_id, message_text, is_read, read_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $sender_bracu_id,
                $recipient_bracu_id,
                $gig_id,
                $message_text,
                $is_read,
                $read_at,
                $created_at
            ]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count messages\n";
}







function createForumThreads(array $userIds): array
{
    $conn = db();
    $threadIds = [];
    
    $categories = ['General', 'Tips', 'Help', 'Showcase'];
    $thread_titles = [
        'How to get more gigs?',
        'Best practices for freelancers',
        'Pricing strategy discussion',
        'Tips for writing good gig descriptions',
        'How to communicate effectively with clients?',
        'Dealing with difficult clients',
        'Portfolio tips and tricks',
        'Time management for freelancers',
        'Quality control best practices',
        'Getting your first review',
        'How to improve gig visibility?',
        'Setting realistic deadlines',
        'Payment security concerns',
        'Marketing yourself effectively',
        'Building client relationships',
    ];
    
    
    for ($i = 0; $i < 100; $i++) {
        $creator_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        $title = $thread_titles[rand(0, count($thread_titles) - 1)] . ' #' . ($i + 1);
        $description = 'This is a discussion thread about ' . strtolower($title) . '. Feel free to share your thoughts and experiences.';
        $category = $categories[rand(0, count($categories) - 1)];
        $is_pinned = (rand(0, 1) && rand(0, 1));
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 180) . ' days'));
        
        try {
            $stmt = $conn->prepare('
                INSERT INTO `Forum_Threads` (creator_id, title, description, category, is_pinned, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $creator_bracu_id,
                $title,
                $description,
                $category,
                $is_pinned,
                $created_at
            ]);
            $threadIds[] = $conn->lastInsertId();
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created " . count($threadIds) . " forum threads\n";
    return $threadIds;
}







function createForumReplies(array $threadIds, array $userIds): void
{
    $conn = db();
    $count = 0;
    
    $reply_templates = [
        'Great tip! I will definitely try this approach.',
        'Thanks for sharing your experience.',
        'I agree with your point here.',
        'This helped me a lot.',
        'Could you elaborate more on this?',
        'Any specific tools you recommend?',
        'I have similar experiences.',
        'Well said! Very helpful.',
        'This is exactly what I needed.',
        'Thanks for the detailed explanation.',
    ];
    
    
    for ($i = 0; $i < 500; $i++) {
        $thread_id = $threadIds[rand(0, count($threadIds) - 1)];
        $author_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        $reply_text = $reply_templates[rand(0, count($reply_templates) - 1)];
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 180) . ' days'));
        
        try {
            $conn->prepare('
                INSERT INTO `Forum_Replies` (thread_id, author_id, reply_text, created_at)
                VALUES (?, ?, ?, ?)
            ')->execute([
                $thread_id,
                $author_bracu_id,
                $reply_text,
                $created_at
            ]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count forum replies\n";
}






function createGigSearchIndex(array $gigIds): void
{
    $conn = db();
    $count = 0;
    
    $keywords_pool = [
        'website development php javascript',
        'blog writing content creation seo',
        'logo design branding graphics',
        'mobile app development android ios',
        'data entry excel spreadsheet',
        'video editing production',
        'social media marketing content',
        'rest api backend development',
        'database optimization mysql',
        'code review testing quality',
        'ui ux design interface',
        'technical documentation writing',
        'graphic design illustration',
        'email marketing campaign',
        'seo optimization ranking'
    ];
    
    
    foreach ($gigIds as $gig_id) {
        $keywords = $keywords_pool[rand(0, count($keywords_pool) - 1)];
        
        try {
            $conn->prepare('
                INSERT INTO `Gig_Search_Index` (GID, search_keywords)
                VALUES (?, ?)
            ')->execute([$gig_id, $keywords]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count gig search index entries\n";
}







function createTransactionLedger(array $userIds, array $gigIds): void
{
    $conn = db();
    $count = 0;
    
    
    try {
        $result = $conn->query("SHOW TABLES LIKE 'Transaction_Ledger'")->fetch();
        if (!$result) {
            echo "  ⚠️  Transaction_Ledger table not found (virtual economy migration not applied). Skipping.\n";
            return;
        }
    } catch (Exception $e) {
        echo "  ⚠️  Could not check Transaction_Ledger table. Skipping.\n";
        return;
    }
    
    $transaction_types = ['gig_payment', 'points_redemption', 'refund', 'bonus', 'withdrawal'];
    $statuses = ['pending', 'completed', 'failed', 'reversed'];
    
    
    for ($i = 0; $i < 400; $i++) {
        $from_user = $userIds[rand(0, count($userIds) - 1)];
        $to_user = $userIds[rand(0, count($userIds) - 1)];
        
        
        if ($from_user === $to_user) continue;
        
        $transaction_id = 'TXN' . time() . rand(1000, 9999);
        $transaction_type = $transaction_types[rand(0, count($transaction_types) - 1)];
        $amount = rand(50, 5000) / 2;
        $points_transferred = rand(0, 500);
        $gig_id = (rand(0, 1) ? $gigIds[rand(0, count($gigIds) - 1)] : null);
        $status = $statuses[rand(0, count($statuses) - 1)];
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
        $completed_at = ($status !== 'pending' ? date('Y-m-d H:i:s', strtotime('-' . rand(0, 60) . ' days')) : null);
        
        try {
            $conn->prepare('
                INSERT INTO `Transaction_Ledger` (transaction_id, from_user, to_user, transaction_type, amount, points_transferred, gig_id, status, created_at, completed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $transaction_id,
                $from_user,
                $to_user,
                $transaction_type,
                $amount,
                $points_transferred,
                $gig_id,
                $status,
                $created_at,
                $completed_at
            ]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count transaction ledger entries\n";
}






function createUserPoints(array $userIds): void
{
    $conn = db();
    $count = 0;
    
    
    try {
        $result = $conn->query("SHOW TABLES LIKE 'User_Points'")->fetch();
        if (!$result) {
            echo "  ⚠️  User_Points table not found (virtual economy migration not applied). Skipping.\n";
            return;
        }
    } catch (Exception $e) {
        echo "  ⚠️  Could not check User_Points table. Skipping.\n";
        return;
    }
    
    $tiers = ['bronze', 'silver', 'gold', 'platinum'];
    
    
    foreach ($userIds as $user_bracu_id) {
        $total_points = rand(0, 5000);
        $available_points = rand(0, $total_points);
        $points_redeemed = rand(0, 2000);
        $lifetime_points = $total_points + $points_redeemed;
        $points_tier = $tiers[rand(0, count($tiers) - 1)];
        
        try {
            $conn->prepare('
                INSERT INTO `User_Points` (BRACU_ID, total_points, available_points, points_redeemed, lifetime_points, points_tier)
                VALUES (?, ?, ?, ?, ?, ?)
            ')->execute([
                $user_bracu_id,
                $total_points,
                $available_points,
                $points_redeemed,
                $lifetime_points,
                $points_tier
            ]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count user points records\n";
}







function createPointsActivities(array $userIds, array $gigIds): void
{
    $conn = db();
    $count = 0;
    
    
    try {
        $result = $conn->query("SHOW TABLES LIKE 'Points_Activity'")->fetch();
        if (!$result) {
            echo "  ⚠️  Points_Activity table not found (virtual economy migration not applied). Skipping.\n";
            return;
        }
    } catch (Exception $e) {
        echo "  ⚠️  Could not check Points_Activity table. Skipping.\n";
        return;
    }
    
    $activity_types = ['earned', 'redeemed', 'bonus', 'expired'];
    
    
    for ($i = 0; $i < 300; $i++) {
        $user_bracu_id = $userIds[rand(0, count($userIds) - 1)];
        $activity_type = $activity_types[rand(0, count($activity_types) - 1)];
        $points_amount = rand(10, 500);
        $related_gig = (rand(0, 1) ? $gigIds[rand(0, count($gigIds) - 1)] : null);
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 180) . ' days'));
        
        try {
            $conn->prepare('
                INSERT INTO `Points_Activity` (BRACU_ID, activity_type, points_amount, related_gig, created_at)
                VALUES (?, ?, ?, ?, ?)
            ')->execute([
                $user_bracu_id,
                $activity_type,
                $points_amount,
                $related_gig,
                $created_at
            ]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count points activities\n";
}






function createTransactionBatches(array $userIds): void
{
    $conn = db();
    $count = 0;
    
    
    try {
        $result = $conn->query("SHOW TABLES LIKE 'Transaction_Batch'")->fetch();
        if (!$result) {
            echo "  ⚠️  Transaction_Batch table not found (virtual economy migration not applied). Skipping.\n";
            return;
        }
    } catch (Exception $e) {
        echo "  ⚠️  Could not check Transaction_Batch table. Skipping.\n";
        return;
    }
    
    $batch_types = ['daily_settlements', 'points_conversion', 'refund_batch', 'bonus_distribution'];
    $statuses = ['pending', 'processing', 'completed', 'failed'];
    
    
    for ($i = 0; $i < 50; $i++) {
        $batch_id = 'BATCH' . time() . rand(1000, 9999);
        $batch_type = $batch_types[rand(0, count($batch_types) - 1)];
        $total_transactions = rand(10, 100);
        $successful_transactions = rand(0, $total_transactions);
        $failed_transactions = $total_transactions - $successful_transactions;
        $total_amount = rand(1000, 50000) / 2;
        $status = $statuses[rand(0, count($statuses) - 1)];
        $initiated_by = (rand(0, 1) ? $userIds[0] : null); 
        $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
        $started_at = ($status !== 'pending' ? date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days')) : null);
        $completed_at = ($status === 'completed' ? date('Y-m-d H:i:s', strtotime('-' . rand(0, 60) . ' days')) : null);
        
        try {
            $conn->prepare('
                INSERT INTO `Transaction_Batch` (batch_id, batch_type, total_transactions, successful_transactions, failed_transactions, total_amount, status, initiated_by, created_at, started_at, completed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $batch_id,
                $batch_type,
                $total_transactions,
                $successful_transactions,
                $failed_transactions,
                $total_amount,
                $status,
                $initiated_by,
                $created_at,
                $started_at,
                $completed_at
            ]);
            $count++;
        } catch (Exception $e) {
            
        }
    }
    
    echo "  Created $count transaction batches\n";
}







function createTransactionDisputes(array $userIds, array $gigIds): void
{
    $conn = db();
    $count = 0;
    
    
    try {
        $result = $conn->query("SHOW TABLES LIKE 'Transaction_Disputes'")->fetch();
        if (!$result) {
            echo "  ⚠️  Transaction_Disputes table not found (virtual economy migration not applied). Skipping.\n";
            return;
        }
        
        $result = $conn->query("SHOW TABLES LIKE 'Transaction_Ledger'")->fetch();
        if (!$result) {
            echo "  ⚠️  Transaction_Ledger table not found (virtual economy migration not applied). Skipping.\n";
            return;
        }
    } catch (Exception $e) {
        echo "  ⚠️  Could not check Transaction_Disputes tables. Skipping.\n";
        return;
    }
    
    $dispute_reasons = ['payment_error', 'work_not_completed', 'quality_issue', 'duplicate_charge', 'unauthorized', 'other'];
    $statuses = ['open', 'under_review', 'resolved', 'closed'];
    $resolution_types = ['refund', 'partial_refund', 'accepted', 'rejected'];
    
    
    $transactions = $conn->query("SELECT transaction_id FROM `Transaction_Ledger` LIMIT 50")->fetchAll();
    
    if (!empty($transactions)) {
        
        foreach ($transactions as $transaction) {
            $dispute_id = 'DISP' . time() . rand(1000, 9999);
            $transaction_id = $transaction['transaction_id'];
            $complainant_id = $userIds[rand(0, count($userIds) - 1)];
            $respondent_id = $userIds[rand(0, count($userIds) - 1)];
            
            
            if ($complainant_id === $respondent_id) continue;
            
            $gig_id = (rand(0, 1) ? $gigIds[rand(0, count($gigIds) - 1)] : null);
            $dispute_reason = $dispute_reasons[rand(0, count($dispute_reasons) - 1)];
            $dispute_description = 'I have a dispute regarding this transaction. Please review the details.';
            $status = $statuses[rand(0, count($statuses) - 1)];
            $resolution_type = ($status !== 'open' ? $resolution_types[rand(0, count($resolution_types) - 1)] : null);
            $refund_amount = ($resolution_type && in_array($resolution_type, ['refund', 'partial_refund']) ? rand(50, 5000) / 2 : null);
            $resolved_by = ($status === 'resolved' || $status === 'closed' ? $userIds[0] : null); 
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
            $resolved_at = ($status === 'resolved' || $status === 'closed' ? date('Y-m-d H:i:s', strtotime('-' . rand(0, 60) . ' days')) : null);
            
            try {
                $conn->prepare('
                    INSERT INTO `Transaction_Disputes` (dispute_id, transaction_id, complainant_id, respondent_id, gig_id, dispute_reason, dispute_description, status, resolution_type, refund_amount, resolved_by, created_at, resolved_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ')->execute([
                    $dispute_id,
                    $transaction_id,
                    $complainant_id,
                    $respondent_id,
                    $gig_id,
                    $dispute_reason,
                    $dispute_description,
                    $status,
                    $resolution_type,
                    $refund_amount,
                    $resolved_by,
                    $created_at,
                    $resolved_at
                ]);
                $count++;
            } catch (Exception $e) {
                
            }
        }
    }
    
    echo "  Created $count transaction disputes\n";
}
