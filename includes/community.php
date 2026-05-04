<?php
declare(strict_types=1);

class Community
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
        try {
            if ($rating < 1 || $rating > 5) {
                return false;
            }

            $query = "INSERT INTO Ratings (rater_id, ratee_id, gig_id, rating, review_text, is_client_rating)
                      VALUES (?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE rating = ?, review_text = ?, updated_at = NOW()";

            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$rater_id, $ratee_id, $gig_id, $rating, $review_text, $is_client_rating, $rating, $review_text]);
        } catch (PDOException $e) {
            error_log("Create rating error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user ratings/reviews
     */
    public function getUserRatings(string $bracu_id): array
    {
        try {
            $query = "SELECT r.*, u.full_name, u.avatar_path FROM Ratings r
                      JOIN User u ON r.rater_id = u.BRACU_ID
                      WHERE r.ratee_id = ?
                      ORDER BY r.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user ratings error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user rating average
     */
    public function getUserRatingAverage(string $bracu_id): ?array
    {
        try {
            $query = "SELECT
                        COUNT(*) as total_ratings,
                        AVG(rating) as avg_rating,
                        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                      FROM Ratings WHERE ratee_id = ?";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get rating average error: " . $e->getMessage());
            return null;
        }
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
        try {
            $query = "INSERT INTO Messages (sender_id, recipient_id, gig_id, message_text)
                      VALUES (?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$sender_id, $recipient_id, $gig_id, $message_text]);
        } catch (PDOException $e) {
            error_log("Send message error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get messages between two users
     */
    public function getConversation(string $user1_id, string $user2_id, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = "SELECT * FROM Messages
                      WHERE (sender_id = ? AND recipient_id = ?)
                         OR (sender_id = ? AND recipient_id = ?)
                      ORDER BY created_at DESC
                      LIMIT ? OFFSET ?";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get conversation error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's message conversations
     */
    public function getUserConversations(string $bracu_id): array
    {
        try {
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

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id, $bracu_id, $bracu_id, $bracu_id, $bracu_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get conversations error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(string $recipient_id, string $sender_id): bool
    {
        try {
            $query = "UPDATE Messages
                      SET is_read = 1, read_at = NOW()
                      WHERE sender_id = ? AND recipient_id = ? AND is_read = 0";

            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$sender_id, $recipient_id]);
        } catch (PDOException $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread message count
     */
    public function getUnreadMessageCount(string $bracu_id): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM Messages
                      WHERE recipient_id = ? AND is_read = 0";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
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
        try {
            $query = "INSERT INTO Forum_Threads (creator_id, title, description, category)
                      VALUES (?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($query);
            if ($stmt->execute([$creator_id, $title, $description, $category])) {
                return (int) $this->pdo->lastInsertId();
            }
            return 0;
        } catch (PDOException $e) {
            error_log("Create forum thread error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get forum threads
     */
    public function getForumThreads(string $category = '', int $limit = 20, int $offset = 0): array
    {
        try {
            $query = "SELECT ft.*, u.full_name, u.avatar_path FROM Forum_Threads ft
                      JOIN User u ON ft.creator_id = u.BRACU_ID";

            $params = [];

            if ($category !== '') {
                $query .= " WHERE ft.category = ?";
                $params[] = $category;
            }

            $query .= " ORDER BY ft.is_pinned DESC, ft.updated_at DESC
                       LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get forum threads error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get forum thread with replies
     */
    public function getForumThreadWithReplies(int $thread_id): array
    {
        try {
            $thread_query = "SELECT ft.*, u.full_name, u.avatar_path FROM Forum_Threads ft
                            JOIN User u ON ft.creator_id = u.BRACU_ID
                            WHERE ft.id = ?";

            $stmt = $this->pdo->prepare($thread_query);
            $stmt->execute([$thread_id]);
            $thread = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$thread) {
                return [];
            }

            // Update view count
            $update_query = "UPDATE Forum_Threads SET view_count = view_count + 1 WHERE id = ?";
            $update_stmt = $this->pdo->prepare($update_query);
            $update_stmt->execute([$thread_id]);

            // Get replies
            $replies_query = "SELECT fr.*, u.full_name, u.avatar_path FROM Forum_Replies fr
                             JOIN User u ON fr.author_id = u.BRACU_ID
                             WHERE fr.thread_id = ?
                             ORDER BY fr.created_at ASC";

            $replies_stmt = $this->pdo->prepare($replies_query);
            $replies_stmt->execute([$thread_id]);
            $replies = $replies_stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'thread' => $thread,
                'replies' => $replies
            ];
        } catch (PDOException $e) {
            error_log("Get forum thread error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add reply to forum thread
     */
    public function addForumReply(int $thread_id, string $author_id, string $reply_text): bool
    {
        try {
            $query = "INSERT INTO Forum_Replies (thread_id, author_id, reply_text)
                      VALUES (?, ?, ?)";

            $stmt = $this->pdo->prepare($query);
            if ($stmt->execute([$thread_id, $author_id, $reply_text])) {
                // Update reply count
                $update_query = "UPDATE Forum_Threads SET reply_count = reply_count + 1, updated_at = NOW()
                               WHERE id = ?";
                $update_stmt = $this->pdo->prepare($update_query);
                $update_stmt->execute([$thread_id]);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Add forum reply error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Award badge to user
     */
    public function awardBadge(string $bracu_id, string $badge_type, string $badge_name, ?string $description = null): bool
    {
        try {
            $query = "INSERT INTO User_Badges (BRACU_ID, badge_type, badge_name, badge_description)
                      VALUES (?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE badge_name = ?, badge_description = ?";

            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$bracu_id, $badge_type, $badge_name, $description, $badge_name, $description]);
        } catch (PDOException $e) {
            error_log("Award badge error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user badges
     */
    public function getUserBadges(string $bracu_id): array
    {
        try {
            $query = "SELECT * FROM User_Badges WHERE BRACU_ID = ? ORDER BY earned_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user badges error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check and award achievement badges
     */
    public function checkAndAwardBadges(string $bracu_id): void
    {
        try {
            // Get user stats
            $ratings = $this->getUserRatingAverage($bracu_id);
            $earnings = $this->getUserEarnings($bracu_id);

            // Top Rated Badge (avg rating >= 4.5)
            if ($ratings && isset($ratings['avg_rating']) && $ratings['avg_rating'] >= 4.5 && $ratings['total_ratings'] >= 5) {
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
        } catch (Exception $e) {
            error_log("Check badges error: " . $e->getMessage());
        }
    }

    /**
     * Helper: Get user earnings
     */
    private function getUserEarnings(string $bracu_id): float
    {
        try {
            $query = "SELECT COALESCE(SUM(amount), 0) as total FROM User_Earnings WHERE BRACU_ID = ? AND status = 'released'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            return (float) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Get earnings error: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Helper: Get completed jobs count
     */
    private function getCompletedJobsCount(string $bracu_id): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM Working_on WHERE BRACU_ID = ? AND done_at IS NOT NULL";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Get completed jobs error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Helper: Check if user is responsive
     */
    private function isResponsive(string $bracu_id): bool
    {
        try {
            $query = "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, read_at)) as avg_response_time
                      FROM Messages
                      WHERE recipient_id = ? AND read_at IS NOT NULL
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // If average response time is less than 24 hours (86400 seconds)
            return $result && isset($result['avg_response_time']) && $result['avg_response_time'] && $result['avg_response_time'] < 86400;
        } catch (PDOException $e) {
            error_log("Check responsive error: " . $e->getMessage());
            return false;
        }
    }
}
