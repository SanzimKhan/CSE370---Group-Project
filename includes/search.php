<?php
declare(strict_types=1);

class Search
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    


    public function indexGig(int $gig_id, string $title = '', string $description = '', string $category = ''): bool
    {
        try {
            
            if (empty($title) || empty($description)) {
                $query = "SELECT LIST_OF_GIGS as description, CATAGORY FROM Gigs WHERE GID = ?";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$gig_id]);
                $gig = $stmt->fetch(PDO::FETCH_ASSOC);

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

            
            $keywords = $this->generateSearchKeywords($title, $description, $category);

            
            $query = "INSERT INTO Gig_Search_Index (GID, search_keywords)
                      VALUES (?, ?)
                      ON DUPLICATE KEY UPDATE search_keywords = ?, updated_at = NOW()";

            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$gig_id, $keywords, $keywords]);
        } catch (PDOException $e) {
            error_log("Index gig error: " . $e->getMessage());
            return false;
        }
    }

    


    public function searchGigs(string $query, string $category = '', int $limit = 20, int $offset = 0): array
    {
        try {
            $search_query = "SELECT DISTINCT g.*, u.full_name, COUNT(gv.id) as view_count
                            FROM Gigs g
                            JOIN User u ON g.BRACU_ID = u.BRACU_ID
                            LEFT JOIN Gig_Views gv ON g.GID = gv.GID
                            JOIN Gig_Search_Index gsi ON g.GID = gsi.GID
                            WHERE MATCH(gsi.search_keywords) AGAINST(? IN BOOLEAN MODE)";

            $params = [$query];

            if (!empty($category)) {
                $search_query .= " AND g.CATAGORY = ?";
                $params[] = $category;
            }

            $search_query .= " AND g.STATUS = 'listed'
                             GROUP BY g.GID
                             ORDER BY MATCH(gsi.search_keywords) AGAINST(? IN BOOLEAN MODE) DESC, g.created_at DESC
                             LIMIT ? OFFSET ?";

            $params[] = $query;
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($search_query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search gigs error: " . $e->getMessage());
            return [];
        }
    }

    


    public function advancedSearch(
        ?string $keyword = null,
        ?string $category = null,
        ?string $sort_by = 'recent',
        int $min_credits = 0,
        int $max_credits = 999999,
        int $limit = 20,
        int $offset = 0
    ): array {
        try {
            $query = "SELECT g.*, u.full_name, COUNT(gv.id) as view_count
                     FROM Gigs g
                     JOIN User u ON g.BRACU_ID = u.BRACU_ID
                     LEFT JOIN Gig_Views gv ON g.GID = gv.GID
                     WHERE g.STATUS = 'listed' AND g.CREDIT_AMOUNT BETWEEN ? AND ?";

            $params = [$min_credits, $max_credits];

            if ($keyword) {
                $query .= " AND (g.LIST_OF_GIGS LIKE ? OR g.skill_tags LIKE ? OR g.CATAGORY LIKE ?)";
                $keyword_search = "%$keyword%";
                $params[] = $keyword_search;
                $params[] = $keyword_search;
                $params[] = $keyword_search;
            }

            if ($category) {
                $query .= " AND g.CATAGORY = ?";
                $params[] = $category;
            }

            $query .= " GROUP BY g.GID";

            
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
                default: 
                    $query .= " ORDER BY g.created_at DESC";
            }

            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Advanced search error: " . $e->getMessage());
            return [];
        }
    }

    


    public function getSearchSuggestions(string $partial_query, int $limit = 10): array
    {
        try {
            $search_term = "$partial_query%";
            $query = "SELECT DISTINCT
                        SUBSTRING_INDEX(search_keywords, ' ', 1) as suggestion
                      FROM Gig_Search_Index
                      WHERE search_keywords LIKE ?
                      LIMIT ?";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$search_term, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search suggestions error: " . $e->getMessage());
            return [];
        }
    }

    


    public function searchUsers(string $query, ?string $current_user = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $search_query = "%$query%";
            $query_str = "SELECT u.*,
                               COUNT(DISTINCT r.id) as total_ratings,
                               AVG(r.rating) as avg_rating
                         FROM User u
                         LEFT JOIN Ratings r ON u.BRACU_ID = r.ratee_id
                     WHERE (u.full_name LIKE ? OR u.bio LIKE ? OR u.skills LIKE ?)";

            $params = [$search_query, $search_query, $search_query];

            if ($current_user) {
                $query_str .= " AND u.BRACU_ID != ?";
                $params[] = $current_user;
            }

            $query_str .= " GROUP BY u.BRACU_ID
                         ORDER BY total_ratings DESC, u.full_name ASC
                         LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($query_str);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }

    


    public function removeFromIndex(int $gig_id): bool
    {
        try {
            $query = "DELETE FROM Gig_Search_Index WHERE GID = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$gig_id]);
        } catch (PDOException $e) {
            error_log("Remove from index error: " . $e->getMessage());
            return false;
        }
    }

    


    public function reindexAllGigs(): int
    {
        try {
            $count = 0;

            
            $this->pdo->exec("DELETE FROM Gig_Search_Index");

            
            $query = "SELECT GID, LIST_OF_GIGS, CATAGORY FROM Gigs WHERE STATUS = 'listed'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            while ($gig = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($this->indexGig($gig['GID'], '', $gig['LIST_OF_GIGS'], $gig['CATAGORY'])) {
                    $count++;
                }
            }

            return $count;
        } catch (PDOException $e) {
            error_log("Reindex all gigs error: " . $e->getMessage());
            return 0;
        }
    }

    


    private function generateSearchKeywords(string $title, string $description, string $category): string
    {
        
        $keywords = [];

        
        if (!empty($category)) {
            $keywords[] = $category;
        }

        
        if (!empty($title)) {
            $keywords[] = $title;
        }

        
        $stop_words = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = str_word_count(strtolower($description), 1);
        $filtered_words = array_filter($words, function ($word) use ($stop_words) {
            return !in_array($word, $stop_words) && strlen($word) > 3;
        });

        $keywords = array_merge($keywords, array_slice(array_unique($filtered_words), 0, 10));

        return implode(' ', $keywords);
    }
}
