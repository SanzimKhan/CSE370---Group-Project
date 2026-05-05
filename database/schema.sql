CREATE DATABASE IF NOT EXISTS bracu_freelance_marketplace;
USE bracu_freelance_marketplace;

CREATE TABLE IF NOT EXISTS `User` (
    BRACU_ID VARCHAR(20) PRIMARY KEY,
    Bracu_mail VARCHAR(120) NOT NULL UNIQUE,
    full_name VARCHAR(120) NULL,
    client TINYINT(1) NOT NULL DEFAULT 1,
    mobile_number VARCHAR(20) NOT NULL,
    address_line VARCHAR(255) NULL,
    bio TEXT NULL,
    avatar_path VARCHAR(255) NULL,
    password VARCHAR(255) NOT NULL,
    freelancer TINYINT(1) NOT NULL DEFAULT 1,
    preferred_mode ENUM('hiring', 'working') NOT NULL DEFAULT 'hiring',
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    credit_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `Gigs` (
    GID INT AUTO_INCREMENT PRIMARY KEY,
    BRACU_ID VARCHAR(20) NOT NULL,
    CREDIT_AMOUNT DECIMAL(10,2) NOT NULL,
    LIST_OF_GIGS TEXT NOT NULL,
    CATAGORY ENUM('IT', 'Writing', 'Others') NOT NULL,
    DEADLINE DATE NOT NULL,
    STATUS ENUM('listed', 'pending', 'done') NOT NULL DEFAULT 'listed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gigs_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `Working_on` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    BRACU_ID VARCHAR(20) NOT NULL,
    GID INT NOT NULL,
    credit DECIMAL(10,2) NOT NULL,
    accepted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    done_at TIMESTAMP NULL,
    payment_released TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT uq_working_on_gid UNIQUE (GID),
    CONSTRAINT fk_working_on_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_working_on_gig FOREIGN KEY (GID)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_gigs_status ON `Gigs` (STATUS);
CREATE INDEX IF NOT EXISTS idx_gigs_category ON `Gigs` (CATAGORY);
CREATE INDEX IF NOT EXISTS idx_working_on_user ON `Working_on` (BRACU_ID);
CREATE INDEX IF NOT EXISTS idx_user_is_admin ON `User` (is_admin);

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

CREATE INDEX IF NOT EXISTS idx_analytics_user ON `Analytics_Activity` (BRACU_ID);
CREATE INDEX IF NOT EXISTS idx_analytics_type ON `Analytics_Activity` (activity_type);
CREATE INDEX IF NOT EXISTS idx_analytics_created ON `Analytics_Activity` (created_at);
CREATE INDEX IF NOT EXISTS idx_gig_views_gid ON `Gig_Views` (GID);
CREATE INDEX IF NOT EXISTS idx_gig_views_user ON `Gig_Views` (BRACU_ID);
CREATE INDEX IF NOT EXISTS idx_earnings_user ON `User_Earnings` (BRACU_ID);
CREATE INDEX IF NOT EXISTS idx_earnings_status ON `User_Earnings` (status);
CREATE INDEX IF NOT EXISTS idx_ratings_rater ON `Ratings` (rater_id);
CREATE INDEX IF NOT EXISTS idx_ratings_ratee ON `Ratings` (ratee_id);
CREATE INDEX IF NOT EXISTS idx_ratings_gig ON `Ratings` (gig_id);
CREATE INDEX IF NOT EXISTS idx_messages_sender ON `Messages` (sender_id);
CREATE INDEX IF NOT EXISTS idx_messages_recipient ON `Messages` (recipient_id);
CREATE INDEX IF NOT EXISTS idx_messages_read ON `Messages` (is_read);
CREATE INDEX IF NOT EXISTS idx_forum_threads_creator ON `Forum_Threads` (creator_id);
CREATE INDEX IF NOT EXISTS idx_forum_threads_category ON `Forum_Threads` (category);
CREATE INDEX IF NOT EXISTS idx_forum_threads_pinned ON `Forum_Threads` (is_pinned);
CREATE INDEX IF NOT EXISTS idx_forum_replies_thread ON `Forum_Replies` (thread_id);
CREATE INDEX IF NOT EXISTS idx_forum_replies_author ON `Forum_Replies` (author_id);
