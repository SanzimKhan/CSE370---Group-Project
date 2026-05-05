<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/community.php';

// Check authentication
require_login();

$pdo = db();
$community = new Community($pdo);

// Handle new thread creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_thread'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'General';

    if ($title && $description) {
        $community->createForumThread($_SESSION['user_id'], $title, $description, $category);
        header('Location: forum.php');
        exit;
    }
}

// Get category filter
$category = $_GET['category'] ?? '';
$page = (int) ($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get threads
$threads = $community->getForumThreads($category, $limit, $offset);

// Get user info for display
$query = "SELECT full_name, avatar_path FROM User WHERE BRACU_ID = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Forum - BRACU Freelance Marketplace</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .forum-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .forum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .create-thread-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .create-thread-btn:hover {
            background: #0056b3;
        }

        .category-filter {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .category-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .thread-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
        }

        .thread-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background: #f0f0f0;
        }

        .thread-content {
            flex: 1;
        }

        .thread-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .thread-title:hover {
            text-decoration: underline;
        }

        .thread-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }

        .thread-excerpt {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
        }

        .thread-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            font-size: 12px;
            color: #999;
        }

        .thread-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 10px;
        }

        .badge-pinned {
            background: #ffc107;
            color: #333;
        }

        .badge-locked {
            background: #dc3545;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal.show {
            display: block;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .submit-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
        }

        .submit-btn:hover {
            background: #0056b3;
        }

        .no-threads {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="forum-container">
        <div class="forum-header">
            <h1>Community Forum</h1>
            <button class="create-thread-btn" onclick="openCreateThreadModal()">Create Thread</button>
        </div>

        <!-- Category Filter -->
        <div class="category-filter">
            <a href="forum.php" class="category-btn <?php echo $category === '' ? 'active' : ''; ?>">All</a>
            <a href="?category=General" class="category-btn <?php echo $category === 'General' ? 'active' : ''; ?>">General</a>
            <a href="?category=Tips" class="category-btn <?php echo $category === 'Tips' ? 'active' : ''; ?>">Tips</a>
            <a href="?category=Help" class="category-btn <?php echo $category === 'Help' ? 'active' : ''; ?>">Help</a>
            <a href="?category=Showcase" class="category-btn <?php echo $category === 'Showcase' ? 'active' : ''; ?>">Showcase</a>
        </div>

        <!-- Threads List -->
        <div class="threads-list">
            <?php if (count($threads) > 0): ?>
                <?php foreach ($threads as $thread): ?>
                    <div class="thread-item">
                        <img src="<?php echo htmlspecialchars($thread['avatar_path'] ?? '/assets/uploads/avatars/default.png'); ?>" 
                             alt="<?php echo htmlspecialchars($thread['full_name']); ?>" 
                             class="thread-avatar">
                        <div class="thread-content">
                            <a href="forum_view.php?id=<?php echo $thread['id']; ?>" class="thread-title">
                                <?php echo htmlspecialchars($thread['title']); ?>
                                <?php if ($thread['is_pinned']): ?>
                                    <span class="thread-badge badge-pinned">PINNED</span>
                                <?php endif; ?>
                                <?php if ($thread['is_locked']): ?>
                                    <span class="thread-badge badge-locked">LOCKED</span>
                                <?php endif; ?>
                            </a>
                            <div class="thread-meta">
                                Started by <strong><?php echo htmlspecialchars($thread['full_name']); ?></strong>
                                on <?php echo date('M d, Y', strtotime($thread['created_at'])); ?>
                            </div>
                            <div class="thread-excerpt">
                                <?php echo htmlspecialchars(substr($thread['description'], 0, 150)) . '...'; ?>
                            </div>
                            <div class="thread-stats">
                                <span>👁️ <?php echo $thread['view_count']; ?> Views</span>
                                <span>💬 <?php echo $thread['reply_count']; ?> Replies</span>
                                <span><?php echo $thread['category']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-threads">
                    <p>No threads in this category yet. Be the first to create one!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Thread Modal -->
    <div id="createThreadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span>Create New Thread</span>
                <button class="close-btn" onclick="closeCreateThreadModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required placeholder="Thread title">
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="General">General</option>
                        <option value="Tips">Tips & Advice</option>
                        <option value="Help">Help & Support</option>
                        <option value="Showcase">Showcase</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required placeholder="Share your thoughts..."></textarea>
                </div>

                <button type="submit" name="create_thread" class="submit-btn">Create Thread</button>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function openCreateThreadModal() {
            document.getElementById('createThreadModal').classList.add('show');
        }

        function closeCreateThreadModal() {
            document.getElementById('createThreadModal').classList.remove('show');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('createThreadModal');
            if (event.target === modal) {
                modal.classList.remove('show');
            }
        }
    </script>
</body>
</html>
