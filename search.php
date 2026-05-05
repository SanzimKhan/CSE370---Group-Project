<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/search.php';

// Require login
$user = require_login();
$pageTitle = 'Search Gigs';

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

require_once __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="kicker">Gig Discovery</div>
    <h1>Search Gigs</h1>
    <p class="muted">Find and explore available gigs that match your interests.</p>

    <!-- Search Form -->
    <form method="GET" action="search.php">
        <div class="grid cols-3">
            <div class="form-row">
                <label for="q">Keyword</label>
                <input type="text" id="q" name="q" value="<?= h($query) ?>" placeholder="Search for gigs...">
            </div>

            <div class="form-row">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All Categories</option>
                    <option value="IT" <?= $category === 'IT' ? 'selected' : '' ?>>IT</option>
                    <option value="Writing" <?= $category === 'Writing' ? 'selected' : '' ?>>Writing</option>
                    <option value="Others" <?= $category === 'Others' ? 'selected' : '' ?>>Others</option>
                </select>
            </div>

            <div class="form-row">
                <label for="sort">Sort By</label>
                <select id="sort" name="sort">
                    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                    <option value="deadline" <?= $sort === 'deadline' ? 'selected' : '' ?>>Deadline</option>
                </select>
            </div>
        </div>

        <div class="grid cols-3">
            <div class="form-row">
                <label for="min">Min Price (৳)</label>
                <input type="number" id="min" name="min" value="<?= $min_price ?>" min="0">
            </div>

            <div class="form-row">
                <label for="max">Max Price (৳)</label>
                <input type="number" id="max" name="max" value="<?= $max_price ?>" min="0">
            </div>

            <div class="form-row">
                <button type="submit">Search</button>
                <a href="search.php" class="button-secondary">Clear</a>
            </div>
        </div>
    </form>

    <!-- Search Results -->
    <?php if ($search_performed): ?>
        <?php if (count($results) > 0): ?>
            <h2>Results</h2>
            <p class="muted"><?= count($results) ?> gig<?= count($results) !== 1 ? 's' : '' ?> found</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Deadline</th>
                            <th>Credit (৳)</th>
                            <th>Client</th>
                            <th>Views</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $gig): ?>
                            <?php $isOwnGig = $gig['BRACU_ID'] === $user['BRACU_ID']; ?>
                            <tr>
                                <td><?= h(substr($gig['LIST_OF_GIGS'], 0, 50)) ?></td>
                                <td><?= h($gig['CATAGORY']) ?></td>
                                <td><?= h($gig['DEADLINE'] ?? 'N/A') ?></td>
                                <td><?= h(format_credit((float) $gig['CREDIT_AMOUNT'])) ?></td>
                                <td><?= h($gig['full_name'] ?? $gig['BRACU_ID']) ?></td>
                                <td><?= (int) ($gig['view_count'] ?? 0) ?></td>
                                <td>
                                    <?php if ($isOwnGig): ?>
                                        <span class="muted">Your gig</span>
                                    <?php else: ?>
                                        <a href="freelancer/marketplace.php?gig=<?= (int) $gig['GID'] ?>" class="button-secondary">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="muted">
                <p>No gigs found matching your search criteria.</p>
                <p>Try adjusting your filters or search terms.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="muted">Enter search criteria above to find gigs</p>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
