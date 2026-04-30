-- Analytics, Indexing, and Community Features Migration

-- ===== ANALYTICS TABLES =====

-- Track user activities for analytics
CREATE TABLE IF NOT EXISTS `Analytics_Activity` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    BRACU_ID VARCHAR(20) NOT NULL,
    activity_type ENUM('login', 'gig_view', 'gig_create', 'gig_apply', 'profile_view', 'message_send') NOT NULL,
    gig_id INT NULL,
    target_user VARCHAR(20) NULL,
    activity_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_analytics_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_analytics_gig FOREIGN KEY (gig_id)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- Track gig views
CREATE TABLE IF NOT EXISTS `Gig_Views` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    GID INT NOT NULL,
    BRACU_ID VARCHAR(20) NULL,
    viewer_ip VARCHAR(45) NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gig_views_gig FOREIGN KEY (GID)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_gig_views_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- Track user earnings
CREATE TABLE IF NOT EXISTS `User_Earnings` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    BRACU_ID VARCHAR(20) NOT NULL,
    gig_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'released', 'refunded') NOT NULL DEFAULT 'pending',
    earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    released_at TIMESTAMP NULL,
    CONSTRAINT fk_earnings_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_earnings_gig FOREIGN KEY (gig_id)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- ===== COMMUNITY TABLES =====

-- Ratings and Reviews
CREATE TABLE IF NOT EXISTS `Ratings` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rater_id VARCHAR(20) NOT NULL,
    ratee_id VARCHAR(20) NOT NULL,
    gig_id INT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT NULL,
    is_client_rating TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ratings_rater FOREIGN KEY (rater_id)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_ratings_ratee FOREIGN KEY (ratee_id)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_ratings_gig FOREIGN KEY (gig_id)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    UNIQUE KEY unique_gig_rating (rater_id, ratee_id, gig_id)
);

-- User Reputation/Badges
CREATE TABLE IF NOT EXISTS `User_Badges` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    BRACU_ID VARCHAR(20) NOT NULL,
    badge_type ENUM('verified', 'top_rated', 'responsive', 'trusted', 'new_member') NOT NULL,
    badge_name VARCHAR(100) NOT NULL,
    badge_description TEXT NULL,
    earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_badges_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (BRACU_ID, badge_type)
);

-- Direct Messaging
CREATE TABLE IF NOT EXISTS `Messages` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id VARCHAR(20) NOT NULL,
    recipient_id VARCHAR(20) NOT NULL,
    gig_id INT NULL,
    message_text TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_messages_gig FOREIGN KEY (gig_id)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- Community Forums/Discussions
CREATE TABLE IF NOT EXISTS `Forum_Threads` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('General', 'Tips', 'Help', 'Showcase') NOT NULL DEFAULT 'General',
    view_count INT NOT NULL DEFAULT 0,
    reply_count INT NOT NULL DEFAULT 0,
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_thread_creator FOREIGN KEY (creator_id)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `Forum_Replies` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    author_id VARCHAR(20) NOT NULL,
    reply_text TEXT NOT NULL,
    reply_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reply_thread FOREIGN KEY (thread_id)
        REFERENCES `Forum_Threads` (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_reply_author FOREIGN KEY (author_id)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- ===== INDEXING TABLES =====

-- Full-text search index for gigs
CREATE TABLE IF NOT EXISTS `Gig_Search_Index` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    GID INT NOT NULL UNIQUE,
    search_keywords TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT INDEX ft_keywords (search_keywords),
    CONSTRAINT fk_search_index_gig FOREIGN KEY (GID)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- ===== INDEXES FOR PERFORMANCE =====

-- Analytics indexes
CREATE INDEX idx_analytics_user ON `Analytics_Activity` (BRACU_ID);
CREATE INDEX idx_analytics_type ON `Analytics_Activity` (activity_type);
CREATE INDEX idx_analytics_created ON `Analytics_Activity` (created_at);
CREATE INDEX idx_gig_views_gid ON `Gig_Views` (GID);
CREATE INDEX idx_gig_views_user ON `Gig_Views` (BRACU_ID);
CREATE INDEX idx_earnings_user ON `User_Earnings` (BRACU_ID);
CREATE INDEX idx_earnings_status ON `User_Earnings` (status);

-- Community indexes
CREATE INDEX idx_ratings_rater ON `Ratings` (rater_id);
CREATE INDEX idx_ratings_ratee ON `Ratings` (ratee_id);
CREATE INDEX idx_ratings_gig ON `Ratings` (gig_id);
CREATE INDEX idx_messages_sender ON `Messages` (sender_id);
CREATE INDEX idx_messages_recipient ON `Messages` (recipient_id);
CREATE INDEX idx_messages_read ON `Messages` (is_read);
CREATE INDEX idx_forum_threads_creator ON `Forum_Threads` (creator_id);
CREATE INDEX idx_forum_threads_category ON `Forum_Threads` (category);
CREATE INDEX idx_forum_threads_pinned ON `Forum_Threads` (is_pinned);
CREATE INDEX idx_forum_replies_thread ON `Forum_Replies` (thread_id);
CREATE INDEX idx_forum_replies_author ON `Forum_Replies` (author_id);
