

USE bracu_freelance_marketplace;

CREATE TABLE IF NOT EXISTS `Transaction_Ledger` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) NOT NULL UNIQUE,
    from_user VARCHAR(20) NOT NULL,
    to_user VARCHAR(20) NOT NULL,
    transaction_type ENUM('gig_payment', 'points_redemption', 'refund', 'bonus', 'withdrawal') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    points_transferred INT DEFAULT 0,
    gig_id INT NULL,
    status ENUM('pending', 'completed', 'failed', 'reversed') NOT NULL DEFAULT 'pending',
    description VARCHAR(255) NULL,
    metadata JSON NULL,
    batch_id VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    CONSTRAINT fk_ledger_from_user FOREIGN KEY (from_user)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_ledger_to_user FOREIGN KEY (to_user)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_ledger_gig FOREIGN KEY (gig_id)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_from_user (from_user),
    INDEX idx_to_user (to_user),
    INDEX idx_status (status),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_batch_id (batch_id)
);


CREATE TABLE IF NOT EXISTS `User_Points` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    BRACU_ID VARCHAR(20) NOT NULL UNIQUE,
    total_points INT NOT NULL DEFAULT 0,
    available_points INT NOT NULL DEFAULT 0,
    points_redeemed INT NOT NULL DEFAULT 0,
    lifetime_points INT NOT NULL DEFAULT 0,
    points_tier ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL DEFAULT 'bronze',
    last_points_earned_at TIMESTAMP NULL,
    last_points_redeemed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_points_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_total_points (total_points),
    INDEX idx_points_tier (points_tier)
);

CREATE TABLE IF NOT EXISTS `Points_Activity` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    BRACU_ID VARCHAR(20) NOT NULL,
    activity_type ENUM('earned', 'redeemed', 'bonus', 'expired') NOT NULL,
    points_amount INT NOT NULL,
    related_gig INT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_points_activity_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_points_activity_gig FOREIGN KEY (related_gig)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_user_activity (BRACU_ID),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS `Transaction_Batch` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(50) NOT NULL UNIQUE,
    batch_type ENUM('daily_settlements', 'points_conversion', 'refund_batch', 'bonus_distribution') NOT NULL,
    total_transactions INT NOT NULL DEFAULT 0,
    successful_transactions INT NOT NULL DEFAULT 0,
    failed_transactions INT NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    initiated_by VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_log JSON NULL,
    CONSTRAINT fk_batch_initiated_by FOREIGN KEY (initiated_by)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_batch_type (batch_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
CREATE TABLE IF NOT EXISTS `Transaction_Disputes` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispute_id VARCHAR(50) NOT NULL UNIQUE,
    transaction_id VARCHAR(50) NOT NULL,
    complainant_id VARCHAR(20) NOT NULL,
    respondent_id VARCHAR(20) NOT NULL,
    gig_id INT NULL,
    dispute_reason ENUM('payment_error', 'work_not_completed', 'quality_issue', 'duplicate_charge', 'unauthorized', 'other') NOT NULL,
    dispute_description TEXT NOT NULL,
    status ENUM('open', 'under_review', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    resolution_type ENUM('refund', 'partial_refund', 'accepted', 'rejected') NULL,
    refund_amount DECIMAL(10,2) NULL,
    admin_notes TEXT NULL,
    resolved_by VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    CONSTRAINT fk_dispute_transaction FOREIGN KEY (transaction_id)
        REFERENCES `Transaction_Ledger` (transaction_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_dispute_complainant FOREIGN KEY (complainant_id)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_dispute_respondent FOREIGN KEY (respondent_id)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_dispute_gig FOREIGN KEY (gig_id)
        REFERENCES `Gigs` (GID)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_dispute_resolved_by FOREIGN KEY (resolved_by)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_complainant (complainant_id),
    INDEX idx_respondent (respondent_id),
    INDEX idx_created_at (created_at),
    INDEX idx_dispute_reason (dispute_reason)
);

CREATE TABLE IF NOT EXISTS `Redemption_History` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    redemption_id VARCHAR(50) NOT NULL UNIQUE,
    BRACU_ID VARCHAR(20) NOT NULL,
    points_redeemed INT NOT NULL,
    credit_received DECIMAL(10,2) NOT NULL,
    redemption_rate DECIMAL(5,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    CONSTRAINT fk_redemption_user FOREIGN KEY (BRACU_ID)
        REFERENCES `User` (BRACU_ID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_user (BRACU_ID),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

INSERT INTO User_Points (BRACU_ID, total_points, available_points, points_tier)
SELECT BRACU_ID, 0, 0, 'bronze' FROM User
WHERE BRACU_ID NOT IN (SELECT BRACU_ID FROM User_Points);

CREATE INDEX idx_ledger_from_to ON Transaction_Ledger (from_user, to_user);
CREATE INDEX idx_ledger_gig_status ON Transaction_Ledger (gig_id, status);
CREATE INDEX idx_points_tier_date ON User_Points (points_tier, updated_at);
