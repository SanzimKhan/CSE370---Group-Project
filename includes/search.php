<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

class Search
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Index a gig for full-text search
     */
    public function indexGig(int $gig_id, string $title = '', string $description = '', string $category = ''): bool
    {
        // Get gig details if not provided
        if (empty($title) || empty($description)) {
            $query = "SELECT LIST_OF_GIGS as description, CATAGORY FROM Gigs WHERE GID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('i', $gig_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $gig = $result->fetch_assoc();

            if (!$gig) {
                return false;
            }

            if (empty($description)) {
                $description = $gig['description'];
            }
            if (empty($category)) {
                $category = $gig['CATAGORY'];
            }
        }

        // Build search keywords
        $keywords = $this->generateSearchKeywords($title, $description, $category);

        // Insert or update index
        $query = "INSERT INTO Gig_Search_Index (GID, search_keywords)
                  VALUES (?, ?)
                  ON DUPLICATE KEY UPDATE search_keywords = ?, updated_at = NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iss', $gig_id, $keywords, $keywords);

        return $stmt->execute();
    }

    /**
     * Search gigs using full-text search
     */
    public function searchGigs(string $query, string $category = '', int $limit = 20, int $offset = 0): array
    {
        $search_query = "SELECT DISTINCT g.*, u.full_name, COUNT(gv.id) as view_count
                        FROM Gigs g
                        JOIN User u ON g.BRACU_ID = u.BRACU_ID
                        LEFT JOIN Gig_Views gv ON g.GID = gv.GID
                        JOIN Gig_Search_Index gsi ON g.GID = gsi.GID
                        WHERE MATCH(gsi.search_keywords) AGAINST(? IN BOOLEAN MODE)";

        if (!empty($category)) {
            $search_query .= " AND g.CATAGORY = ?";
        }

        $search_query .= " AND g.STATUS = 'listed'
                         GROUP BY g.GID
                         ORDER BY MATCH(gsi.search_keywords) AGAINST(? IN BOOLEAN MODE) DESC, g.created_at DESC
                         LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($search_query);

        if (!empty($category)) {
            $stmt->bind_param('sssii', $query, $category, $query, $limit, $offset);
        } else {
            $stmt->bind_param('ssii', $query, $query, $limit, $offset);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Advanced search with filters
     */
    public function advancedSearch(
        ?string $keyword = null,
        ?string $category = null,
        ?string $sort_by = 'recent',
        int $min_credits = 0,
        int $max_credits = 999999,
        int $limit = 20,
        int $offset = 0
    ): array {
        $query = "SELECT g.*, u.full_name, COUNT(gv.id) as view_count
                 FROM Gigs g
                 JOIN User u ON g.BRACU_ID = u.BRACU_ID
                 LEFT JOIN Gig_Views gv ON g.GID = gv.GID
                 WHERE g.STATUS = 'listed' AND g.CREDIT_AMOUNT BETWEEN ? AND ?";

        if ($keyword) {
            $query .= " AND (g.LIST_OF_GIGS LIKE ? OR g.CATAGORY LIKE ?)";
        }

        if ($category) {
            $query .= " AND g.CATAGORY = ?";
        }

        $query .= " GROUP BY g.GID";

        // Sort options
        switch ($sort_by) {
            case 'price_high':
                $query .= " ORDER BY g.CREDIT_AMOUNT DESC";
                break;
            case 'price_low':
                $query .= " ORDER BY g.CREDIT_AMOUNT ASC";
                break;
            case 'popular':
                $query .= " ORDER BY view_count DESC";
                break;
            case 'deadline':
                $query .= " ORDER BY g.DEADLINE ASC";
                break;
            default: // recent
                $query .= " ORDER BY g.created_at DESC";
        }

        $query .= " LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        if ($keyword && $category) {
            $keyword_search = "%$keyword%";
            $stmt->bind_param('iissii', $min_credits, $max_credits, $keyword_search, $keyword_search, $category, $limit, $offset);
        } elseif ($keyword) {
            $keyword_search = "%$keyword%";
            $stmt->bind_param('iissii', $min_credits, $max_credits, $keyword_search, $keyword_search, $limit, $offset);
        } elseif ($category) {
            $stmt->bind_param('iisi', $min_credits, $max_credits, $category, $limit, $offset);
        } else {
            $stmt->bind_param('iiii', $min_credits, $max_credits, $limit, $offset);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Suggest search keywords based on partial input
     */
    public function getSearchSuggestions(string $partial_query, int $limit = 10): array
    {
        $search_term = "$partial_query%";
        $query = "SELECT DISTINCT 
                    SUBSTRING_INDEX(search_keywords, ' ', 1) as suggestion
                  FROM Gig_Search_Index
                  WHERE search_keywords LIKE ?
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('si', $search_term, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Search users by name or bio
     */
    public function searchUsers(string $query, int $limit = 20, int $offset = 0): array
    {
        $search_query = "%$query%";
        $query_str = "SELECT u.*, 
                           COUNT(DISTINCT r.id) as total_ratings,
                           AVG(r.rating) as avg_rating
                     FROM User u
                     LEFT JOIN Ratings r ON u.BRACU_ID = r.ratee_id
                     WHERE (u.full_name LIKE ? OR u.bio LIKE ?)
                     AND u.BRACU_ID != ?
                     GROUP BY u.BRACU_ID
                     ORDER BY total_ratings DESC, u.full_name ASC
                     LIMIT ? OFFSET ?";

        $current_user = $_SESSION['user_id'] ?? '';
        $stmt = $this->conn->prepare($query_str);
        $stmt->bind_param('sssii', $search_query, $search_query, $current_user, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Remove gig from search index (when gig is deleted or unlisted)
     */
    public function removeFromIndex(int $gig_id): bool
    {
        $query = "DELETE FROM Gig_Search_Index WHERE GID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $gig_id);

        return $stmt->execute();
    }

    /**
     * Reindex all gigs (admin task)
     */
    public function reindexAllGigs(): int
    {
        $count = 0;

        // First clear the index
        $this->conn->query("DELETE FROM Gig_Search_Index");

        // Get all active gigs
        $query = "SELECT GID, LIST_OF_GIGS, CATAGORY FROM Gigs WHERE STATUS = 'listed'";
        $result = $this->conn->query($query);

        if ($result) {
            while ($gig = $result->fetch_assoc()) {
                if ($this->indexGig($gig['GID'], '', $gig['LIST_OF_GIGS'], $gig['CATAGORY'])) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Generate search keywords from gig content
     */
    private function generateSearchKeywords(string $title, string $description, string $category): string
    {
        // Extract key terms from description
        $keywords = [];

        // Add category
        if (!empty($category)) {
            $keywords[] = $category;
        }

        // Add title
        if (!empty($title)) {
            $keywords[] = $title;
        }

        // Extract common words from description (remove common stop words)
        $stop_words = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = str_word_count(strtolower($description), 1);
        $filtered_words = array_filter($words, function ($word) use ($stop_words) {
            return !in_array($word, $stop_words) && strlen($word) > 3;
        });

        $keywords = array_merge($keywords, array_slice(array_unique($filtered_words), 0, 10));

        return implode(' ', $keywords);
    }
}
