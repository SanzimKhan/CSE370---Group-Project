<?php
declare(strict_types=1);

class Analytics
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Log user activity
     */
    public function logActivity(
        string $bracu_id,
        string $activity_type,
        ?int $gig_id = null,
        ?string $target_user = null,
        ?array $activity_data = null
    ): bool {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $data_json = $activity_data ? json_encode($activity_data) : null;

        $query = "INSERT INTO Analytics_Activity
                  (BRACU_ID, activity_type, gig_id, target_user, activity_data, ip_address, user_agent)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([
                $bracu_id,
                $activity_type,
                $gig_id,
                $target_user,
                $data_json,
                $ip_address,
                $user_agent
            ]);
        } catch (PDOException $e) {
            error_log("Analytics error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log gig view
     */
    public function logGigView(int $gig_id, ?string $bracu_id = null): bool
    {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $query = "INSERT INTO Gig_Views (GID, BRACU_ID, viewer_ip) VALUES (?, ?, ?)";

        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$gig_id, $bracu_id, $ip_address]);
        } catch (PDOException $e) {
            error_log("Gig view logging error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get gig views count
     */
    public function getGigViewsCount(int $gig_id): int
    {
        $query = "SELECT COUNT(*) as count FROM Gig_Views WHERE GID = ?";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$gig_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($row['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Get gig views error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get unique viewers count for a gig
     */
    public function getGigUniqueViewersCount(int $gig_id): int
    {
        $query = "SELECT COUNT(DISTINCT BRACU_ID) as count FROM Gig_Views WHERE GID = ? AND BRACU_ID IS NOT NULL";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$gig_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($row['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Get unique viewers error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user analytics dashboard
     */
    public function getUserAnalytics(string $bracu_id): array
    {
        $analytics = [
            'total_logins' => 0,
            'total_gig_views' => 0,
            'gigs_created' => 0,
            'gigs_applied' => 0,
            'total_earnings' => 0,
            'pending_earnings' => 0,
            'completion_rate' => 0,
            'last_activity' => null,
            'activity_breakdown' => []
        ];

        try {
            // Total logins
            $query = "SELECT COUNT(*) as count FROM Analytics_Activity
                      WHERE BRACU_ID = ? AND activity_type = 'login'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['total_logins'] = (int) ($result['count'] ?? 0);

            // Total gig views (views on gigs created by this user)
            $query = "SELECT COUNT(DISTINCT gv.id) as count FROM Gig_Views gv
                      INNER JOIN Gigs g ON gv.GID = g.GID
                      WHERE g.BRACU_ID = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['total_gig_views'] = (int) ($result['count'] ?? 0);

            // Gigs created
            $query = "SELECT COUNT(*) as count FROM Gigs WHERE BRACU_ID = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['gigs_created'] = (int) ($result['count'] ?? 0);

            // Gigs applied to
            $query = "SELECT COUNT(*) as count FROM Working_on WHERE BRACU_ID = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['gigs_applied'] = (int) ($result['count'] ?? 0);

            // Total earnings
            $query = "SELECT COALESCE(SUM(amount), 0) as total FROM User_Earnings WHERE BRACU_ID = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['total_earnings'] = (float) ($result['total'] ?? 0);

            // Pending earnings
            $query = "SELECT COALESCE(SUM(amount), 0) as total FROM User_Earnings 
                      WHERE BRACU_ID = ? AND status = 'pending'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['pending_earnings'] = (float) ($result['total'] ?? 0);

            // Completion rate (based on gigs applied to)
            if ($analytics['gigs_applied'] > 0) {
                $query = "SELECT COUNT(*) as count FROM Working_on 
                          WHERE BRACU_ID = ? AND done_at IS NOT NULL";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$bracu_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $completed = (int) ($result['count'] ?? 0);
                $analytics['completion_rate'] = round(($completed / $analytics['gigs_applied']) * 100, 2);
            }

            // Last activity
            $query = "SELECT created_at FROM Analytics_Activity 
                      WHERE BRACU_ID = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['last_activity'] = $result['created_at'] ?? null;

            // Activity breakdown
            $query = "SELECT activity_type, COUNT(*) as count FROM Analytics_Activity 
                      WHERE BRACU_ID = ? GROUP BY activity_type";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($activities as $activity) {
                $analytics['activity_breakdown'][$activity['activity_type']] = (int) $activity['count'];
            }

        } catch (PDOException $e) {
            error_log("User analytics error: " . $e->getMessage());
        }

        return $analytics;
    }

    /**
     * Get gig analytics
     */
    public function getGigAnalytics(int $gig_id): array
    {
        $analytics = [
            'views' => 0,
            'unique_viewers' => 0,
            'applications' => 0,
            'completion_status' => null,
            'earned_amount' => 0
        ];

        try {
            // Views
            $query = "SELECT COUNT(*) as count FROM Gig_Views WHERE GID = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$gig_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['views'] = (int) ($result['count'] ?? 0);

            // Unique viewers
            $query = "SELECT COUNT(DISTINCT BRACU_ID) as count FROM Gig_Views 
                      WHERE GID = ? AND BRACU_ID IS NOT NULL";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$gig_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['unique_viewers'] = (int) ($result['count'] ?? 0);

            // Applications
            $query = "SELECT COUNT(*) as count FROM Working_on WHERE GID = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$gig_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['applications'] = (int) ($result['count'] ?? 0);

            // Completion status
            $query = "SELECT STATUS FROM Gigs WHERE GID = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$gig_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['completion_status'] = $result['STATUS'] ?? null;

            // Earned amount
            $query = "SELECT COALESCE(SUM(amount), 0) as total FROM User_Earnings WHERE gig_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$gig_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['earned_amount'] = (float) ($result['total'] ?? 0);

        } catch (PDOException $e) {
            error_log("Gig analytics error: " . $e->getMessage());
        }

        return $analytics;
    }

    /**
     * Get trending gigs based on views
     */
    public function getTrendingGigs(int $limit = 10, int $days = 7): array
    {
        $query = "SELECT g.GID, g.TITLE, g.CATAGORY, COUNT(gv.id) as view_count
                  FROM Gigs g
                  INNER JOIN Gig_Views gv ON g.GID = gv.GID
                  WHERE gv.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY g.GID, g.TITLE, g.CATAGORY
                  ORDER BY view_count DESC
                  LIMIT ?";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$days, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get trending gigs error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user activity history
     */
    public function getUserActivityHistory(string $bracu_id, int $limit = 50): array
    {
        $query = "SELECT activity_type, gig_id, target_user, activity_data, created_at
                  FROM Analytics_Activity
                  WHERE BRACU_ID = ?
                  ORDER BY created_at DESC
                  LIMIT ?";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$bracu_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user activity history error: " . $e->getMessage());
            return [];
        }
    }
}
