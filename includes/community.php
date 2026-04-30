<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

class Community
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Create a rating/review
     */
    public function createRating(
        string $rater_id,
        string $ratee_id,
        int $rating,
        ?string $review_text = null,
        ?int $gig_id = null,
        bool $is_client_rating = false
    ): bool {
        if ($rating < 1 || $rating > 5) {
            return false;
        }

        $query = "INSERT INTO Ratings (rater_id, ratee_id, gig_id, rating, review_text, is_client_rating)
                  VALUES (?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE rating = ?, review_text = ?, updated_at = NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssisisiss', $rater_id, $ratee_id, $gig_id, $rating, $review_text, $is_client_rating, $rating, $review_text);

        return $stmt->execute();
    }

    /**
     * Get user ratings/reviews
     */
    public function getUserRatings(string $bracu_id): array
    {
        $query = "SELECT r.*, u.full_name, u.avatar_path FROM Ratings r
                  JOIN User u ON r.rater_id = u.BRACU_ID
                  WHERE r.ratee_id = ?
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get user rating average
     */
    public function getUserRatingAverage(string $bracu_id): array
    {
        $query = "SELECT 
                    COUNT(*) as total_ratings,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                  FROM Ratings WHERE ratee_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Send a message
     */
    public function sendMessage(
        string $sender_id,
        string $recipient_id,
        string $message_text,
        ?int $gig_id = null
    ): bool {
        $query = "INSERT INTO Messages (sender_id, recipient_id, gig_id, message_text)
                  VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssss', $sender_id, $recipient_id, $gig_id, $message_text);

        return $stmt->execute();
    }

    /**
     * Get messages between two users
     */
    public function getConversation(string $user1_id, string $user2_id, int $limit = 50, int $offset = 0): array
    {
        $query = "SELECT * FROM Messages 
                  WHERE (sender_id = ? AND recipient_id = ?) 
                     OR (sender_id = ? AND recipient_id = ?)
                  ORDER BY created_at DESC
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssssii', $user1_id, $user2_id, $user2_id, $user1_id, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get user's message conversations
     */
    public function getUserConversations(string $bracu_id): array
    {
        $query = "SELECT 
                    m.id,
                    CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END as contact_id,
                    u.full_name,
                    u.avatar_path,
                    m.message_text as last_message,
                    m.created_at as last_message_time,
                    m.is_read,
                    COUNT(CASE WHEN m.recipient_id = ? AND m.is_read = 0 THEN 1 END) as unread_count
                  FROM Messages m
                  JOIN User u ON (
                    CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END
                  ) = u.BRACU_ID
                  WHERE m.sender_id = ? OR m.recipient_id = ?
                  GROUP BY contact_id
                  ORDER BY m.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sssss', $bracu_id, $bracu_id, $bracu_id, $bracu_id, $bracu_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(string $recipient_id, string $sender_id): bool
    {
        $query = "UPDATE Messages 
                  SET is_read = 1, read_at = NOW()
                  WHERE sender_id = ? AND recipient_id = ? AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $sender_id, $recipient_id);

        return $stmt->execute();
    }

    /**
     * Get unread message count
     */
    public function getUnreadMessageCount(string $bracu_id): int
    {
        $query = "SELECT COUNT(*) as count FROM Messages 
                  WHERE recipient_id = ? AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return (int) $result->fetch_assoc()['count'];
    }

    /**
     * Create forum thread
     */
    public function createForumThread(
        string $creator_id,
        string $title,
        string $description,
        string $category = 'General'
    ): int {
        $query = "INSERT INTO Forum_Threads (creator_id, title, description, category)
                  VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssss', $creator_id, $title, $description, $category);

        if ($stmt->execute()) {
            return (int) $this->conn->insert_id;
        }
        return 0;
    }

    /**
     * Get forum threads
     */
    public function getForumThreads(string $category = '', int $limit = 20, int $offset = 0): array
    {
        $query = "SELECT ft.*, u.full_name, u.avatar_path FROM Forum_Threads ft
                  JOIN User u ON ft.creator_id = u.BRACU_ID";

        if ($category !== '') {
            $query .= " WHERE ft.category = ?";
        }

        $query .= " ORDER BY ft.is_pinned DESC, ft.updated_at DESC
                   LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        if ($category !== '') {
            $stmt->bind_param('sii', $category, $limit, $offset);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get forum thread with replies
     */
    public function getForumThreadWithReplies(int $thread_id): array
    {
        $thread_query = "SELECT ft.*, u.full_name, u.avatar_path FROM Forum_Threads ft
                        JOIN User u ON ft.creator_id = u.BRACU_ID
                        WHERE ft.id = ?";

        $stmt = $this->conn->prepare($thread_query);
        $stmt->bind_param('i', $thread_id);
        $stmt->execute();
        $thread = $stmt->get_result()->fetch_assoc();

        if (!$thread) {
            return [];
        }

        // Update view count
        $update_query = "UPDATE Forum_Threads SET view_count = view_count + 1 WHERE id = ?";
        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bind_param('i', $thread_id);
        $update_stmt->execute();

        // Get replies
        $replies_query = "SELECT fr.*, u.full_name, u.avatar_path FROM Forum_Replies fr
                         JOIN User u ON fr.author_id = u.BRACU_ID
                         WHERE fr.thread_id = ?
                         ORDER BY fr.created_at ASC";

        $replies_stmt = $this->conn->prepare($replies_query);
        $replies_stmt->bind_param('i', $thread_id);
        $replies_stmt->execute();
        $replies = $replies_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'thread' => $thread,
            'replies' => $replies
        ];
    }

    /**
     * Add reply to forum thread
     */
    public function addForumReply(int $thread_id, string $author_id, string $reply_text): bool
    {
        $query = "INSERT INTO Forum_Replies (thread_id, author_id, reply_text)
                  VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iss', $thread_id, $author_id, $reply_text);

        if ($stmt->execute()) {
            // Update reply count
            $update_query = "UPDATE Forum_Threads SET reply_count = reply_count + 1, updated_at = NOW()
                           WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bind_param('i', $thread_id);
            $update_stmt->execute();
            return true;
        }
        return false;
    }

    /**
     * Award badge to user
     */
    public function awardBadge(string $bracu_id, string $badge_type, string $badge_name, ?string $description = null): bool
    {
        $query = "INSERT INTO User_Badges (BRACU_ID, badge_type, badge_name, badge_description)
                  VALUES (?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE badge_name = ?, badge_description = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssssss', $bracu_id, $badge_type, $badge_name, $description, $badge_name, $description);

        return $stmt->execute();
    }

    /**
     * Get user badges
     */
    public function getUserBadges(string $bracu_id): array
    {
        $query = "SELECT * FROM User_Badges WHERE BRACU_ID = ? ORDER BY earned_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Check and award achievement badges
     */
    public function checkAndAwardBadges(string $bracu_id): void
    {
        // Get user stats
        $ratings = $this->getUserRatingAverage($bracu_id);
        $earnings = $this->getUserEarnings($bracu_id);

        // Top Rated Badge (avg rating >= 4.5)
        if ($ratings && $ratings['avg_rating'] >= 4.5 && $ratings['total_ratings'] >= 5) {
            $this->awardBadge($bracu_id, 'top_rated', 'Top Rated', 'Earned with an average rating of 4.5+ stars');
        }

        // Trusted Badge (5+ completed jobs)
        $completed_jobs = $this->getCompletedJobsCount($bracu_id);
        if ($completed_jobs >= 5) {
            $this->awardBadge($bracu_id, 'trusted', 'Trusted', 'Completed 5 or more jobs');
        }

        // Responsive Badge (fast response time)
        if ($this->isResponsive($bracu_id)) {
            $this->awardBadge($bracu_id, 'responsive', 'Responsive', 'Consistently responds quickly to messages');
        }
    }

    /**
     * Helper: Get user earnings
     */
    private function getUserEarnings(string $bracu_id): float
    {
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM User_Earnings WHERE BRACU_ID = ? AND status = 'released'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return (float) $result->fetch_assoc()['total'];
    }

    /**
     * Helper: Get completed jobs count
     */
    private function getCompletedJobsCount(string $bracu_id): int
    {
        $query = "SELECT COUNT(*) as count FROM Working_on WHERE BRACU_ID = ? AND done_at IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return (int) $result->fetch_assoc()['count'];
    }

    /**
     * Helper: Check if user is responsive
     */
    private function isResponsive(string $bracu_id): bool
    {
        $query = "SELECT AVG(TIME_TO_SEC(read_at) - TIME_TO_SEC(created_at)) as avg_response_time
                  FROM Messages 
                  WHERE recipient_id = ? AND read_at IS NOT NULL
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // If average response time is less than 24 hours (86400 seconds)
        return $result && $result['avg_response_time'] && $result['avg_response_time'] < 86400;
    }
}
