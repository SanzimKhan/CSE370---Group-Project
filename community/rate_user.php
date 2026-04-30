<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/community.php';

// Check authentication
require_login();

$conn = getConnection();
$community = new Community($conn);

// Get user to rate
$rate_user_id = $_GET['user'] ?? null;
$gig_id = (int) ($_GET['gig'] ?? 0);

if (!$rate_user_id) {
    header('Location: /index.php');
    exit;
}

// Verify user exists
$query = "SELECT full_name FROM User WHERE BRACU_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $rate_user_id);
$stmt->execute();
$rate_user = $stmt->get_result()->fetch_assoc();

if (!$rate_user) {
    header('Location: /index.php');
    exit;
}

// Get gig details if provided
$gig_info = null;
if ($gig_id > 0) {
    $query = "SELECT GID, LIST_OF_GIGS, CREDIT_AMOUNT FROM Gigs WHERE GID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $gig_id);
    $stmt->execute();
    $gig_info = $stmt->get_result()->fetch_assoc();
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $rating = (int) ($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');
    $is_client = isset($_POST['is_client']);

    if ($rating >= 1 && $rating <= 5) {
        $community->createRating(
            $_SESSION['user_id'],
            $rate_user_id,
            $rating,
            $review ?: null,
            $gig_id ?: null,
            $is_client
        );

        // Check and award badges
        $community->checkAndAwardBadges($rate_user_id);

        header('Location: profile.php?id=' . urlencode($rate_user_id) . '&rated=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate <?php echo htmlspecialchars($rate_user['full_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .rating-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .rating-form {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .form-subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .rating-stars {
            display: flex;
            gap: 10px;
            font-size: 32px;
        }

        .star-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: #ddd;
            transition: all 0.2s;
        }

        .star-btn:hover,
        .star-btn.active {
            color: #ffc107;
            transform: scale(1.1);
        }

        .rating-display {
            margin-top: 10px;
            font-weight: bold;
            color: #ffc107;
        }

        .textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 120px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-label {
            cursor: pointer;
            color: #666;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
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

        .gig-info {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .gig-info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }

        .gig-info-value {
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .char-count {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="rating-container">
        <a href="profile.php?id=<?php echo urlencode($rate_user_id); ?>" class="back-link">← Back to Profile</a>

        <div class="rating-form">
            <h1 class="form-title">Rate <?php echo htmlspecialchars($rate_user['full_name']); ?></h1>
            <p class="form-subtitle">Share your experience working with this user</p>

            <?php if ($gig_info): ?>
                <div class="gig-info">
                    <div class="gig-info-label">Gig:</div>
                    <div class="gig-info-value"><?php echo htmlspecialchars(substr($gig_info['LIST_OF_GIGS'], 0, 80)); ?></div>
                    <div class="gig-info-label" style="margin-top: 10px;">Amount:</div>
                    <div class="gig-info-value">৳<?php echo number_format($gig_info['CREDIT_AMOUNT'], 2); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Rating Stars -->
                <div class="form-group">
                    <label>Your Rating *</label>
                    <div class="rating-stars" id="ratingStars">
                        <button type="button" class="star-btn" data-rating="1">★</button>
                        <button type="button" class="star-btn" data-rating="2">★</button>
                        <button type="button" class="star-btn" data-rating="3">★</button>
                        <button type="button" class="star-btn" data-rating="4">★</button>
                        <button type="button" class="star-btn" data-rating="5">★</button>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                    <div class="rating-display" id="ratingDisplay">Select a rating</div>
                </div>

                <!-- Review Text -->
                <div class="form-group">
                    <label for="review">Review (Optional)</label>
                    <textarea id="review" name="review" class="textarea" placeholder="Share details about your experience..." maxlength="500"></textarea>
                    <div class="char-count"><span id="charCount">0</span>/500</div>
                </div>

                <!-- Is Client Rating -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="isClient" name="is_client" value="1">
                        <label for="isClient" class="checkbox-label">Rating as a client (not freelancer)</label>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="form-buttons">
                    <button type="submit" name="submit_rating" class="btn btn-primary">Submit Rating</button>
                    <a href="profile.php?id=<?php echo urlencode($rate_user_id); ?>" class="btn btn-secondary" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const stars = document.querySelectorAll('.star-btn');
        const ratingInput = document.getElementById('ratingInput');
        const ratingDisplay = document.getElementById('ratingDisplay');
        const reviewText = document.getElementById('review');
        const charCount = document.getElementById('charCount');

        // Star rating
        stars.forEach(star => {
            star.addEventListener('click', function(e) {
                e.preventDefault();
                const rating = this.dataset.rating;
                ratingInput.value = rating;
                ratingDisplay.textContent = `${rating} out of 5 stars`;
                
                stars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseover', function() {
                const rating = this.dataset.rating;
                stars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });

        document.getElementById('ratingStars').addEventListener('mouseout', function() {
            const currentRating = ratingInput.value;
            stars.forEach(s => {
                if (s.dataset.rating <= currentRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });

        // Character counter
        reviewText.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    </script>
</body>
</html>
