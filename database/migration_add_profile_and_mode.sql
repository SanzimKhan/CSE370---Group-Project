USE bracu_freelance_marketplace;

ALTER TABLE `User`
    ADD COLUMN IF NOT EXISTS full_name VARCHAR(120) NULL AFTER Bracu_mail,
    ADD COLUMN IF NOT EXISTS address_line VARCHAR(255) NULL AFTER mobile_number,
    ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER address_line,
    ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(255) NULL AFTER bio,
    ADD COLUMN IF NOT EXISTS preferred_mode ENUM('hiring', 'working') NOT NULL DEFAULT 'hiring' AFTER freelancer;

UPDATE `User`
SET full_name = COALESCE(NULLIF(full_name, ''), BRACU_ID),
    preferred_mode = CASE
        WHEN preferred_mode IN ('hiring', 'working') THEN preferred_mode
        ELSE 'hiring'
    END;
