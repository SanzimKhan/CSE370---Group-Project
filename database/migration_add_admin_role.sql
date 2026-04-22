USE bracu_freelance_marketplace;

ALTER TABLE `User`
    ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER freelancer;

-- Make sure at least one admin exists (demo admin).
UPDATE `User`
SET is_admin = 1
WHERE BRACU_ID = '20101001';
