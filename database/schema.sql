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

CREATE INDEX idx_gigs_status ON `Gigs` (STATUS);
CREATE INDEX idx_gigs_category ON `Gigs` (CATAGORY);
CREATE INDEX idx_working_on_user ON `Working_on` (BRACU_ID);
CREATE INDEX idx_user_is_admin ON `User` (is_admin);
