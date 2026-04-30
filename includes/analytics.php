<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

class Analytics
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
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

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            'ssiisss',
            $bracu_id,
            $activity_type,
            $gig_id,
            $target_user,
            $data_json,
            $ip_address,
            $user_agent
        );

        return $stmt->execute();
    }

    /**
     * Log gig view
     */
    public function logGigView(int $gig_id, ?string $bracu_id = null): bool
    {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $query = "INSERT INTO Gig_Views (GID, BRACU_ID, viewer_ip) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iss', $gig_id, $bracu_id, $ip_address);

        return $stmt->execute();
    }

    /**
     * Get gig views count
     */
    public function getGigViewsCount(int $gig_id): int
    {
        $query = "SELECT COUNT(*) as count FROM Gig_Views WHERE GID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $gig_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int) $row['count'];
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

        // Total logins
        $query = "SELECT COUNT(*) as count FROM Analytics_Activity 
                  WHERE BRACU_ID = ? AND activity_type = 'login'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['total_logins'] = (int) $result->fetch_assoc()['count'];

        // Total gig views (for their gigs)
        $query = "SELECT COUNT(DISTINCT gv.id) as count FROM Gig_Views gv
                  JOIN Gigs g ON gv.GID = g.GID
                  WHERE g.BRACU_ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['total_gig_views'] = (int) $result->fetch_assoc()['count'];

        // Gigs created
        $query = "SELECT COUNT(*) as count FROM Gigs WHERE BRACU_ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['gigs_created'] = (int) $result->fetch_assoc()['count'];

        // Gigs applied to
        $query = "SELECT COUNT(*) as count FROM Working_on WHERE BRACU_ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['gigs_applied'] = (int) $result->fetch_assoc()['count'];

        // Total earnings
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM User_Earnings WHERE BRACU_ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['total_earnings'] = (float) $result->fetch_assoc()['total'];

        // Pending earnings
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM User_Earnings 
                  WHERE BRACU_ID = ? AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['pending_earnings'] = (float) $result->fetch_assoc()['total'];

        // Completion rate
        if ($analytics['gigs_applied'] > 0) {
            $query = "SELECT COUNT(*) as count FROM Working_on 
                      WHERE BRACU_ID = ? AND done_at IS NOT NULL";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('s', $bracu_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $completed = (int) $result->fetch_assoc()['count'];
            $analytics['completion_rate'] = round(($completed / $analytics['gigs_applied']) * 100, 2);
        }

        // Last activity
        $query = "SELECT created_at FROM Analytics_Activity 
                  WHERE BRACU_ID = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $analytics['last_activity'] = $row ? $row['created_at'] : null;

        // Activity breakdown
        $query = "SELECT activity_type, COUNT(*) as count FROM Analytics_Activity 
                  WHERE BRACU_ID = ? GROUP BY activity_type";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $bracu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['activity_breakdown'][$row['activity_type']] = (int) $row['count'];
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

        // Views
        $query = "SELECT COUNT(*) as count FROM Gig_Views WHERE GID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $gig_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['views'] = (int) $result->fetch_assoc()['count'];

        // Unique viewers
        $query = "SELECT COUNT(DISTINCT BRACU_ID) as count FROM Gig_Views WHERE GID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $gig_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['unique_viewers'] = (int) $result->fetch_assoc()['count'];

        // Applications
        $query = "SELECT COUNT(*) as count FROM Working_on WHERE GID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $gig_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['applications'] = (int) $result->fetch_assoc()['count'];

        // Completion status
        $query = "SELECT STATUS FROM Gigs WHERE GID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $gig_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $analytics['completion_status'] = $row ? $row['STATUS'] : null;

        // Earned amount
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM User_Earnings WHERE gig_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $gig_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['earned_amount'] = (float) $result->fetch_assoc()['total'];

        return $analytics;
    }
}
