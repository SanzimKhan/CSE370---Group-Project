<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/search.php';

// Check authentication
if (!isset($_SESSION['user_bracu_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = db();
$search = new Search($pdo);

$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'recent';
$min_price = (int) ($_GET['min'] ?? 0);
$max_price = (int) ($_GET['max'] ?? 999999);

$results = [];
$search_performed = false;

if ($query || $category) {
    $search_performed = true;
    $results = $search->advancedSearch($query, $category, $sort, $min_price, $max_price);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Gigs - BRACU Freelance Marketplace</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .search-header {
            margin-bottom: 30px;
        }

        .search-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .search-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }

        .search-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .result-count {
            color: #666;
            font-size: 14px;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .gig-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .gig-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
        }

        .gig-card-content {
            padding: 15px;
        }

        .gig-title {
            font-weight: bold;
            font-size: 16px;
            color: #007bff;
            margin-bottom: 8px;
        }

        .gig-description {
            color: #666;
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .gig-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #e0e0e0;
        }

        .gig-credit {
            font-weight: bold;
            color: #28a745;
            font-size: 16px;
        }

        .gig-category {
            display: inline-block;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: #666;
        }

        .gig-views {
            font-size: 12px;
            color: #999;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-search {
            text-align: center;
            padding: 40px;
            color: #999;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="search-container">
        <div class="search-header">
            <h1>Search Gigs</h1>
        </div>

        <!-- Search Form -->
        <form method="GET" class="search-form">
            <div class="search-row">
                <div class="form-group">
                    <label for="q">Keyword</label>
                    <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search for gigs...">
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <option value="IT" <?php echo $category === 'IT' ? 'selected' : ''; ?>>IT</option>
                        <option value="Writing" <?php echo $category === 'Writing' ? 'selected' : ''; ?>>Writing</option>
                        <option value="Others" <?php echo $category === 'Others' ? 'selected' : ''; ?>>Others</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sort">Sort By</label>
                    <select id="sort" name="sort">
                        <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="deadline" <?php echo $sort === 'deadline' ? 'selected' : ''; ?>>Deadline</option>
                    </select>
                </div>
            </div>

            <div class="search-row">
                <div class="form-group">
                    <label for="min">Min Price (৳)</label>
                    <input type="number" id="min" name="min" value="<?php echo $min_price; ?>" min="0">
                </div>

                <div class="form-group">
                    <label for="max">Max Price (৳)</label>
                    <input type="number" id="max" name="max" value="<?php echo $max_price; ?>" min="0">
                </div>
            </div>

            <div class="search-buttons">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="search.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>

        <!-- Search Results -->
        <?php if ($search_performed): ?>
            <?php if (count($results) > 0): ?>
                <div class="results-header">
                    <h2>Results</h2>
                    <span class="result-count"><?php echo count($results); ?> gig<?php echo count($results) !== 1 ? 's' : ''; ?> found</span>
                </div>

                <div class="results-grid">
                    <?php foreach ($results as $gig): ?>
                        <a href="freelancer/marketplace.php?gig=<?php echo $gig['GID']; ?>" class="gig-card">
                            <div class="gig-card-content">
                                <div class="gig-title">
                                    <?php echo htmlspecialchars(substr($gig['LIST_OF_GIGS'], 0, 50)); ?>
                                </div>

                                <div class="gig-description">
                                    <?php echo htmlspecialchars(substr($gig['LIST_OF_GIGS'], 0, 100)) . '...'; ?>
                                </div>

                                <div class="gig-meta">
                                    <div>
                                        <div class="gig-credit">৳<?php echo number_format($gig['CREDIT_AMOUNT'], 2); ?></div>
                                        <div class="gig-category"><?php echo $gig['CATAGORY']; ?></div>
                                    </div>
                                    <div>
                                        <div class="gig-views">👁️ <?php echo $gig['view_count']; ?> views</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>No gigs found matching your search criteria.</p>
                    <p style="font-size: 14px; margin-top: 10px;">Try adjusting your filters or search terms.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-search">
                <p>Enter search criteria above to find gigs</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
